<?php

namespace App\Services\Whatsapp;

use App\Models\WaAccount;
use App\Models\WaDelivery;
use App\Models\WaTemplate;
use App\Services\Tenants\DeliveryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Le versioni del modello del promemoria di ogni numero.
 *
 *  - al collegamento si creano i testi di partenza (IT e EN);
 *  - il ristoratore può proporre un testo nuovo: diventa una versione nuova
 *    che Meta deve approvare, e intanto continua a partire quella in uso;
 *  - quando Meta approva, la versione nuova prende il posto della vecchia;
 *    se la rifiuta, resta quella vecchia e il motivo si mostra al ristoratore.
 *
 * Gli stati arrivano dal webhook message_template_status_update e, per
 * sicurezza, dal comando wa:sync-templates. Ogni cambio si comunica al
 * gestionale (endpoint "templates").
 */
class TemplateManager
{
    public function __construct(
        private MetaGraph $meta,
        private DeliveryService $deliveries,
    ) {
    }

    /** Al collegamento: i testi di partenza, o quelli già su Meta se il numero li ha. */
    public function createDefaults(WaAccount $account): void
    {
        foreach (ReminderTemplate::DEFAULTS as $language => $text) {
            $created = $this->meta->createTemplate(
                $account->waba_id,
                ReminderTemplate::definition(ReminderTemplate::BASE_NAME, $language, $text['body'], $text['buttons']),
                $account->token,
            );

            if ($created === null) {
                continue; // c'era già: lo legge sync() qui sotto
            }

            $this->store($account, ReminderTemplate::BASE_NAME, $language, $text['body'], $text['buttons'], $created);
        }

        $this->sync($account, notify: false);
    }

    /**
     * Un testo nuovo del ristoratore per una lingua.
     *
     * @return array{ok: bool, errors: string[], template: ?WaTemplate}
     */
    public function propose(WaAccount $account, string $language, string $body, array $buttons): array
    {
        $errors = ReminderTemplate::validate($body, $buttons);

        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors, 'template' => null];
        }

        $body = ReminderTemplate::normalize($body);
        $buttons = array_map(fn ($label) => trim((string) $label), array_values($buttons));

        // Una proposta ancora in attesa si sostituisce: conta solo l'ultima.
        $account->templates()
            ->where('language', $language)
            ->where('current', false)
            ->whereIn('status', [WaTemplate::PENDING, WaTemplate::REJECTED])
            ->get()
            ->each(function (WaTemplate $old) use ($account) {
                try {
                    $this->meta->deleteTemplate($account->waba_id, $old->name, $old->meta_id, $account->token);
                } catch (\Throwable $e) {
                    Log::info('(WA Templates) Versione superata non eliminata da Meta', ['name' => $old->name, 'error' => $e->getMessage()]);
                }

                $old->update(['status' => WaTemplate::SUPERSEDED]);
            });

        $name = $this->nextName($account, $language);

        try {
            $created = $this->meta->createTemplate(
                $account->waba_id,
                ReminderTemplate::definition($name, $language, $body, $buttons),
                $account->token,
            );
        } catch (MetaGraphException $e) {
            return ['ok' => false, 'errors' => [$e->getMessage()], 'template' => null];
        }

        $template = $this->store($account, $name, $language, $body, $buttons, $created ?? []);
        $this->settle($template);
        $this->notify($account);

        return ['ok' => true, 'errors' => [], 'template' => $template->fresh()];
    }

    /** Uno stato nuovo da Meta (webhook). Restituisce la consegna al gestionale, se serve. */
    public function applyStatus(WaTemplate $template, string $status, ?string $category = null, ?string $reason = null): ?WaDelivery
    {
        return $this->updateStatus($template, $status, $category, $reason)
            ? $this->notify($template->account, queue: true)
            : null;
    }

    /** true se è cambiato qualcosa. */
    private function updateStatus(WaTemplate $template, string $status, ?string $category = null, ?string $reason = null): bool
    {
        $status = strtoupper($status);

        if ($template->status === WaTemplate::SUPERSEDED) {
            return false;
        }

        $template->fill(array_filter([
            'status' => $status,
            'category' => $category ? strtoupper($category) : null,
        ]));
        $template->reason = $status === WaTemplate::REJECTED ? $this->reasonText($reason) : null;

        if (! $template->isDirty()) {
            return false;
        }

        $template->save();
        $this->settle($template);

        return true;
    }

    /**
     * Allinea le righe con quello che c'è su Meta: stati, e i template creati
     * prima che l'hub li tenesse in tabella (si riconoscono dal nome).
     */
    public function sync(WaAccount $account, bool $notify = true): void
    {
        $remote = $this->meta->templates($account->waba_id, $account->token, ReminderTemplate::BASE_NAME);
        $changed = false;

        foreach ($remote as $item) {
            $language = (string) ($item['language'] ?? '');
            $template = $account->templates()
                ->where(fn ($q) => $q->where('meta_id', (string) $item['id'])->orWhere(fn ($q) => $q
                    ->where('name', $item['name'])->where('language', $language)))
                ->first();

            if (! $template) {
                $template = $this->import($account, $item);
                $changed = $changed || $template !== null;

                continue;
            }

            if (! $template->meta_id) {
                $template->update(['meta_id' => (string) $item['id']]);
            }

            $changed = $this->updateStatus($template, (string) ($item['status'] ?? $template->status), $item['category'] ?? null, $item['rejected_reason'] ?? null) || $changed;
        }

        // Una lingua senza versione in uso prende l'ultima approvata.
        foreach ($account->templates()->pluck('language')->unique() as $language) {
            if (! $account->templates()->where('language', $language)->where('current', true)->exists()) {
                $latest = $account->templates()->where('language', $language)->where('status', WaTemplate::APPROVED)->latest('id')->first();

                if ($latest) {
                    $this->makeCurrent($latest);
                    $changed = true;
                }
            }
        }

        if ($changed && $notify) {
            $this->deliveries->attemptMany(array_filter([$this->notify($account, queue: true)?->id]));
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function payload(WaAccount $account): array
    {
        return $account->templates()
            ->where('status', '!=', WaTemplate::SUPERSEDED)
            ->orderBy('language')
            ->orderByDesc('id')
            ->get()
            ->map(fn (WaTemplate $template) => $template->toTenantArray())
            ->all();
    }

    /** Avvisa il gestionale del ristorante; con queue la consegna parte dopo. */
    public function notify(WaAccount $account, bool $queue = false): ?WaDelivery
    {
        if (! $account->tenant) {
            return null;
        }

        $payload = ['templates' => $this->payload($account)];

        return $queue
            ? $this->deliveries->queue($account->tenant, 'templates', $payload)
            : $this->deliveries->deliver($account->tenant, 'templates', $payload);
    }

    /** Approvata la versione più nuova di una lingua: prende il posto di quella in uso. */
    private function settle(WaTemplate $template): void
    {
        if ($template->status !== WaTemplate::APPROVED || $template->current) {
            return;
        }

        $newer = $template->account->templates()
            ->where('language', $template->language)
            ->where('id', '>', $template->id)
            ->where('status', '!=', WaTemplate::SUPERSEDED)
            ->exists();

        if (! $newer) {
            $this->makeCurrent($template);
        }
    }

    private function makeCurrent(WaTemplate $template): void
    {
        DB::transaction(function () use ($template) {
            $template->account->templates()
                ->where('language', $template->language)
                ->where('id', '!=', $template->id)
                ->update(['current' => false]);

            $template->update(['current' => true]);
        });
    }

    private function store(WaAccount $account, string $name, string $language, string $body, array $buttons, array $created): WaTemplate
    {
        return WaTemplate::updateOrCreate(
            ['wa_account_id' => $account->id, 'name' => $name, 'language' => $language],
            [
                'meta_id' => $created['id'] ?? null,
                'status' => strtoupper((string) ($created['status'] ?? WaTemplate::PENDING)),
                'category' => isset($created['category']) ? strtoupper((string) $created['category']) : null,
                'body' => ReminderTemplate::normalize($body),
                'params' => ReminderTemplate::params(ReminderTemplate::normalize($body)),
                'buttons' => array_values($buttons),
                'reason' => null,
            ],
        );
    }

    /** Un template su Meta che l'hub non conosceva: lo si legge dal suo testo. */
    private function import(WaAccount $account, array $item): ?WaTemplate
    {
        $components = collect($item['components'] ?? []);
        $bodyText = (string) data_get($components->firstWhere('type', 'BODY'), 'text', '');
        $buttons = collect(data_get($components->firstWhere('type', 'BUTTONS'), 'buttons', []))->pluck('text')->all();

        if ($bodyText === '' || count($buttons) !== 2) {
            return null;
        }

        // Il testo di partenza ha sempre avuto questo ordine di variabili.
        $params = ['nome', 'locale', 'data', 'ora', 'persone'];
        preg_match_all('/\{\{(\d+)\}\}/', $bodyText, $placeholders);
        $params = array_slice($params, 0, count($placeholders[1]));

        $template = WaTemplate::create([
            'wa_account_id' => $account->id,
            'name' => (string) $item['name'],
            'language' => (string) $item['language'],
            'meta_id' => (string) $item['id'],
            'status' => strtoupper((string) ($item['status'] ?? WaTemplate::PENDING)),
            'category' => isset($item['category']) ? strtoupper((string) $item['category']) : null,
            'body' => ReminderTemplate::fromMeta($bodyText, $params),
            'params' => $params,
            'buttons' => $buttons,
        ]);

        $this->settle($template);

        return $template;
    }

    private function nextName(WaAccount $account, string $language): string
    {
        $version = $account->templates()->where('language', $language)->count() + 1;

        do {
            $name = ReminderTemplate::BASE_NAME . '_' . $language . '_' . $version++;
        } while (WaTemplate::where('wa_account_id', $account->id)->where('name', $name)->exists());

        return $name;
    }

    /** I codici di rifiuto di Meta, detti in modo comprensibile. */
    private function reasonText(?string $reason): ?string
    {
        $reason = strtoupper(trim((string) $reason));

        return match ($reason) {
            '', 'NONE' => 'Meta non ha indicato il motivo.',
            'INCORRECT_CATEGORY' => 'Meta lo considera pubblicitario: togli frasi promozionali (offerte, inviti a tornare…) e lascia solo il promemoria.',
            'INVALID_FORMAT' => 'Formato non accettato da Meta: controlla variabili, a capo e lunghezza.',
            'TAG_CONTENT_MISMATCH', 'SCAM', 'PROMOTIONAL' => 'Meta lo considera pubblicitario o poco chiaro: tienilo semplice e informativo.',
            'ABUSIVE_CONTENT' => 'Meta ha trovato contenuti non ammessi nel testo.',
            default => 'Rifiutato da Meta (' . $reason . ').',
        };
    }
}

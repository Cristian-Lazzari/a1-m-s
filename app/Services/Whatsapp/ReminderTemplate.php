<?php

namespace App\Services\Whatsapp;

/**
 * Il promemoria che template-pro-be manda X ore prima della prenotazione.
 *
 * Il ristoratore scrive il testo con le variabili per nome ({nome}, {locale},
 * {data}, {ora}, {persone}); qui diventa il formato di Meta ({{1}}, {{2}}… in
 * ordine di apparizione) e si ricorda l'ordine in "params", perché chi invia
 * sappia cosa mettere in ogni posto. Due pulsanti quick reply: il primo
 * conferma, il secondo disdice (il payload lo mette chi invia).
 *
 * Categoria UTILITY: niente frasi promozionali, o Meta lo passa a MARKETING.
 * Le regole di validate() sono quelle per cui Meta rifiuta un modello: meglio
 * dirlo subito al ristoratore che dopo giorni di attesa.
 */
class ReminderTemplate
{
    public const BASE_NAME = 'promemoria_prenotazione';

    public const MAX_BODY = 1024;

    /** 25 nei template, ma 20 nei pulsanti dei messaggi interattivi: vale il più stretto. */
    public const MAX_BUTTON = 20;

    /** Variabili ammesse e quelle che un promemoria deve avere. */
    public const VARIABLES = ['nome', 'locale', 'data', 'ora', 'persone'];

    public const REQUIRED = ['data', 'ora'];

    private const PATTERN = '/\{\s*([\pL_]+)\s*\}/u';

    /** Testi di partenza, uguali a quelli approvati sui primi numeri. */
    public const DEFAULTS = [
        'it' => [
            'body' => "Ciao {nome}, ti ricordiamo la prenotazione da {locale} per {data} alle {ora}, {persone} persone.\n\nSe non riesci più a venire, avvisaci con un tocco: libererai il tavolo per qualcun altro.",
            'buttons' => ['Confermo', 'Devo disdire'],
        ],
        'en' => [
            'body' => "Hi {nome}, this is a reminder of your booking at {locale} on {data} at {ora} for {persone} people.\n\nIf you can no longer make it, let us know with one tap: you will free the table for someone else.",
            'buttons' => ["I'll be there", 'Cancel booking'],
        ],
    ];

    private const EXAMPLES = [
        'it' => ['nome' => 'Luca', 'locale' => 'Trattoria da Mario', 'data' => 'sabato 12 ottobre', 'ora' => '20:30', 'persone' => '4'],
        'en' => ['nome' => 'Luke', 'locale' => 'Trattoria da Mario', 'data' => 'Saturday 12 October', 'ora' => '8:30 PM', 'persone' => '4'],
    ];

    /**
     * Testo ripulito come lo salverà Meta: spazi in fondo alle righe tolti, al
     * massimo una riga vuota di fila, variabili scritte sempre uguali.
     */
    public static function normalize(string $body): string
    {
        $body = str_replace(["\r\n", "\r"], "\n", $body);
        $body = preg_replace('/[ \t]+$/mu', '', $body);
        $body = preg_replace("/\n{3,}/", "\n\n", $body);
        $body = preg_replace_callback(self::PATTERN, fn ($m) => '{' . mb_strtolower($m[1]) . '}', $body);

        return trim($body);
    }

    /** @return string[] le variabili nell'ordine in cui compaiono */
    public static function params(string $body): array
    {
        preg_match_all(self::PATTERN, $body, $matches);

        return array_map(fn ($key) => mb_strtolower($key), $matches[1]);
    }

    /**
     * I motivi per cui Meta rifiuterebbe questo testo. Vuoto = si può inviare.
     *
     * @return string[]
     */
    public static function validate(string $body, array $buttons): array
    {
        $errors = [];
        $body = self::normalize($body);
        $params = self::params($body);

        if ($body === '') {
            $errors[] = 'Il testo è vuoto.';
        }

        if (mb_strlen($body) > self::MAX_BODY) {
            $errors[] = 'Il testo è troppo lungo: al massimo ' . self::MAX_BODY . ' caratteri.';
        }

        if (str_contains($body, '{{') || str_contains($body, '}}')) {
            $errors[] = 'Usa le variabili con una graffa sola, per esempio {nome}.';
        }

        $unknown = array_values(array_diff(array_unique($params), self::VARIABLES));

        if ($unknown !== []) {
            $errors[] = 'Variabili sconosciute: ' . implode(', ', array_map(fn ($k) => '{' . $k . '}', $unknown))
                . '. Puoi usare: ' . implode(' ', array_map(fn ($k) => '{' . $k . '}', self::VARIABLES)) . '.';
        }

        $repeated = array_keys(array_filter(array_count_values($params), fn ($count) => $count > 1));

        if ($repeated !== []) {
            $errors[] = 'Ogni variabile si può usare una volta sola: ' . implode(', ', array_map(fn ($k) => '{' . $k . '}', $repeated)) . '.';
        }

        $missing = array_values(array_diff(self::REQUIRED, $params));

        if ($missing !== []) {
            $errors[] = 'Un promemoria deve dire quando: mancano ' . implode(' e ', array_map(fn ($k) => '{' . $k . '}', $missing)) . '.';
        }

        if ($body !== '' && preg_match('/^\{[\pL_]+\}/u', $body)) {
            $errors[] = 'Il testo non può iniziare con una variabile: scrivi prima qualcosa, per esempio "Ciao {nome}".';
        }

        if ($body !== '' && preg_match('/\{[\pL_]+\}$/u', $body)) {
            $errors[] = 'Il testo non può finire con una variabile: aggiungi qualcosa dopo, anche solo un punto.';
        }

        if (preg_match('/\{[\pL_]+\}\s*\{[\pL_]+\}/u', $body)) {
            $errors[] = 'Due variabili non possono stare attaccate: mettici in mezzo almeno una parola o un segno, per esempio "{data} alle {ora}".';
        }

        $buttons = array_values($buttons);

        if (count($buttons) !== 2) {
            $errors[] = 'Servono due pulsanti: uno per confermare e uno per disdire.';
        } else {
            foreach ($buttons as $i => $label) {
                $label = trim((string) $label);
                $which = $i === 0 ? 'di conferma' : 'di disdetta';

                if ($label === '') {
                    $errors[] = "Il pulsante {$which} è vuoto.";
                } elseif (mb_strlen($label) > self::MAX_BUTTON) {
                    $errors[] = "Il pulsante {$which} è troppo lungo: al massimo " . self::MAX_BUTTON . ' caratteri.';
                } elseif (preg_match('/[\r\n{}]/u', $label)) {
                    $errors[] = "Il pulsante {$which} non può contenere a capo o variabili.";
                }
            }

            if (mb_strtolower(trim((string) $buttons[0])) === mb_strtolower(trim((string) $buttons[1]))) {
                $errors[] = 'I due pulsanti devono avere testi diversi.';
            }
        }

        return $errors;
    }

    /** Il corpo nel formato di Meta: {{1}}, {{2}}… in ordine di apparizione. */
    public static function toMeta(string $body): string
    {
        $i = 0;

        return preg_replace_callback(self::PATTERN, function () use (&$i) {
            $i++;

            return '{{' . $i . '}}';
        }, self::normalize($body));
    }

    /** Il contrario, per i modelli già su Meta: {{n}} → {chiave} secondo params. */
    public static function fromMeta(string $metaBody, array $params): string
    {
        return preg_replace_callback('/\{\{(\d+)\}\}/', fn ($m) => '{' . ($params[(int) $m[1] - 1] ?? 'var' . $m[1]) . '}', $metaBody);
    }

    /** La richiesta di creazione per POST /{waba}/message_templates. */
    public static function definition(string $name, string $language, string $body, array $buttons): array
    {
        $body = self::normalize($body);
        $examples = self::EXAMPLES[$language] ?? self::EXAMPLES['en'];
        $bodyComponent = ['type' => 'BODY', 'text' => self::toMeta($body)];
        $params = self::params($body);

        if ($params !== []) {
            $bodyComponent['example'] = ['body_text' => [array_map(fn ($key) => $examples[$key] ?? $key, $params)]];
        }

        return [
            'name' => $name,
            'language' => $language,
            'category' => 'UTILITY',
            'components' => [
                $bodyComponent,
                [
                    'type' => 'BUTTONS',
                    'buttons' => array_map(fn ($text) => ['type' => 'QUICK_REPLY', 'text' => trim((string) $text)], array_values($buttons)),
                ],
            ],
        ];
    }
}

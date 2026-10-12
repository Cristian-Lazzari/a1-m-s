<?php

namespace App\Http\Controllers\Whatsapp;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\WaAccount;
use App\Services\Tenants\DeliveryService;
use App\Services\Whatsapp\MetaGraph;
use App\Services\Whatsapp\OnboardingLinks;
use App\Services\Whatsapp\TemplateManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Collegamento del numero WhatsApp di un ristorante (Embedded Signup).
 *
 * Il ristoratore arriva qui con un link monouso chiesto dal suo gestionale
 * (POST /api/v1/onboarding-links). Il popup di Meta gira solo su questo
 * dominio: nell'app Meta basta un dominio consentito per tutti i ristoranti.
 * Il token del numero resta qui: al gestionale si dice solo che è collegato.
 */
class OnboardingController extends Controller
{
    private const SESSION_KEY = 'wa_onboarding';

    public function show(Request $request, OnboardingLinks $links): View
    {
        $token = (string) $request->query('token');
        $link = $token !== '' ? $links->consume($token) : null;

        if ($link) {
            $request->session()->put(self::SESSION_KEY, $link);
        } else {
            // Ricaricare la pagina non deve buttare fuori: il link è già stato usato
            // da questa stessa sessione.
            $link = $request->session()->get(self::SESSION_KEY);
        }

        $tenant = $link ? Tenant::where('active', true)->find($link['tenant_id']) : null;

        abort_unless($tenant, 403, 'Link di collegamento non valido o scaduto: riaprilo dal gestionale.');

        return view('whatsapp.connect', [
            'tenant' => $tenant,
            'account' => $tenant->accounts()->latest('id')->first(),
            'appId' => config('whatsapp.app_id'),
            'configId' => config('whatsapp.embedded_signup_config_id'),
            'graphVersion' => config('whatsapp.graph_version'),
            'returnUrl' => $link['return'] ?? null,
        ]);
    }

    public function complete(Request $request, MetaGraph $meta, DeliveryService $deliveries, TemplateManager $templates): JsonResponse
    {
        $link = $request->session()->get(self::SESSION_KEY);
        $tenant = $link ? Tenant::where('active', true)->find($link['tenant_id']) : null;

        if (! $tenant) {
            return response()->json(['ok' => false, 'error' => 'Sessione scaduta: riapri il collegamento dal gestionale.'], 419);
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'max:2048'],
            'waba_id' => ['required', 'string', 'regex:/^\d{1,32}$/'],
            'phone_number_id' => ['required', 'string', 'regex:/^\d{1,32}$/'],
            'business_id' => ['nullable', 'string', 'regex:/^\d{1,32}$/'],
        ]);

        $account = WaAccount::where('phone_number_id', $data['phone_number_id'])->first();

        // Un numero già collegato a un altro ristorante non si ruba.
        if ($account && $account->tenant_id !== $tenant->id) {
            return response()->json(['ok' => false, 'error' => 'Questo numero è già collegato a un altro locale.'], 409);
        }

        try {
            $token = $meta->exchangeCode($data['code']);

            $account = WaAccount::updateOrCreate(
                ['phone_number_id' => $data['phone_number_id']],
                [
                    'tenant_id' => $tenant->id,
                    'waba_id' => $data['waba_id'],
                    'business_id' => $data['business_id'] ?? null,
                    'token' => $token,
                    'status' => WaAccount::STATUS_PENDING,
                    'last_error' => null,
                ],
            );

            $meta->subscribeApp($account->waba_id, $token);

            // Il PIN è la verifica in due passaggi del numero: si genera una
            // volta e si conserva, serve di nuovo se il numero va ri-registrato.
            $pin = $account->pin ?: str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $account->update(['pin' => $pin]);
            $meta->registerPhone($account->phone_number_id, $pin, $token);

            $account->update($meta->phoneInfo($account->phone_number_id, $token));

            $templates->createDefaults($account);

            $account->update(['status' => WaAccount::STATUS_ACTIVE]);
        } catch (\Throwable $e) {
            $account?->update(['status' => WaAccount::STATUS_ERROR, 'last_error' => $e->getMessage()]);

            Log::error('(WA Onboarding) Collegamento non completato', [
                'tenant_id' => $tenant->id,
                'phone_number_id' => $data['phone_number_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        // Il gestionale lo saprebbe comunque chiedendo GET /api/v1/account:
        // l'avviso serve solo ad aggiornare subito la sua pagina.
        $deliveries->deliver($tenant, 'account', [
            'connected' => true,
            'display_phone' => $account->display_phone,
            'verified_name' => $account->verified_name,
        ]);
        $templates->notify($account);

        return response()->json([
            'ok' => true,
            'display_phone' => $account->display_phone,
            'verified_name' => $account->verified_name,
            'return' => $link['return'] ?? null,
        ]);
    }
}

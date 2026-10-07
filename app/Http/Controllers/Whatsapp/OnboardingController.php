<?php

namespace App\Http\Controllers\Whatsapp;

use App\Http\Controllers\Controller;
use App\Models\Source;
use App\Models\WaAccount;
use App\Services\Whatsapp\MetaGraph;
use App\Services\Whatsapp\ReminderTemplate;
use App\Services\Whatsapp\TenantApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Collegamento del numero WhatsApp di un ristorante (Embedded Signup).
 *
 * Il ristoratore arriva qui dal suo gestionale con un link firmato col suo
 * PRIVATE_REPORT_TOKEN. Il popup di Meta gira solo su questo dominio, così
 * nell'app Meta basta un "Allowed domain" per tutti i ristoranti.
 */
class OnboardingController extends Controller
{
    private const SESSION_KEY = 'wa_onboarding';

    public function show(Request $request): View
    {
        $source = Source::where('db_name', (string) $request->query('db'))->first();
        $expires = (int) $request->query('expires');
        $signature = (string) $request->query('signature');

        $valid = $source
            && filled($source->private_token)
            && $expires >= now()->getTimestamp()
            && hash_equals(hash_hmac('sha256', $source->db_name . '|' . $expires, $source->private_token), $signature);

        abort_unless($valid, 403, 'Link di collegamento non valido o scaduto: riaprilo dal gestionale.');

        $request->session()->put(self::SESSION_KEY, [
            'source_id' => $source->id,
            'return' => $this->safeReturnUrl((string) $request->query('return'), $source),
        ]);

        return view('whatsapp.connect', [
            'source' => $source,
            'account' => WaAccount::where('source_id', $source->id)->latest('id')->first(),
            'appId' => config('whatsapp.app_id'),
            'configId' => config('whatsapp.embedded_signup_config_id'),
            'graphVersion' => config('whatsapp.graph_version'),
            'returnUrl' => $request->session()->get(self::SESSION_KEY . '.return'),
        ]);
    }

    public function complete(Request $request, MetaGraph $meta, TenantApi $tenants): JsonResponse
    {
        $session = $request->session()->get(self::SESSION_KEY);
        $source = $session ? Source::find($session['source_id']) : null;

        if (! $source) {
            return response()->json(['ok' => false, 'error' => 'Sessione scaduta: riapri il collegamento dal gestionale.'], 419);
        }

        $data = $request->validate([
            'code' => ['required', 'string'],
            'waba_id' => ['required', 'string', 'max:64'],
            'phone_number_id' => ['required', 'string', 'max:64'],
            'business_id' => ['nullable', 'string', 'max:64'],
        ]);

        $account = null;

        try {
            $token = $meta->exchangeCode($data['code']);

            $account = WaAccount::updateOrCreate(
                ['phone_number_id' => $data['phone_number_id']],
                [
                    'source_id' => $source->id,
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

            foreach (ReminderTemplate::definitions() as $template) {
                $meta->createTemplate($account->waba_id, $template, $token);
            }

            $response = $tenants->post($source, 'account', [
                'waba_id' => $account->waba_id,
                'phone_number_id' => $account->phone_number_id,
                'display_phone' => $account->display_phone,
                'token' => $token,
            ]);

            if (! $response->successful()) {
                throw new \RuntimeException('Il gestionale del ristorante non ha salvato il numero (HTTP ' . $response->status() . ').');
            }

            $account->update(['status' => WaAccount::STATUS_ACTIVE]);
        } catch (\Throwable $e) {
            $account?->update(['status' => WaAccount::STATUS_ERROR, 'last_error' => $e->getMessage()]);

            Log::error('(WA Onboarding) Collegamento non completato', [
                'source_id' => $source->id,
                'phone_number_id' => $data['phone_number_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok' => true,
            'display_phone' => $account->display_phone,
            'verified_name' => $account->verified_name,
            'return' => $session['return'] ?? null,
        ]);
    }

    /** Si torna solo al gestionale di quel ristorante, mai a un indirizzo qualsiasi. */
    private function safeReturnUrl(string $url, Source $source): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);
        $expected = parse_url((string) $source->app_url, PHP_URL_HOST);

        return $host && $expected && strcasecmp($host, $expected) === 0 && str_starts_with($url, 'https://')
            ? $url
            : null;
    }
}

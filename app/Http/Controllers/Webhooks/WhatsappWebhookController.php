<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Tenants\DeliveryService;
use App\Services\WaFailureAlertService;
use App\Services\Whatsapp\WebhookProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    /** Verifica dell'URL quando lo si imposta nell'app Meta. */
    public function verify(Request $request)
    {
        $expected = (string) config('whatsapp.webhook_verify_token');

        // PHP trasforma "hub.verify_token" in "hub_verify_token".
        if ($expected !== ''
            && $request->query('hub_mode') === 'subscribe'
            && hash_equals($expected, (string) $request->query('hub_verify_token'))) {
            return response((string) $request->query('hub_challenge'), 200);
        }

        return response('Forbidden', 403);
    }

    public function handle(Request $request, WebhookProcessor $processor, DeliveryService $deliveries)
    {
        try {
            $ids = $processor->process((array) $request->json()->all());
        } catch (\Throwable $e) {
            // A Meta si risponde comunque 200: un errore nostro non deve
            // fargli ripetere all'infinito lo stesso webhook.
            Log::error('(WA Webhook) Elaborazione fallita: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            app(WaFailureAlertService::class)->notify('wa_webhook', [
                'resource' => ['entries' => count((array) $request->json('entry', []))],
            ], $e);

            return response('EVENT_RECEIVED', 200);
        }

        // Le consegne ai ristoranti partono dopo aver risposto a Meta; quelle
        // che falliscono le riprende wa:retry-deliveries.
        if ($ids !== []) {
            app()->terminating(fn () => $deliveries->attemptMany($ids));
        }

        return response('EVENT_RECEIVED', 200);
    }
}

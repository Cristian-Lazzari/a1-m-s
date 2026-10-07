<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Accetta solo webhook firmati da Meta.
 *
 * Meta firma il corpo della richiesta con l'app secret (HMAC SHA-256) e manda
 * la firma in X-Hub-Signature-256. Senza questo controllo chiunque conosca
 * l'URL potrebbe fingersi Meta e, per esempio, annullare prenotazioni.
 */
class VerifyMetaSignature
{
    public function handle(Request $request, Closure $next)
    {
        $secret = (string) config('whatsapp.app_secret');

        if ($secret === '') {
            // Meglio rifiutare tutto che accettare tutto.
            Log::critical('(WA Webhook) META_APP_SECRET non impostato: webhook rifiutato.');

            return response('Not configured', 503);
        }

        $header = (string) $request->header('X-Hub-Signature-256', '');
        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

        if ($header === '' || ! hash_equals($expected, $header)) {
            Log::warning('(WA Webhook) Firma non valida, richiesta scartata', ['ip' => $request->ip()]);

            return response('Invalid signature', 403);
        }

        return $next($request);
    }
}

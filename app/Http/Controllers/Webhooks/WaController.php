<?php

namespace App\Http\Controllers\Webhooks;

use Carbon\Carbon;
use Stripe\Refund;
use Stripe\Stripe;
use App\Models\Date;
use App\Models\Order;
use App\Models\Source;
use App\Models\Message;
use App\Models\Setting;
use App\Models\Reservation;
use App\Models\OrderProduct;
use Illuminate\Http\Request;
use App\Mail\confermaOrdineAdmin;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class WaController extends Controller
{
    // Metodo per gestire la verifica del webhook
    public function verify(Request $request)
    {
        //$verifyToken = config('configurazione.WA_TO');
        $verifyToken = 'diocane';

        if ($request->query('hub_verify_token') === $verifyToken) {
            return response($request->query('hub_challenge'), 200);
        }

        return response('Token di verifica non valido', 403);
    }

    // Metodo per gestire i webhook
    public function handle(Request $request)
    {
        $data = $request->all();
         Log::info("Webhook ricevuto", $data);
    
        if (isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
            $message = $data['entry'][0]['changes'][0]['value']['messages'][0] ?? null;
            $number = $message['from'] ?? 'non hai trovato il numero';
            $messageId = '';
            $buttonId = '';
            Log::info("messaggio:" , $message);
            if(isset($message['interactive'])){
                $messageId = $data['entry'][0]['changes'][0]['value']['messages'][0]['context']['id'] ?? null;
                $buttonId = $message['interactive']['button_reply']['id']; 
                Log::info("Pulsante premuto(interactive): $buttonId, ID messaggio: $messageId");   
            }else {
                $messageId = $message['context']['id'] ?? null;    
                $buttonId = $message['button']['text']; 
                Log::info("Pulsante premuto(template): $buttonId, ID messaggio: $messageId");
            }

            $message = Message::where('wa_id' , $messageId)->firstOrFail();
            if (!$message) {
                Log::info("Nessun  Message : " . $messageId);
                return;
            }
            $domain = Source::where('id', $message->source)->firstOrFail();
            $correct_domain = stripslashes($domain->domain);
            // URL del sito ricevente
            $url = $correct_domain . '/webhook/wa' ;
            
            // Dati da inviare
            $data = [
                'wa_id' => $messageId,
                'number' => $number,
                'response' => $buttonId == 'Conferma' ? 1 : 0,
            ];
    
            // Invio della richiesta POST
            $response = Http::post($url, $data);
    
            // Gestione della risposta
            if ($response->successful()) {
                return response()->json([
                    'status' => 'success',
                    'data' => $response->json(),
                ]);
            }

        } else {
            Log::info("Struttura del messaggio non valida o messaggio mancante.");
        }

        return response()->json(['status' => 'success']);
    }




}

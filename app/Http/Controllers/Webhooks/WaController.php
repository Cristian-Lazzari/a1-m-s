<?php

namespace App\Http\Controllers\Webhooks;

use Swift_Mailer;
use Carbon\Carbon;
use Stripe\Refund;
use Stripe\Stripe;
use App\Models\Order;
use App\Models\Source;
use App\Models\Message;
use App\Models\Setting;
use Swift_SmtpTransport;
use App\Models\Reservation;
use App\Models\OrderProduct;
use Illuminate\Http\Request;
use App\Mail\confermaOrdineAdmin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

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

        $incomingMessage = data_get($data, 'entry.0.changes.0.value.messages.0');

        if (!$incomingMessage) {
            Log::info("Struttura del messaggio non valida o messaggio mancante.");
            return response('EVENT_RECEIVED', 200);
        }

        Log::info("Messaggio webhook normalizzato:", $incomingMessage);

        $messageId = data_get($incomingMessage, 'context.id');
        if (!$messageId) {
            Log::warning("(WC) Risposta WhatsApp senza context.id", [
                'incoming_type' => $incomingMessage['type'] ?? null,
                'message' => $incomingMessage,
            ]);

            return response('EVENT_RECEIVED', 200);
        }

        $storedMessage = Message::where('wa_id', $messageId)->first();
        if (!$storedMessage) {
            Log::warning("(WC) Nessun Message trovato per il context.id ricevuto", [
                'wa_id' => $messageId,
            ]);

            return response('EVENT_RECEIVED', 200);
        }

        $source = Source::find($storedMessage->source);
        if (!$source) {
            Log::warning("(WC) Nessuna Source trovata per il messaggio ricevuto", [
                'wa_id' => $messageId,
                'source_id' => $storedMessage->source,
            ]);

            return response('EVENT_RECEIVED', 200);
        }

        $reply = $this->extractWhatsappReplyAction($incomingMessage, $storedMessage);
        if (!$reply) {
            Log::warning("(WC) Impossibile determinare il pulsante premuto", [
                'wa_id' => $messageId,
                'stored_type' => $storedMessage->type,
                'incoming_type' => $incomingMessage['type'] ?? null,
                'message' => $incomingMessage,
            ]);

            return response('EVENT_RECEIVED', 200);
        }

        $responseValue = $this->mapWhatsappReplyToResponse($reply['action']);
        if ($responseValue === null) {
            Log::warning("(WC) Azione WhatsApp non riconosciuta, nessun aggiornamento eseguito", [
                'wa_id' => $messageId,
                'action' => $reply['action'],
                'reply_type' => $reply['type'],
            ]);

            return response('EVENT_RECEIVED', 200);
        }

        Log::info("(WC) Pulsante premuto", [
            'wa_id' => $messageId,
            'stored_type' => $storedMessage->type,
            'reply_type' => $reply['type'],
            'action' => $reply['action'],
        ]);

        $payload = [
            'wa_id' => $messageId,
            'number' => $incomingMessage['from'] ?? 'non hai trovato il numero',
            'response' => $responseValue,
        ];

        $this->handle_p2($payload, $source);
        DB::disconnect('mysql');
        Config::set("database.connections.mysql", config('database.connections.mysql'));

        return response('EVENT_RECEIVED', 200);
    }

    protected function extractWhatsappReplyAction(array $incomingMessage, Message $storedMessage): ?array
    {
        $preferredType = $this->normalizeStoredWhatsappMessageType($storedMessage->type ?? null);
        $parsers = $preferredType === 'template'
            ? ['template', 'interactive']
            : ['interactive', 'template'];

        foreach ($parsers as $parser) {
            $reply = $parser === 'interactive'
                ? $this->extractInteractiveReplyAction($incomingMessage)
                : $this->extractTemplateReplyAction($incomingMessage);

            if ($reply !== null) {
                return [
                    'type' => $parser,
                    'action' => $reply,
                ];
            }
        }

        return null;
    }

    protected function extractInteractiveReplyAction(array $incomingMessage): ?string
    {
        return data_get($incomingMessage, 'interactive.button_reply.id')
            ?? data_get($incomingMessage, 'interactive.button_reply.title')
            ?? data_get($incomingMessage, 'interactive.list_reply.id')
            ?? data_get($incomingMessage, 'interactive.list_reply.title');
    }

    protected function extractTemplateReplyAction(array $incomingMessage): ?string
    {
        return data_get($incomingMessage, 'button.payload')
            ?? data_get($incomingMessage, 'button.text');
    }

    protected function normalizeStoredWhatsappMessageType($type): ?string
    {
        $normalizedType = is_string($type) ? strtolower(trim($type)) : (string) $type;

        return match ($normalizedType) {
            '0', 'interactive' => 'interactive',
            '1', 'template', 'button' => 'template',
            default => null,
        };
    }

    protected function mapWhatsappReplyToResponse(?string $action): ?int
    {
        if (!is_string($action) || trim($action) === '') {
            return null;
        }

        $normalized = Str::of($action)->squish()->lower()->toString();

        if (str_contains($normalized, 'conferm') || str_contains($normalized, 'accept')) {
            return 1;
        }

        if (str_contains($normalized, 'annull') || str_contains($normalized, 'cancel')) {
            return 0;
        }

        return null;
    }
    // Metodo per gestire i webhook
    protected function handle_p2($data, $source)
    {   

        Config::set("database.connections.dynamic", [
            'driver'    => 'mysql',
            'host'      => '127.0.0.1',
            'port'      => '3306',
            'database'  => $source->db_name,
            'username'  => 'dciludls_ceo',
            'password'  => 'sepT2921!',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]);
        // resettiamo la connessione per far usare i nuovi parametri
        DB::purge('dynamic');
        DB::reconnect('dynamic');

    


        $number = $data['number'];


        $setting = Setting::on('dynamic')->where('name', 'wa')->first();

        $property = json_decode($setting->property, true);
        $numbers = $property['numbers'];
        $co_work = false;
        if(count($numbers) == 2){
            $co_work = true;
            $p = array_search($number, $numbers);
            $this->updateLastResponseWa($p);
            Log::info("Co1 ==> {$p}");
            $p = $p == 0 ? 1 : 0;
            $this->updateLastResponseWa($p);
            Log::info("Co2 ==> {$p}");
            $number_correct = $numbers[$p];
        }else{
            Log::info("single ==> lol");
            $this->updateLastResponseWa(0);
        }

        $messageId = $data['wa_id'];
        $button_r = $data['response'];

        $order       = Order::on('dynamic')->where('whatsapp_message_id', 'like', '%' . $messageId . '%')->first();
        $reservation = Reservation::on('dynamic')->where('whatsapp_message_id', 'like', '%' . $messageId . '%')->first();
        if ($order) {
            Log::info("(WC)L' ordine: " . json_encode($order));
            $status = $order->status;
            $this->statusOrder($button_r, $order, $source);
            if($button_r == 1 && in_array($status, [1, 5])){
                return;
            }elseif($button_r == 0 && in_array($status, [0, 6])){
                return;
            }elseif(in_array($status, [1, 5, 0, 6])){
                return;
            }
            if ($co_work) {
                $this->message_co_worker(1, $button_r, $p, $order, $number_correct);
            }
        } else
        if ($reservation) {
            if ($reservation) {
                $status = $reservation->status;
                $this->statusRes($button_r, $reservation, $source);
                if($button_r == 1 && in_array($status, [1, 5])){
                    return;
                }elseif($button_r == 0 && in_array($status, [0, 6])){
                    return;
                }elseif(in_array($status, [1, 5, 0, 6])){
                    return;
                }
                if ($co_work) {
                    $this->message_co_worker(false, $button_r, $p, $reservation, $number_correct);
                }
            }
        } else {
            // Nessun ordine o prenotazione trovato per il Message ID
            Log::info("(WC) Nessun ordine o prenotazione trovati per il Message ID: " . $messageId);
        }
        return;
    }

    protected function message_co_worker($o_r, $c_a, $p, $or_res, $number){
        try {
 
            // Definizione dei messaggi in base allo stato
            $m = $o_r ? 'L\'ordine è stato ' : 'La prenotazione è stata ';
            $sub = $o_r ? 'L\'ordine è stato' : 'La prenotazione è stata';
    
            if ($o_r) {
                $link_id = config('configurazione.APP_URL') . '/admin/orders/' . $or_res->id;
            }else{
                $link_id = config('configurazione.APP_URL') . '/admin/reservations/' . $or_res->id;
            }
            if ($c_a) {
                $m .= '*confermat' . ($o_r ? 'o* ✅' : 'a* ✅');
                $word = 'confermat' . ($o_r ? 'o ✅' : 'a ✅');
            } else {
                $m .= '*annullat' . ($o_r ? 'o* ❌' : 'a* ❌');
                $word = 'annullat' . ($o_r ? 'o ❌' : 'a ❌');
            }
    
            $m .= ' dal *tuo collega*';

            $messages = json_decode($or_res->whatsapp_message_id, true);
            $old_id = $messages[$p];

            Log::info("(WC) Esecuzione message_co_worker", [
                'o_r' => $o_r,
                'c_a' => $c_a,
                'p' => $p,
                'old_id' => $old_id,
                'number' => $number
            ]);
            $type_m_1 = 0;
            // Controllo se la risposta è entro 24 ore
            if ($this->isLastResponseWaWithin24Hours($p)) {    
                $data = [
                    'messaging_product' => 'whatsapp',
                    'to' => $number,
                    'recipient_type' => 'individual',
                    'type' => 'text',
                    'context' => [
                        'message_id' => $old_id
                    ],
                    'text' => [
                        'body' => $m
                    ]
                ];
            } else {
                $type_m_1 = 1;
                $data = [
                    'messaging_product' => 'whatsapp',
                    'to' => $number,
                    'recipient_type' => 'individual',
                    'type' => 'template',
                    'template' => [
                        'name' => 'response_link',
                        'language' => [
                            'code' => 'it'
                        ],
                        'components' => [
                            [
                                'type' => 'body',
                                'parameters' => [
                                    [
                                        'type' => 'text',
                                        'text' => $sub
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => $word
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => 'tuo collega'
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => $or_res->name . ' ' . $or_res->surname,
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => $or_res->date_slot,
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => $link_id,
                                    ],
                                ]
                            ]
                        ]
                    ],
                    'context' => [
                        "message_id" => $old_id
                    ],
                ];
            }
    
            $url = 'https://graph.facebook.com/v24.0/' . config('configurazione.WA_ID') . '/messages';
            
            $response = Http::withHeaders([
                'Authorization' => config('configurazione.WA_TO'),
                'Content-Type' => 'application/json'
            ])->post($url, $data);

            // Log della risposta ricevuta
            Log::info("(WC) Risposta da WhatsApp:", ['response' => $response->json()]);
    
            return $response->json();
        } catch (Exception $e) {
            Log::error("(WC) Errore in message_co_worker", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
    
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    protected function statusOrder($c_a, $order, $source){
        Log::info("(WC) Inizio statusOrder");
         
        if($c_a == 1 && in_array($order->status, [1, 5])){
            return;
        }elseif($c_a == 0 && in_array($order->status, [0, 6])){
            return;
        }elseif(in_array($order->status, [1, 5, 0, 6])){
            return;
        }
        if($c_a == 1){
            if($order->status == 2){
                $order->status = 1;
            }elseif($order->status == 3){
                $order->status = 5;
            }
            $m = 'L\'ordine è stata confermato correttamente';
            $message = 'Grazie ' . $order->name . ' per aver ordinato da noi, ti confermiamo che il tuo ordine sarà pronto per il ' . $order->date_slot;    
        }else{
            if(in_array($order->status, [3, 5])){
                $m = 'L\'ordine è stato annullato e RIMBORSATO correttamente';
                $message = 'Ci dispiace informarti che purtroppo il tuo ordine è stato annullato e rimborsato';
                //codice per rimborso
                try {
                    $stripeSecretKey = config('configurazione.STRIPE_SECRET'); 
                 
                    // Imposta la chiave segreta di Stripe
                    Stripe::setApiKey($stripeSecretKey);
        
                    if ($order->checkout_session_id === null) {
                        return response()->json(['error' => 'Payment not found'], 404);
                    }
                    // Effettua il rimborso
                    $refund = Refund::create([
                        'payment_intent' => $order->checkout_session_id, // Questo è l'ID dell'intent di pagamento
                    ]);
                    $order->status = 6;
                } catch (\Exception $e) {
                    return response()->json(['error' => $e->getMessage()], 500);
                }
                
            }elseif(in_array($order->status, [2, 1])){
                $m = 'L\'ordine è stato annullato correttamente';
                $message = 'Ci dispiace informarti che purtroppo il tuo ordine è stato annullato';
                $order->status = 0;
            }else{
                $m = 'L\'ordine era già stato annullato!';
                return; 
            }
        }
        $order->update();

        //new menu
        $product_r = [];
        foreach ($order->products as $p) {
            $arrO = $p->pivot->option !== '[]' ? json_decode($p->pivot->option, true) : [];
            $arrA = $p->pivot->add !== '[]' ? json_decode($p->pivot->add, true) : [];
            $r_option = [];
            $r_add = [];
            foreach ($arrO as $o) {
                $ingredient = Ingredient::on('dynamic')->where('name', $o)->first();
                $r_option[] = $ingredient;
            }
            foreach ($arrA as $o) {
                $ingredient = Ingredient::on('dynamic')->where('name', $o)->first();
                $r_add[] = $ingredient;
            }
            $p->setAttribute('r_option', $r_option);
            $p->setAttribute('r_add', $r_add);
            $product_r[] = $p;
        }
        $cart_mail = [
            'products' => $product_r,
            'menus' => $order->menus,
        ];
        $set = Setting::on('dynamic')->where('name', 'Contatti')->first();
        $p_set = json_decode($set->property, true);
        $bodymail = [
            'type' => 'or',
            'to' => 'user',
            
            'title' =>  $c_a ? 'Ti confermiamo che il tuo ordine è stato accettato' : 'Ci dispiace informarti che il tuo ordine è stato annullato',
            'subtitle' => $order->status == 6 ? 'Il tuo rimborso verrà elaborato in 5-10 gironi lavorativi' : '',
            'whatsapp_message_id' => $order->whatsapp_message_id,

            'order_id' => $order->id,
            'name' => $order->name,
            'surname' => $order->surname,
            'email' => $order->email,
            'date_slot' => $order->date_slot,
            'message' => $order->message,
            'phone' => $order->phone,
            'admin_phone' => $p_set['telefono'],
            
            'comune' => $order->comune,
            'address' => $order->address,
            'address_n' => $order->address_n,

            'app_domain' => $source->app_domain,
            'app_url' => $source->app_url,
            'app_name' => $source->app_name,
            
            'status' => $order->status,
            'cart' => $cart_mail,
            'total_price' => $order->tot_price,
        ];
       
         // Crea un transport dinamico
        config()->set([
            "mail.mailers.smtp.host"       => $source->host,
            "mail.mailers.smtp.port"       => 465,
            "mail.mailers.smtp.username"   => $source->username,
            "mail.mailers.smtp.password"   => $source->token,
            "mail.mailers.smtp.encryption" => 'ssl',
            "mail.mailers.smtp.from.address"=> $source->username,
            "mail.mailers.smtp.from.name"   => $source->app_name,
        ]);

        // // Invio
        Mail::mailer('smtp')->to($order->email)->send((new confermaOrdineAdmin($bodymail, $source->from_address, $source->from_name)));

        return $m;
    }
    protected function statusRes($c_a, $res, $source){
        Log::info("(WC) Inizio statusRes");
        if($c_a == 1 && in_array($res->status, [1, 5])){
            return;
        }elseif($c_a == 0 && in_array($res->status, [0, 6])){
            return;
        }elseif(in_array($res->status, [1, 5, 0, 6])){
            return;
        }
        $adv_s = Setting::on('dynamic')->where('name', 'advanced')->first();
        $property_adv = json_decode($adv_s->property, 1);
        if($c_a == 1){
            $res->status = 1;
            $m = 'La prenotazione e\' stata confermata correttamente';
            $message = 'Siamo felici di informarti che la tua prenotazione e\' stata confermata, ti ricordo la data e l\'orario che hai scelto: ' . $res->date_slot ;
        }else{
            if($res->status == 0){
                $m = 'La prenotazione e\' stata gia annullata correttamente';
                return;
            }
            $res->status = 0;
            $m = 'La prenotazione e\' stata annullata correttamente';
            $message = 'Ci spiace informarti che la tua prenotazione e\' stata annullata per la data e l\'orario che hai scelto... ' . $res->date_slot ;
        }
        $res->update();
        
        $set = DB::connection('dynamic')
            ->table('settings')
            ->where('name', 'Contatti')
            ->first();
        $p_set = json_decode($set->property, true);
        $bodymail = [
            'type' => 'res',
            'to' => 'user',

            'title' =>  $c_a ? 'Ti confermiamo che la tua prenotazione è stata accettata' : 'Ci dispiace informarti che la tua prenotazione è stata annullata',
            'subtitle' => '',

            'res_id' => $res->id,
            'name' => $res->name,
            'surname' => $res->surname,
            'email' => $res->email,
            'date_slot' => $res->date_slot,
            'message' => $res->message,
            'sala' => $res->sala,
            'phone' => $res->phone,
            'admin_phone' => $p_set['telefono'],

            'app_domain' => $source->app_domain,
            'app_url' => $source->app_url,
            'app_name' => $source->app_name,
            
            'whatsapp_message_id' => $res->whatsapp_message_id,
            'n_person' => $res->n_person,
            'status' => $res->status,
            
            'property_adv' => $property_adv,
        ];
        try {
            // Config dinamica SMTP
            config()->set([
                "mail.mailers.smtp.host"       => $source->host,
                "mail.mailers.smtp.port"       => 465,
                "mail.mailers.smtp.username"   => $source->username,
                "mail.mailers.smtp.password"   => $source->token,
                "mail.mailers.smtp.encryption" => 'ssl',
                "mail.mailers.smtp.from.address"=> $source->username,
                "mail.mailers.smtp.from.name"   => $source->app_name,
            ]);

            // Invio mail
            Mail::mailer('smtp')->to($res->email)
                ->send(new confermaOrdineAdmin($bodymail, $source->from_address, $source->from_name));

            Log::info("Email inviata correttamente a {$res->email} da {$source->username}");

        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
            Log::error("Errore SMTP: ".$e->getMessage());
        } catch (\Exception $e) {
            Log::error("Errore generico invio mail: ".$e->getMessage());
        }

        return;   
    }

    protected function isLastResponseWaWithin24Hours($p)
    {
        $setting = Setting::on('dynamic')->where('name', 'wa')->first();
        if ($setting) {
            // Decodifica il campo 'property' da JSON ad array
            $property = json_decode($setting->property, true);
            if($p == 0){
                 // Controlla se 'last_response_wa' è impostato
                if (isset($property['last_response_wa_1']) && !empty($property['last_response_wa_1'])) {
                    // Confronta la data salvata con le ultime 24 ore
                    $lastResponseDate = Carbon::parse($property['last_response_wa_1']);
                    return $lastResponseDate->greaterThanOrEqualTo(Carbon::now()->subHours(24));
                }
            }else{
                 // Controlla se 'last_response_wa' è impostato
                if (isset($property['last_response_wa_2']) && !empty($property['last_response_wa_2'])) {
                    // Confronta la data salvata con le ultime 24 ore
                    $lastResponseDate = Carbon::parse($property['last_response_wa_2']);
                    return $lastResponseDate->greaterThanOrEqualTo(Carbon::now()->subHours(24));
                }
            }
        }else{
            return false; // Se il record non esiste o la data non è impostata
        }
    }
    protected function updateLastResponseWa($c)
    {
        $setting = Setting::on('dynamic')->where('name', 'wa')->first();
        $property = json_decode($setting->property, true);
        $now = Carbon::now();
        Log::info("update-Last-Response-Wa ==> {$c}");
        if($c == 0){
            $property['last_response_wa_1'] = $now;
        }else {
            $property['last_response_wa_2'] = $now;

        }
        $setting->property= json_encode($property);
        $setting->update();
        
    }

}

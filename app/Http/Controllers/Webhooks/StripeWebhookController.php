<?php

namespace App\Http\Controllers\Webhooks;

use Carbon\Carbon;
use Stripe\Stripe;
use Stripe\Webhook;
use App\Models\Date;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\Request;
use App\Mail\confermaOrdineAdmin;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class StripeWebhookController extends Controller
{   
    public function handleStripeWebhook(Request $request)
    {
        try {
        // La tua chiave segreta di Stripe
        $stripeSecretKey = config('configurazione.STRIPE_SECRET'); 
        
        Log::warning(" SESSIONE CONTROLLER");
        // Imposta la chiave segreta di Stripe
        Stripe::setApiKey($stripeSecretKey);

        // Ottieni il corpo della richiesta
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        // Il tuo endpoint segreto (ottenuto quando configuri il webhook)
        $endpointSecret = config('configurazione.STRIPE_WEBHOOK_SECRET'); // Inserisci il tuo endpoint segreto

        try {
            // Verifica il webhook
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (UnexpectedValueException $e) {
            // Il payload non è valido
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // La firma non è valida
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Gestisci gli eventi pertinenti
        if($event->type == 'checkout.session.completed'){
            $session = $event->data->object; // contiene i dettagli della sessione
            return $this->handleCheckoutSessionCompleted($session);
               
        }
        elseif($event->type == 'payment_intent.payment_failed'){
            $paymentIntent = $event->data->object; // contiene i dettagli del pagamento
            $this->handlePaymentIntentFailed($paymentIntent);
        }

        

        //return response()->json(['status' => 'success'], 200);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore del database: ' . $e->getMessage(),
            ]);
        } catch (Exception $e) {
            $trace = $e->getTrace();
            $errorInfo = [
                'success' => false,
                'error' => 'Si è verificato un errore durante l\'elaborazione della richiesta: ' . $e->getMessage(),

            ];

            return response()->json($errorInfo, 500);
        }
    }



    protected function handleCheckoutSessionCompleted($session)
    {
               

        // Aggiorna il tuo database per segnare l'ordine come completato
        $orderId = $session->metadata->order_id; // Assicurati di aver aggiunto l'ID dell'ordine nei metadata
        
        // Esegui la logica per aggiornare lo stato dell'ordine nel database
        $order = Order::where('id', $orderId)->with('products')->firstOrFail();

        $info =  $order->name . ' ' . $order->surname . ' ha ordinato e PAGATO per il ' . $order->date_slot . ': ';
        // Itera sui prodotti dell'ordine
        $lastProduct = end($newOrder->products);
        foreach ($newOrder->products as $product) {
            // Aggiungi il nome e la quantità del prodotto
            $info .= "{$product->name} ";
            if ($product->pivot->quantity !== 1) {
                $info .= "** {$product->pivot->quantity}*";
            }

            // Gestisci le opzioni del prodotto
            if ($product->pivot->option !== '[]') {
                $options = json_decode($product->pivot->option);
                $info .= "Opzioni: " . implode(', ', $options);
            }
            // Gestisci gli ingredienti aggiunti
            if ($product->pivot->add !== '[]') {
                $addedIngredients = json_decode($product->pivot->add);
                $info .= "Aggiunte: " . implode(', ', $addedIngredients);
            }
            // Gestisci gli ingredienti rimossi
            if ($product->pivot->remove !== '[]') {
                $removedIngredients = json_decode($product->pivot->remove);
                $info .= "Rimossi: " . implode(', ', $removedIngredients);
            }
            // Separatore tra i prodotti
            $info .= ($product === $lastProduct) ? ". " : ", ";
        }
        if($order->comune){
            $info .= "Consegna a domicilio: {$order->address}, {$order->address_n}, {$order->comune} ";
        }else{
            $info .= "Ritiro asporto";
        }
        
        // Definisci l'URL della richiesta
        $url = 'https://graph.facebook.com/v20.0/'. config('configurazione.WA_ID') . '/messages';
        $number = config('configurazione.WA_N');
        if ($this->isLastResponseWaWithin24Hours()) {
            $data = [
                'messaging_product' => 'whatsapp',
                'to' => $number,
                "type"=> "interactive",
                "interactive"=> [
                    "type"=> "button",
                    "header"=> [
                        "type" => "text",
                        "text"=>'Hai una nuova notifica!',
                    ],  
                    "footer"=> [
                        "text"=> "Powered by Future+"
                    ],
                    "body"=> [
                    "text"=> $info,
                    ],
                        "action"=> [
                        "buttons"=> [
                            [
                                "type"=> "reply",
                                "reply"=> [
                                    "id"=> "Conferma",
                                    "title"=> "Conferma"
                                ]
                            ],
                                [
                                "type"=> "reply",
                                "reply"=> [
                                    "id"=> "Annulla",
                                    "title"=> "Annulla"
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        }else{
            $data = [
                'messaging_product' => 'whatsapp',
                'to' => $number,
                'category' => 'marketing',
                'type' => 'template',
                'template' => [
                    'name' => 'or',
                    'language' => [
                        'code' => 'it'
                    ],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => $newOrder->comune ? 'Ordine a domicilio' : 'Ordine d\'asporto', 
                                ],
                                [
                                    'type' => 'text',
                                    'text' => $info  
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        }
        // Effettua la richiesta HTTP POST con le intestazioni necessarie
        $response = Http::withHeaders([
            'Authorization' => config('configurazione.WA_TO'),
            'Content-Type' => 'application/json'
        ])->post($url, $data);

        // Estrai l'ID del messaggio dalla risposta di WhatsApp
        $messageId = $response->json()['messages'][0]['id'] ?? null;
        if ($messageId) {
            // Salva il message_id nell'ordine
            $order->whatsapp_message_id = $messageId;
            $order->update();
        }
        //if($order){
        $date = Date::where('date_slot', $order->date_slot)->first();
                        
        $vis = json_decode($date->visible, true);
        $av = json_decode($date->availability, true);
        $res = json_decode($date->reserving, true);
    

        
        $order->checkout_session_id = $session->payment_intent;
        $order->status = 3;
        $order->update();
        $set = Setting::where('name', 'Contatti')->firstOrFail();
        $p_set = json_decode($set->property, true);
        
        $bodymail_a = [
            'type' => 'or',
            'to' => 'admin',

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
            
            'status' => $order->status,
            'cart' => $order->products,
            'total_price' => $order->tot_price,

            
        ];
        $bodymail_u = [
            'type' => 'or',
            'to' => 'user',

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
            
            'status' => $order->status,
            'cart' => $order->products,
            'total_price' => $order->tot_price,

            
        ];
        $mail = new confermaOrdineAdmin($bodymail_u);
        Mail::to($order->email)->send($mail);

        $mailAdmin = new confermaOrdineAdmin($bodymail_a);
        Mail::to(config('configurazione.mail'))->send($mailAdmin);

        // aggiorno la disponibilità in date
        if(config('configurazione.typeOfOrdering')){
            $res_c1 = $res['cucina_1'];
            $res_c2 = $res['cucina_2'];
            $av_c1  = $av['cucina_1'];
            $av_c2  = $av['cucina_2'];
            // Inizializza i contatori
            $np_c1  = 0;
            $np_c2  = 0;
            // Cicla sui prodotti associati all'ordine
            foreach ($order->products as $product) {
                // return $product;
                // Controlla il tipo di cucina del prodotto
                if ($product->type_plate == 1) {
                    $np_c1 += $product->pivot->quantity * $product->slot_plate;
                } elseif ($product->type_plate == 2) {
                    $np_c2 += $product->pivot->quantity * $product->slot_plate;
                }
            }
            
            if(isset($order->comune)){
                if( ($res['domicilio'] + 1) < $av['domicilio']){
                    $res['domicilio'] = $res['domicilio'] + 1;
                } else{
                    $res['domicilio'] = $res['domicilio'] + 1;
                    $vis['domicilio'] = 0;
                }
            }
            if((($res_c1 + $np_c1) < $av_c1) && (($res_c2 + $np_c2) < $av_c2)){}
            elseif((($res_c1 + $np_c1) == $av_c1) && (($res_c2 + $np_c2) < $av_c2)){
                $vis['cucina_1'] = 0;
            }elseif((($res_c2 + $np_c2) == $av_c2) && (($res_c1 + $np_c1) < $av_c1)){
                $vis['cucina_2'] = 0;
            }else{
                $vis['cucina_1'] = 0;
                $vis['cucina_2'] = 0;
            }
            $res['cucina_1'] += $np_c1;
            $res['cucina_2'] += $np_c2;
            
        }else{
            if(isset($order->comune)){
                if(($res['domicilio'] + 1) < $av['domicilio']){}
                else{
                    $vis['domicilio'] = 0;
                }
                $res['domicilio'] = $res['domicilio'] + 1;
            }else{
                if(($res['asporto'] + 1) < $av['asporto']){}
                else{
                    $vis['asporto'] = 0;
                }
                $res['asporto'] = $res['asporto'] + 1;
            }
        }
        $date->visible = json_encode($vis);
        $date->reserving = json_encode($res);
        $date->update();

        

        return [$date, $np_c1, $np_c2];
    
        
    }

    protected function handlePaymentIntentFailed($paymentIntent)
    {
        // Aggiorna il tuo database per segnare l'ordine come fallito
        $orderId = $paymentIntent->metadata->order_id; // Assicurati di aver aggiunto l'ID dell'ordine nei metadata
        // Esegui la logica per aggiornare lo stato dell'ordine nel database
        $orderId = $session->metadata->order_id; // Assicurati di aver aggiunto l'ID dell'ordine nei metadata
        // Esegui la logica per aggiornare lo stato dell'ordine nel database
        $order = Order::where('id', $orderId)->firstOrFail();
        $order->status = 0;
        $order->update();
    
    }
    protected function isLastResponseWaWithin24Hours()
    {
        // Trova il record con name = 'wa'
        $setting = Setting::where('name', 'wa')->first();

        if ($setting) {
            // Decodifica il campo 'property' da JSON ad array
            $property = json_decode($setting->property, true);

            // Controlla se 'last_response_wa' è impostato
            if (isset($property['last_response_wa']) && !empty($property['last_response_wa'])) {
                // Confronta la data salvata con le ultime 24 ore
                $lastResponseDate = Carbon::parse($property['last_response_wa']);
                return $lastResponseDate->greaterThanOrEqualTo(Carbon::now()->subHours(24));
            }
        }

        return false; // Se il record non esiste o la data non è impostata
    }

}

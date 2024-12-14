<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Date;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Ingredient;
use App\Models\Reservation;
use App\Models\OrderProduct;
use Illuminate\Http\Request;
use App\Mail\confermaOrdineAdmin;
use App\Events\NewOrderNotification;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Api\PaymentController;



class OrderController extends Controller
{
    private $validations = [
        'name'      => 'required|string|max:50',
        'phone'     => 'required|string|max:20',
        'email'     => 'required|email|max:100',
        'message'   => 'nullable|string|max:1000',
    ];

    public function store(Request $request)
    { 
        
        

        $request->validate($this->validations);
        $delivery = false;
        $data = $request->all();
        try {

            $date = Date::where('date_slot', $data['date_slot'])->firstOrFail();
            $vis = json_decode($date->visible, true);
            $av = json_decode($date->availability, true);
            $res = json_decode($date->reserving, true);
    
            $arrvar = str_replace('\\', '', $data['cart']);
            $cart = json_decode($arrvar, true);
    
            if(config('configurazione.typeOfOrdering')){
                $res_c1 = $res['cucina_1'];
                $res_c2 = $res['cucina_2'];
                $av_c1 = $av['cucina_1'];
                $av_c2 = $av['cucina_2'];
                $np_c1 = $data['npezzi_c1'];
                $np_c2 = $data['npezzi_c2'];
                if(isset($data['comune'])){
                    if( ($res['domicilio'] + 1) < $av['domicilio']){
                        $res['domicilio'] = $res['domicilio'] + 1;
    
                        if((($res_c1 + $np_c1) < $av_c1) && (($res_c2 + $np_c2) < $av_c2)){
                            $res['cucina_1'] = $res['cucina_1'] + $np_c1;
                            $res['cucina_2'] = $res['cucina_2'] + $np_c2;
                        }elseif(($res_c1 + $np_c1) == $av_c1){
                            $res['cucina_1'] = $res['cucina_1'] + $np_c1;
                            $vis['cucina_1'] = 0;
                        }elseif(($res_c2 + $np_c2) == $av_c2){
                            $res['cucina_2'] = $res['cucina_2'] + $np_c2;
                            $vis['cucina_2'] = 0;
                        }elseif((($res_c1 + $np_c1) == $av_c1) && (($res_c2 + $np_c2) == $av_c2)){
                            $res['cucina_1'] = $res['cucina_1'] + $np_c1;
                            $vis['cucina_1'] = 0;
                            $res['cucina_2'] = $res['cucina_2'] + $np_c2;
                            $vis['cucina_2'] = 0;
                        }else{
                            return response()->json([
                                'success' => false,
                                'message' => 'Controlla meglio la disponibilità per l\'orario che hai scelto... Prova di nuovo!',
                                'data' => $date
                            ]);
                        }
                    } elseif (($res['domicilio'] + 1) == $av['domicilio']){
                        $res['domicilio'] = $res['domicilio'] + 1;
                        $vis['domicilio'] = 0;
                        if((($res_c1 + $np_c1) < $av_c1) && (($res_c2 + $np_c2) < $av_c2)){
                            $res['cucina_1'] = $res['cucina_1'] + $np_c1;
                            $res['cucina_2'] = $res['cucina_2'] + $np_c2;
                        }elseif(($res_c1 + $np_c1) == $av_c1){
                            $res['cucina_1'] = $res['cucina_1'] + $np_c1;
                            $vis['cucina_1'] = 0;
                        }elseif(($res_c2 + $np_c2) == $av_c2){
                            $res['cucina_2'] = $res['cucina_2'] + $np_c2;
                            $vis['cucina_2'] = 0;
                        }elseif((($res_c1 + $np_c1) == $av_c1) && (($res_c2 + $np_c2) == $av_c2)){
                            $res['cucina_1'] = $res['cucina_1'] + $np_c1;
                            $vis['cucina_1'] = 0;
                            $res['cucina_2'] = $res['cucina_2'] + $np_c2;
                            $vis['cucina_2'] = 0;
                        }else{
                            return response()->json([
                                'success' => false,
                                'message' => 'Controlla meglio la disponibilità per l\'orario che hai scelto... Prova di nuovo!',
                                'data' => $date
                            ]);
                        }
                    }else{
                        return response()->json([
                            'success' => false,
                            'message' => 'Controlla meglio la disponibilità per l\'orario che hai scelto... Prova di nuovo!',
                            'data' => $date
                        ]);
    
                    }
                }else{
                    if((($res_c1 + $np_c1) < $av_c1) && (($res_c2 + $np_c2) < $av_c2)){
                        $res['cucina_1'] = $res['cucina_1'] + $np_c1;
                        $res['cucina_2'] = $res['cucina_2'] + $np_c2;
                    }elseif(($res_c1 + $np_c1) == $av_c1){
                        $res['cucina_1'] = $res['cucina_1'] + $np_c1;
                        $vis['cucina_1'] = 0;
                    }elseif(($res_c2 + $np_c2) == $av_c2){
                        $res['cucina_2'] = $res['cucina_2'] + $np_c2;
                        $vis['cucina_2'] = 0;
                    }elseif((($res_c1 + $np_c1) == $av_c1) && (($res_c2 + $np_c2) == $av_c2)){
                        $res['cucina_1'] = $res['cucina_1'] + $np_c1;
                        $vis['cucina_1'] = 0;
                        $res['cucina_2'] = $res['cucina_2'] + $np_c2;
                        $vis['cucina_2'] = 0;
                    }else{
                        return response()->json([
                            'success' => false,
                            'message' => 'Sembra che pochi attimi fa la disponibilita sia cambiata, ci dispiace per l\'inconveniente... provate di nuovo',
                            'data' => $date
                        ]);
                    }
    
                }
                $date->visible = json_encode($vis);
                $date->reserving = json_encode($res);
            }else{
                if(isset($data['comune'])){
                    if(($res['domicilio'] + 1) < $av['domicilio']){
                        $res['domicilio'] = $res['domicilio'] + 1;
                        $date->reserving = json_encode($res);  
                    }elseif(($res['domicilio'] + 1) == $av['domicilio']){
                        $res['domicilio'] = $res['domicilio'] + 1;
                        $date->reserving = json_encode($res);
                        $vis['domicilio'] = 0;
                        $date->visible = json_encode($vis);
                    }else{
                        return response()->json([
                            'success' => false,
                            'message' => 'Controlla meglio la disponibilità per l\'orario che hai scelto... Prova di nuovo!',
                            'data' => $date
                        ]);
                    }
                }else{
                    if(($res['asporto'] + 1) < $av['asporto']){
                        $res['asporto'] = $res['asporto'] + 1;
                        $date->reserving = json_encode($res);  
                    }elseif(($res['asporto'] + 1) == $av['asporto']){
                        $res['asporto'] = $res['asporto'] + 1;
                        $date->reserving = json_encode($res);
                        $vis['asporto'] = 0;
                        $date->visible = json_encode($vis);
                    }else{
                        return response()->json([
                            'success' => false,
                            'message' => 'Sembra che pochi attimi fa la disponibilita sia cambiata, ci dispiace per l\'inconveniente... provate di nuovo',
                            'data' => $date
                        ]);
                    }
                }
            }
            $total_price = 0;
            for ($i = 0; $i < count($cart); ++$i) {
                $product = Product::where('id', $cart[$i]['id'])->first();
                $total_price += $product->price * $cart[$i]['counter'];
                $cart[$i]['price'] = $product->price;
       
                for ($z = 0; $z < count($cart[$i]['add']); $z++) {
                    $ingredient = Ingredient::where('name', $cart[$i]['add'][$z])->first();
                    $total_price += $ingredient->price * $cart[$i]['counter'];

                    $cart[$i]['price'] +=  $ingredient->price;
                }
                for ($z = 0; $z < count($cart[$i]['option']); $z++) {
                    $ingredient = Ingredient::where('name', $cart[$i]['option'][$z])->first();
                    $total_price += $ingredient->price * $cart[$i]['counter'];
                    
                    $cart[$i]['price'] +=  $ingredient->price;
                }
            }
            
    
            $newOrder = new Order();
            
            $newOrder->name = $data['name'];
            $newOrder->surname = $data['surname'];
            $newOrder->date_slot = $data['date_slot'];
            $newOrder->phone = $data['phone'];
            $newOrder->email = $data['email'];
            $newOrder->message = $data['message'];
            $newOrder->news_letter = $data['news_letter'];
            $newOrder->status = $data['paying'] ? 4 : 2;
            if (isset($data['comune'])) {
                $newOrder->comune = $data['comune'];
                $newOrder->address = $data['via'];
                $newOrder->address_n = $data['cv'];
                $delivery = true;

                $setting = Setting::where('name', 'Possibilità di consegna a domicilio')->first();
                $shipping_cost = json_decode($setting->property, 1);
                $newOrder->tot_price = $total_price + $shipping_cost['delivery_cost'] ;
            }else{
                $newOrder->tot_price = $total_price;
            }
            $newOrder->save();
            
            foreach ($cart as $e) {
                $item_order = new OrderProduct();
                $item_order->order_id = $newOrder->id;
                $item_order->product_id = $e['id'];
                $item_order->quantity= $e['counter'];
                $item_order->remove = json_encode($e['remove']);
                $item_order->add = json_encode($e['add']);
                $item_order->option = json_encode($e['option']);
                $item_order->save();
            }

            
            

            $payment_controller = new PaymentController();
            
            if($data['paying']){   
                
                $payment_url = $payment_controller->checkout($newOrder->products, $newOrder->id, $delivery);
                
                return response()->json([
                    'success'   => true,
                    'payment'   => true,
                    'url'       => $payment_url,
                    'orderId'   => $newOrder->id,
                ]);
                
            }else{
                
                $date->update();
                // Ottieni le impostazioni di contatto
                $set = Setting::where('name', 'Contatti')->firstOrFail();
                $p_set = json_decode($set->property, true);
                if(isset($p_set['telefono'])){
                    $telefono = $p_set['telefono'];
                }else{
                    $telefono = '3332222333';
                }
                
                $bodymail_a = [
                    'type' => 'or',
                    'to' => 'admin',
        
                    'order_id' => $newOrder->id,
                    'name' => $newOrder->name,
                    'surname' => $newOrder->surname,
                    'email' => $newOrder->email,
                    'date_slot' => $newOrder->date_slot,
                    'message' => $newOrder->message,
                    'phone' => $newOrder->phone,
                    'admin_phone' => $p_set['telefono'],
                    
                    'comune' => $newOrder->comune,
                    'address' => $newOrder->address,
                    'address_n' => $newOrder->address_n,
                    
                    'status' => $newOrder->status,
                    'cart' => $newOrder->products,
                    'total_price' => $newOrder->tot_price,
        
                    
                ];
                $bodymail_u = [
                    'type' => 'or',
                    'to' => 'user',
        
                    'order_id' => $newOrder->id,
                    'name' => $newOrder->name,
                    'surname' => $newOrder->surname,
                    'email' => $newOrder->email,
                    'date_slot' => $newOrder->date_slot,
                    'message' => $newOrder->message,
                    'phone' => $newOrder->phone,
                    'admin_phone' => $p_set['telefono'],
                    
                    'comune' => $newOrder->comune,
                    'address' => $newOrder->address,
                    'address_n' => $newOrder->address_n,
                    
                    'status' => $newOrder->status,
                    'cart' => $newOrder->products,
                    'total_price' => $newOrder->tot_price,
        
                    
                ];
                $mail = new confermaOrdineAdmin($bodymail_u);
                Mail::to($data['email'])->send($mail);
        
                $mailAdmin = new confermaOrdineAdmin($bodymail_a);
                Mail::to(config('configurazione.mail'))->send($mailAdmin);

                $info =  $newOrder->name . ' ha ordinato per il ' . $newOrder->date_slot . ': ';
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
                if($newOrder->comune){
                    $info .= "Consegna a domicilio: {$newOrder->address}, {$newOrder->address_n}, {$newOrder->comune} ";
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
                
                // // Effettua la richiesta HTTP POST con le intestazioni necessarie
                $response = Http::withHeaders([
                    'Authorization' => config('configurazione.WA_TO'),
                    'Content-Type' => 'application/json'
                ])->post($url, $data);

                // Estrai l'ID del messaggio dalla risposta di WhatsApp
                $messageId = $response->json()['messages'][0]['id'] ?? null;
                $response_j = $response->json();

                if ($messageId) {
                    // Salva il message_id nell'ordine
                    $newOrder->whatsapp_message_id = $messageId;
                    $newOrder->update();
                }
    
                // Gestisci la risposta
                if ($response->successful()) {
                    return response()->json(['message' => 'Messaggio inviato con successo' , 'response' => $response_j, 'id' => $messageId], 200);
                } else {
                    return response()->json(['success' => false, 'error' => 'Errore nell\'invio del messaggio', 'details' => $response->json()], $response->status());
                }
                // return response()->json([
                //     'success'   => true,
                //     'payment'   => false,
                //     'order'     => $newOrder,
                // ]);
            }


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

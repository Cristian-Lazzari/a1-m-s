<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Date;
use App\Models\Setting;
use App\Models\Reservation;
use Illuminate\Http\Request;
use App\Mail\confermaOrdineAdmin;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class ReservationController extends Controller
{
    private $validations = [
        'name'      => 'required|string|max:50',
        'phone'     => 'required|string|max:20',
        'email'     => 'required|email|max:100',
        'n_adult'  => 'required|string|max:10',
        'n_child'  => 'required|string|max:10',
        'message'   => 'nullable|string|max:1000',
    ];

    public function store(Request $request)
    {
        try {
            // Validazione della richiesta
            $request->validate($this->validations);

            // Ottieni i dati dalla richiesta
            $data = $request->all();
            
            // Cerca la data corrispondente
            $date = Date::where('date_slot', $data['date_slot'])->firstOrFail();
            $vis = json_decode($date->visible, true);
            $av = json_decode($date->availability, true);
            $res = json_decode($date->reserving, true);

            // Calcola numero di persone
            $n_adult = intval($data['n_adult']);
            $n_child = intval($data['n_child']);
            $tot_p = $n_adult + $n_child;
            $n_person = [
                'adult' => $n_adult,
                'child' => $n_child,
            ];
            // Controlla la disponibilità e aggiorna le prenotazioni
            if(config('configurazione.double_t')){
                $sala = $data['sala'];
                if($sala == 1){
                    if(($res['table_1'] + $tot_p) < $av['table_1']){
                        $res['table_1'] = $res['table_1'] + $tot_p;
                        $date->reserving = json_encode($res);
                    } elseif(($res['table_1'] + $tot_p) == $av['table_1']) {
                        $res['table_1'] = $res['table_1'] + $tot_p;
                        $date->reserving = json_encode($res);
                        $vis['table_1'] = 0;
                        $date->visible = json_encode($vis);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'Sembra che pochi attimi fa la disponibilita sia cambiata, ci dispiace per l\'inconveniente... provate di nuovo',
                            'data' => $date
                        ]);
                    }
                }else{
                    if(($res['table_2'] + $tot_p) < $av['table_2']){
                        $res['table_2'] = $res['table_2'] + $tot_p;
                        $date->reserving = json_encode($res);
                    } elseif(($res['table_2'] + $tot_p) == $av['table_2']) {
                        $res['table_2'] = $res['table_2'] + $tot_p;
                        $date->reserving = json_encode($res);
                        $vis['table_2'] = 0;
                        $date->visible = json_encode($vis);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'Sembra che pochi attimi fa la disponibilita sia cambiata, ci dispiace per l\'inconveniente... provate di nuovo',
                            'data' => $date
                        ]);
                    }
                }
            }else{
                if(($res['table'] + $tot_p) < $av['table']){
                    $res['table'] = $res['table'] + $tot_p;
                    $date->reserving = json_encode($res);
                } elseif(($res['table'] + $tot_p) == $av['table']) {
                    $res['table'] = $res['table'] + $tot_p;
                    $date->reserving = json_encode($res);
                    $vis['table'] = 0;
                    $date->visible = json_encode($vis);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Sembra che pochi attimi fa la disponibilita sia cambiata, ci dispiace per l\'inconveniente... provate di nuovo',
                        'data' => $date
                    ]);
                }
            }

        
            // Crea la nuova prenotazione
            $newRes = new Reservation();
            $newRes->name = $data['name'];
            $newRes->surname = $data['surname'];
            $newRes->phone = $data['phone'];
            $newRes->email = $data['email'];
            $newRes->date_slot = $data['date_slot'];
            $newRes->n_person = json_encode($n_person);
            $newRes->message = $data['message'];
            $newRes->status = 2;
            $newRes->news_letter = $data['news_letter'];
            if(config('configurazione.double_t')){
                $newRes->sala = $data['sala'];
            }
            
            $date->update();
            $newRes->save();

            // Ottieni le impostazioni di contatto
            $set = Setting::where('name', 'Contatti')->firstOrFail();
            $p_set = json_decode($set->property, true);
            if(isset($p_set['telefono'])){
                $telefono = $p_set['telefono'];
            }else{
                $telefono = '3332222333';
            }

            // Prepara i dati per le email
            $bodymail_a = [
                'type' => 'res',
                'to' => 'admin',
                
                'res_id' => $newRes->id,
                'name' => $newRes->name,
                'surname' => $newRes->surname,
                'email' => $newRes->email,
                'date_slot' => $newRes->date_slot,
                'message' => $newRes->message,
                'sala' => $newRes->message,
                'phone' => $newRes->phone,
                'admin_phone' => $telefono,
                'n_person' => $n_person,
                'status' => $newRes->status,
            ];
            $bodymail_u = [
                'type' => 'res',
                'to' => 'user',
                
                'res_id' => $newRes->id,
                'name' => $newRes->name,
                'surname' => $newRes->surname,
                'email' => $newRes->email,
                'date_slot' => $newRes->date_slot,
                'message' => $newRes->message,
                'sala' => $newRes->sala,
                'phone' => $newRes->phone,
                'admin_phone' => $telefono,
                'n_person' => $n_person,
                'status' => $newRes->status,
            ];

            // Invia le email
            $mail = new confermaOrdineAdmin($bodymail_u);
            Mail::to($data['email'])->send($mail);
    
            $mailAdmin = new confermaOrdineAdmin($bodymail_a);
            Mail::to(config('configurazione.mail'))->send($mailAdmin);

            $info = $newRes->name . ' ' . $newRes->surname .' ha prenotato per il: ' . $newRes->date_slot . ', gli ospiti sono: ';
            if($n_adult && $n_child){
                $info .= $n_adult . ' adulti e ' . $n_child . ' bambini';
            }elseif($n_adult){
                $info .= $n_adult . ' adulti';
            }elseif($n_child){
                $info .= $n_child . ' bambini';
            }
            if (config('configurazione.double_t') && $newRes->sala ) {
                $info .= ' ** _Sala prenota: ';
                if ($newRes->sala == 1) {
                    $info .= config('configurazione.set_time_dt')[0];
                }else{
                    $info .= config('configurazione.set_time_dt')[1];
                }
            }
            $info .='_ ** ';
            
            $url = 'https://graph.facebook.com/v20.0/'. config('configurazione.WA_ID') . '/messages';
            $number = config('configurazione.WA_N');

            
            if ($this->isLastResponseWaWithin24Hours()) {
                // Esegui azione se è entro le ultime 24 ore
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
                
            } else {
                // Esegui azione alternativa
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
                                        'text' => 'prenotazione tavolo', 
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
                $newRes->whatsapp_message_id = $messageId;
                $newRes->update();
            }
        

            // Risposta di successo
            return response()->json([
                'success' => true,
                'prenotazione' => $newRes,
                'data' => $date
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Errore nel trovare una risorsa
            return response()->json([
                'success' => false,
                'message' => 'Data o impostazione non trovata: ' . $e->getMessage(),
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Errore di validazione
            return response()->json([
                'success' => false,
                'message' => 'Errore di validazione: ' . $e->getMessage(),
                'errors' => $e->errors(),
            ], 200);

        } catch (\Exception $e) {
            // Gestione generale degli errori
            return response()->json([
                'success' => false,
                'message' => 'Si è verificato un errore: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 200);
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
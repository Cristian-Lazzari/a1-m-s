<?php

namespace App\Http\Controllers\Webhooks;

use Swift_Mailer;
use Carbon\Carbon;
use Stripe\Refund;
use Stripe\Stripe;
use App\Models\Order;
use App\Models\Source;
use App\Models\Message;
use App\Models\Ingredient;
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
use App\Services\WaFailureAlertService;
use App\Services\Whatsapp\RestaurantWebhookRouter;
use App\Services\Whatsapp\TenantApi;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
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
        $data = [];
        $messageId = null;
        $source = null;

        try {
        $data = $request->all();

        // Il payload si scrive come stringa JSON. Passandolo come contesto,
        // Monolog lo normalizza e taglia tutto oltre il nono livello: proprio
        // dove stanno gli errori di WhatsApp, che infatti nel log comparivano
        // come "Over 9 levels deep, aborting normalization".
        Log::info('Webhook ricevuto: ' . $this->encodePayload($data));

        // Gli eventi dei numeri dei ristoranti (promemoria ai clienti) vanno al
        // loro gestionale; qui sotto resta solo il numero di piattaforma.
        $data = app(RestaurantWebhookRouter::class)->dispatch($data);

        if ($data === null) {
            return response('EVENT_RECEIVED', 200);
        }

        $incomingMessage = data_get($data, 'entry.0.changes.0.value.messages.0');

        if (!$incomingMessage) {
            // Le ricevute di consegna non sono messaggi, e finivano tutte fra
            // le "strutture non valide": è lì dentro che WhatsApp segnala gli
            // invii falliti, che così non li vedeva nessuno.
            $this->handleStatusCallback($data);

            return response('EVENT_RECEIVED', 200);
        }

        Log::info('Messaggio webhook normalizzato: ' . $this->encodePayload($incomingMessage));

        $messageId = data_get($incomingMessage, 'context.id');
        if (!$messageId) {
            return $this->abortReply('Risposta WhatsApp senza context.id', [
                'incoming_type' => $incomingMessage['type'] ?? null,
                'message' => $incomingMessage,
            ]);
        }

        $storedMessage = Message::where('wa_id', $messageId)->first();
        if (!$storedMessage) {
            return $this->abortReply('Nessun messaggio nostro corrisponde alla risposta ricevuta', [
                'wa_id' => $messageId,
                'message' => $incomingMessage,
            ]);
        }

        $source = Source::find($storedMessage->source);
        if (!$source) {
            return $this->abortReply('Ristorante non trovato per il messaggio ricevuto', [
                'wa_id' => $messageId,
                'source_id' => $storedMessage->source,
            ]);
        }

        // Il ristoratore ha risposto citando l'avviso "un cliente ti ha
        // scritto": il testo va al cliente dal numero del ristorante.
        if ($storedMessage->type === 'inbox') {
            $this->relayToCustomer($storedMessage, $incomingMessage, $source);

            return response('EVENT_RECEIVED', 200);
        }

        $reply = $this->extractWhatsappReplyAction($incomingMessage, $storedMessage);
        if (!$reply) {
            return $this->abortReply('Impossibile capire quale pulsante è stato premuto', [
                'wa_id' => $messageId,
                'stored_type' => $storedMessage->type,
                'incoming_type' => $incomingMessage['type'] ?? null,
                'message' => $incomingMessage,
            ], $source);
        }

        $responseValue = $this->mapWhatsappReplyToResponse($reply['action']);
        if ($responseValue === null) {
            return $this->abortReply('Pulsante premuto non riconosciuto: nessun aggiornamento eseguito', [
                'wa_id' => $messageId,
                'action' => $reply['action'],
                'reply_type' => $reply['type'],
            ], $source);
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

        try {
            $this->handle_p2($payload, $source);
        } catch (\Throwable $e) {
            Log::error('(WC) Errore nel flusso webhook prima/durante handle_p2', [
                'wa_id' => $messageId,
                'source_id' => $source->id ?? null,
                'response' => $responseValue,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())->take(5)->all(),
            ]);

            app(WaFailureAlertService::class)->notify('wa_webhook', [
                'wa_id' => $messageId,
                'lang' => $storedMessage->lang ?? 'it',
                'source' => $source,
                'resource' => [
                    'message_model_id' => $storedMessage->id ?? null,
                    'source_id' => $source->id ?? null,
                    'response' => $responseValue,
                    'incoming_type' => $incomingMessage['type'] ?? null,
                ],
            ], $e);
        } finally {
            DB::disconnect('mysql');
            Config::set("database.connections.mysql", config('database.connections.mysql'));
        }

        return response('EVENT_RECEIVED', 200);
        } catch (\Throwable $e) {
            Log::error('(WC) Errore non gestito in handle webhook', [
                'wa_id' => $messageId,
                'source_id' => $source->id ?? null,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())->take(5)->all(),
            ]);

            app(WaFailureAlertService::class)->notify('wa_webhook', [
                'wa_id' => $messageId,
                'source' => $source,
                'resource' => [
                    'payload_keys' => array_keys($data),
                    'entry_count' => is_countable($data['entry'] ?? null) ? count($data['entry']) : null,
                ],
            ], $e);

            return response('EVENT_RECEIVED', 200);
        }
    }

    protected function relayToCustomer(Message $storedMessage, array $incomingMessage, Source $source): void
    {
        $from = (string) ($incomingMessage['from'] ?? '');
        $text = trim((string) data_get($incomingMessage, 'text.body', ''));

        // Il ristoratore ha appena scritto: la sua finestra di 24 ore è aperta,
        // come quando preme un pulsante.
        try {
            $this->connectToTenant($source);
            $setting = Setting::on('dynamic')->where('name', 'wa')->first();
            $property = $setting ? json_decode($setting->property, true) : null;
            $numbers = is_array($property['numbers'] ?? null) ? array_values($property['numbers']) : [];
            $index = $this->matchWhatsappNumberIndex($from, $numbers);

            if ($index !== null) {
                $this->updateLastResponseWa($index);
            }
        } catch (\Throwable $e) {
            Log::warning('(WC) Finestra 24h del ristoratore non aggiornata: ' . $e->getMessage());
        }

        if ($text === '') {
            $this->sendPlatformText($from, '⚠️ Si possono inoltrare al cliente solo messaggi di testo.', $incomingMessage['id'] ?? null);

            return;
        }

        try {
            $response = app(TenantApi::class)->post($source, 'relay', [
                'ref' => $storedMessage->ref,
                'text' => $text,
            ]);
            $error = $response->successful() ? null : (data_get($response->json(), 'error') ?: 'HTTP ' . $response->status());
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        if ($error === null) {
            $ack = '✅ Inviato al cliente dal numero del locale.';
        } elseif ($error === 'window_closed') {
            $ack = '⚠️ Non inviato: il cliente ha scritto più di 24 ore fa e WhatsApp non permette di rispondergli. Meglio chiamarlo.';
        } else {
            $ack = '⚠️ Non inviato al cliente. Rispondigli dalla dashboard, sezione WhatsApp.';

            Log::error('(WC) Risposta del ristoratore non inoltrata al cliente', [
                'ref' => $storedMessage->ref,
                'source_id' => $source->id,
                'error' => $error,
            ]);
        }

        $this->sendPlatformText($from, $ack, $incomingMessage['id'] ?? null);
    }

    /** Messaggio libero dal numero di piattaforma, agganciato al messaggio del ristoratore. */
    protected function sendPlatformText(string $to, string $body, ?string $contextId = null): void
    {
        if ($to === '') {
            return;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $body],
        ];

        if ($contextId) {
            $payload['context'] = ['message_id' => $contextId];
        }

        try {
            Http::withHeaders([
                'Authorization' => config('configurazione.WA_TO'),
                'Content-Type' => 'application/json',
            ])->post('https://graph.facebook.com/v24.0/' . config('configurazione.WA_ID') . '/messages', $payload);
        } catch (\Throwable $e) {
            Log::warning('(WC) Conferma al ristoratore non inviata: ' . $e->getMessage());
        }
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

        $this->connectToTenant($source);

        // Lingua impostata nel pannello del ristorante (usata per WA co-worker)
        $langSetting = Setting::on('dynamic')->where('name', 'lang')->first();
        $rawAdminLang = $langSetting?->property ?? 'it';
        $decodedAdminLang = json_decode($rawAdminLang, true);
        $adminLang = is_string($decodedAdminLang) ? $decodedAdminLang : $rawAdminLang;


        $number = $data['number'];


        $setting = Setting::on('dynamic')->where('name', 'wa')->first();

        $property = json_decode($setting->property, true);
        $numbers = is_array($property['numbers'] ?? null) ? array_values($property['numbers']) : [];
        $co_work = false;
        $number_correct = null;
        $p = null;

        // Il numero che arriva da WhatsApp è in formato internazionale e senza
        // segni: confrontarlo tale e quale con quello scritto a mano nelle
        // impostazioni faceva fallire array_search, e quel false valeva come
        // indice 0. Risultato: rispondeva il secondo numero e il sistema
        // segnava come "attivo" il primo, che poi si vedeva rifiutare il
        // messaggio libero con l'errore 131047.
        $responderIndex = $this->matchWhatsappNumberIndex($number, $numbers);

        if ($responderIndex === null) {
            Log::warning('(WC) Il numero che ha risposto non è fra quelli configurati', [
                'number' => $number,
                'numbers' => $numbers,
            ]);
        } else {
            // La finestra di 24 ore si apre solo per chi ha davvero risposto.
            $this->updateLastResponseWa($responderIndex);
        }

        if (count($numbers) === 2 && $responderIndex !== null) {
            $co_work = true;
            $p = $responderIndex === 0 ? 1 : 0;
            $number_correct = $numbers[$p];
            Log::info("(WC) Risposta da {$responderIndex}, avviso il collega {$p}");
        }

        $messageId = $data['wa_id'];
        $button_r = $data['response'];

        // Legge la lingua dal messaggio salvato; default 'it' per backward compatibility
        $storedMessage = Message::where('wa_id', $messageId)->first();
        $lang = $storedMessage->lang ?? 'it';

        $order       = Order::on('dynamic')->where('whatsapp_message_id', 'like', '%' . $messageId . '%')->first();
        $reservation = Reservation::on('dynamic')->where('whatsapp_message_id', 'like', '%' . $messageId . '%')->first();
        if ($order) {
            Log::info("(WC)L' ordine: " . json_encode($order));
            $status = $order->status;
            $this->statusOrder($button_r, $order, $source, $lang);
            if($button_r == 1 && in_array($status, [1, 5])){
                return;
            }elseif($button_r == 0 && in_array($status, [0, 6])){
                return;
            }elseif(in_array($status, [1, 5, 0, 6])){
                return;
            }
            if ($co_work) {
                $this->message_co_worker(1, $button_r, $p, $order, $number_correct, $adminLang, $source);
            }
        } else
        if ($reservation) {
            if ($reservation) {
                $status = $reservation->status;
                $this->statusRes($button_r, $reservation, $source, $lang);
                if($button_r == 1 && in_array($status, [1, 5])){
                    return;
                }elseif($button_r == 0 && in_array($status, [0, 6])){
                    return;
                }elseif(in_array($status, [1, 5, 0, 6])){
                    return;
                }
                if ($co_work) {
                    $this->message_co_worker(false, $button_r, $p, $reservation, $number_correct, $adminLang, $source);
                }
            }
        } else {
            // Il pulsante è stato premuto e capito, ma nel database del
            // ristorante non c'è nulla che porti questo whatsapp_message_id:
            // il flusso muore qui e il cliente non riceve niente. Prima era
            // una riga info che si perdeva nel rumore.
            $this->abortReply('Pulsante premuto ma nel database del ristorante non risulta né ordine né prenotazione', [
                'wa_id' => $messageId,
                'response' => $button_r,
                'source_id' => $source->id ?? null,
                'db' => $source->db_name ?? null,
            ], $source);
        }
        return;
    }

    protected function message_co_worker($o_r, $c_a, $p, $or_res, $number, $adminLang = 'it', $source = null){
        try {

            // Imposta locale per le traduzioni dei messaggi al co-worker admin
            app()->setLocale($adminLang);

            // Definizione dei messaggi in base allo stato (tradotti nella lingua admin)
            if ($o_r) {
                $link_id = config('configurazione.APP_URL') . '/admin/orders/' . $or_res->id;
                $m    = $c_a
                    ? __('admin.wa.order_msg_confirmed')
                    : __('admin.wa.order_msg_cancelled');
                $sub  = __('admin.wa.order_label');
                $word = $c_a
                    ? __('admin.wa.confirmed_word')
                    : __('admin.wa.cancelled_word');
            } else {
                $link_id = config('configurazione.APP_URL') . '/admin/reservations/' . $or_res->id;
                $m    = $c_a
                    ? __('admin.wa.res_msg_confirmed')
                    : __('admin.wa.res_msg_cancelled');
                $sub  = __('admin.wa.res_label');
                $word = $c_a
                    ? __('admin.wa.confirmed_word')
                    : __('admin.wa.cancelled_word');
            }

            $colleague = __('admin.wa.colleague');

            $messages = json_decode($or_res->whatsapp_message_id, true);
            // Se la notifica a questo numero non era partita non c'è nessun
            // messaggio a cui agganciare la risposta: il context con id nullo
            // farebbe rifiutare l'invio da WhatsApp.
            $old_id = is_array($messages) ? ($messages[$p] ?? null) : null;

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

                if (!$old_id) {
                    unset($data['context']);
                }
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
                                        'text' => $colleague
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

                if (!$old_id) {
                    unset($data['context']);
                }
            }
    
            $url = 'https://graph.facebook.com/v24.0/' . config('configurazione.WA_ID') . '/messages';

            $headers = [
                'Authorization' => config('configurazione.WA_TO'),
                'Content-Type' => 'application/json',
            ];

            $response = Http::withHeaders($headers)->post($url, $data);

            // Log della risposta ricevuta
            Log::info("(WC) Risposta da WhatsApp:", ['response' => $response->json()]);

            // Se il messaggio testuale fallisce (finestra 24h non aperta), fallback al template
            if (!$response->successful() && $type_m_1 === 0) {
                Log::warning("(WC) Messaggio testuale rifiutato da WhatsApp, fallback al template co-worker", [
                    'status' => $response->status(),
                    'response' => $response->json(),
                    'number' => $number,
                ]);

                $templateData = [
                    'messaging_product' => 'whatsapp',
                    'to' => $number,
                    'recipient_type' => 'individual',
                    'type' => 'template',
                    'template' => [
                        'name' => 'response_link',
                        'language' => ['code' => 'it'],
                        'components' => [
                            [
                                'type' => 'body',
                                'parameters' => [
                                    ['type' => 'text', 'text' => $sub],
                                    ['type' => 'text', 'text' => $word],
                                    ['type' => 'text', 'text' => $colleague],
                                    ['type' => 'text', 'text' => $or_res->name . ' ' . $or_res->surname],
                                    ['type' => 'text', 'text' => $or_res->date_slot],
                                    ['type' => 'text', 'text' => $link_id],
                                ],
                            ],
                        ],
                    ],
                ];

                if ($old_id) {
                    $templateData['context'] = ['message_id' => $old_id];
                }

                $response = Http::withHeaders($headers)->post($url, $templateData);
                Log::info("(WC) Risposta fallback template co-worker:", ['response' => $response->json()]);
                $type_m_1 = 1;
            }

            $this->storeSentMessage($response, $source, $type_m_1, $adminLang);

            return $response->json();
        } catch (Exception $e) {
            Log::error("(WC) Errore in message_co_worker", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
    
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Registra il messaggio appena inviato al collega. Senza questa riga, se
     * WhatsApp poi lo rifiuta, l'avviso di consegna fallita arriva senza nome
     * del ristorante: è il caso in cui nella mail si legge solo "-".
     */
    protected function storeSentMessage($response, $source, $typeFlag, $lang): void
    {
        try {
            $waId = data_get($response->json(), 'messages.0.id');

            if (!$waId || !$source) {
                return;
            }

            Message::create([
                'wa_id'  => $waId,
                'type'   => $typeFlag,
                'source' => $source->id,
                'lang'   => $lang ?: 'it',
            ]);
        } catch (\Throwable $e) {
            Log::error('(WC) Messaggio inviato non registrato: ' . $e->getMessage());
        }
    }

    protected function statusOrder($c_a, $order, $source, $lang = 'it')
    {
        Log::info("(WC) Inizio statusOrder");

        if ($c_a == 1 && in_array($order->status, [1, 5])) {
            return;
        } elseif ($c_a == 0 && in_array($order->status, [0, 6])) {
            return;
        } elseif (in_array($order->status, [1, 5, 0, 6])) {
            return;
        }

        // Imposta la locale per le traduzioni della mail cliente
        app()->setLocale($lang);
        $m = null;

        try {
            if ($c_a == 1) {
                if ($order->status == 2) {
                    $order->status = 1;
                } elseif ($order->status == 3) {
                    $order->status = 5;
                }
                // $m è un messaggio interno (log + co-worker WA), resta in italiano
                $m = 'L\'ordine è stata confermato correttamente';
            } else {
                if (in_array($order->status, [3, 5])) {
                    $m = 'L\'ordine è stato annullato e RIMBORSATO correttamente';
                    // Codice per rimborso Stripe
                    $stripeSecretKey = config('configurazione.STRIPE_SECRET');
                    Stripe::setApiKey($stripeSecretKey);

                    if ($order->checkout_session_id === null) {
                        throw new \RuntimeException('Payment not found for refundable order');
                    }
                    $refund = Refund::create([
                        'payment_intent' => $order->checkout_session_id,
                    ]);
                    $order->status = 6;
                } elseif (in_array($order->status, [2, 1])) {
                    $m = 'L\'ordine è stato annullato correttamente';
                    $order->status = 0;
                } else {
                    $m = 'L\'ordine era già stato annullato!';
                    return $m;
                }
            }

            $order->update();

            // Costruisce l'elenco prodotti per la mail
            $product_r = [];
            foreach ($order->products as $p) {
                $rawO = $p->pivot->option ?? null;
                $rawA = $p->pivot->add    ?? null;
                $rawR = $p->pivot->remove ?? null;
                $arrO = $this->decodeDynamicModifierJsonArray($rawO, 'option', $order, $p);
                $arrA = $this->decodeDynamicModifierJsonArray($rawA, 'add', $order, $p);
                $arrR = $this->decodeDynamicModifierJsonArray($rawR, 'remove', $order, $p);
                $r_option = [];
                $r_add    = [];

                if ($arrO || $arrA || $arrR) {
                    Log::info('(WC) Risoluzione modifiche ordine', [
                        'order_id' => $order->id ?? null,
                        'product_id' => $p->id ?? null,
                        'option' => $arrO,
                        'add' => $arrA,
                        'remove' => $arrR,
                    ]);
                }

                foreach ($arrO as $o) {
                    $this->appendResolvedDynamicIngredient($r_option, $o, 'option', $order, $p);
                }
                foreach ($arrA as $o) {
                    $this->appendResolvedDynamicIngredient($r_add, $o, 'add', $order, $p);
                }
                $p->setAttribute('r_option', $r_option);
                $p->setAttribute('r_add', $r_add);
                $p->setAttribute('r_remove', $arrR);
                $product_r[] = $p;
            }
            $cart_mail = [
                'products' => $product_r,
                'menus'    => $order->menus,
            ];

            $set   = Setting::on('dynamic')->where('name', 'Contatti')->first();
            $p_set = $set ? json_decode($set->property, true) : [];

            $bodymail = [
                'type'   => 'or',
                'to'     => 'user',
                'lang'   => $lang,

                'title'    => $c_a
                    ? __('admin.controllers.orders.accepted_title')
                    : __('admin.controllers.orders.cancelled_title'),
                'subtitle' => $order->status == 6
                    ? __('admin.controllers.orders.refund_subtitle')
                    : '',

                'whatsapp_message_id' => $order->whatsapp_message_id,
                'order_id'            => $order->id,
                'name'                => $order->name,
                'surname'             => $order->surname,
                'email'               => $order->email,
                'date_slot'           => $order->date_slot,
                'message'             => $order->message,
                'phone'               => $order->phone,
                'admin_phone'         => $p_set['telefono'] ?? '',
                'delivery_cost'       => $order->delivery_cost ?? null,

                'comune'    => $order->comune,
                'address'   => $order->address,
                'address_n' => $order->address_n,

                'app_domain' => $source->app_domain,
                'app_url'    => $source->app_url,
                'app_name'   => $source->app_name,

                'status'      => $order->status,
                'cart'        => $cart_mail,
                'total_price' => $order->tot_price,
            ];

            $this->sendCustomerMail($bodymail, $source, $lang, $order->email, 'wa_order', [
                'order_id'  => $order->id ?? null,
                'status'    => $order->status ?? null,
                'response'  => $c_a,
                'modifiers' => $this->orderModifierLogContext($order),
            ], $order);

        } catch (\Throwable $e) {
            Log::error('(WC) Errore in statusOrder', [
                'order_id' => $order->id ?? null,
                'response' => $c_a,
                'lang'     => $lang,
                'source' => [
                    'id' => $source->id ?? null,
                    'db_name' => $source->db_name ?? null,
                    'app_name' => $source->app_name ?? null,
                    'username' => $source->username ?? null,
                ],
                'modifiers' => $this->orderModifierLogContext($order),
                'error'    => $e->getMessage(),
                'file'     => $e->getFile(),
                'line'     => $e->getLine(),
                'trace'    => collect($e->getTrace())->take(5)->all(),
            ]);
            app(WaFailureAlertService::class)->notify('wa_order', [
                'wa_id'    => $order->whatsapp_message_id ?? null,
                'lang'     => $lang,
                'source'   => $source,
                'order'    => $order,
                'resource' => [
                    'order_id' => $order->id ?? null,
                    'status' => $order->status ?? null,
                    'response' => $c_a,
                    'modifiers' => $this->orderModifierLogContext($order),
                ],
            ], $e);
        }

        return $m;
    }

    /**
     * Manda al cliente la mail di conferma o annullamento.
     *
     * È l'unica comunicazione che il cliente riceve dopo che il ristoratore ha
     * premuto il pulsante su WhatsApp: lo stato a quel punto è già cambiato sul
     * database, quindi un invio perso lascia la persona senza risposta e senza
     * che nessuno se ne accorga. Qui dentro si controlla che ci siano davvero
     * destinatario e credenziali, si ritenta una volta sugli errori di rete, e
     * in ogni caso di resa si avvisa info@future-plus.it.
     */
    protected function sendCustomerMail(
        array $bodymail,
        $source,
        string $lang,
        $recipient,
        string $flow,
        array $resource,
        $order = null,
        $reservation = null
    ): void {
        $recipient = is_string($recipient) ? trim($recipient) : '';

        $alert = function (string $reason, ?\Throwable $e = null) use ($flow, $source, $resource, $order, $reservation, $lang, $recipient) {
            app(WaFailureAlertService::class)->notify($flow, [
                'wa_id'       => $order?->whatsapp_message_id ?? $reservation?->whatsapp_message_id,
                'lang'        => $lang,
                'source'      => $source,
                'order'       => $order,
                'reservation' => $reservation,
                'message'     => $reason,
                'error_type'  => 'customer_mail_not_sent',
                'resource'    => $resource + [
                    'recipient'    => $recipient !== '' ? $recipient : null,
                    'reason'       => $reason,
                    'mail_host'    => $source->host ?? null,
                    'mail_account' => $source->username ?? null,
                ],
            ], $e);
        };

        if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            Log::error('(WC) Mail al cliente non inviata: destinatario mancante o non valido', [
                'flow' => $flow,
                'recipient' => $recipient,
                'resource' => $resource,
            ]);
            $alert('Indirizzo del cliente mancante o non valido: la mail non è partita');

            return;
        }

        // Senza mittente non si spedisce da nessun canale: il Mailable lo usa
        // come from e Symfony rifiuterebbe la mail comunque.
        if (trim((string) ($source->username ?? '')) === '') {
            Log::error('(WC) Mail al cliente non inviata: manca la casella mittente del ristorante', [
                'flow' => $flow,
                'source_id' => $source->id ?? null,
            ]);
            $alert('Casella mittente del ristorante non configurata');

            return;
        }

        // Host o password mancanti: il transport smtp nascerebbe monco e
        // fallirebbe con un TypeError, che il failover non intercetta. In quel
        // caso si va dritti a sendmail, che almeno consegna.
        $incompleteSmtp = collect(['host' => $source->host ?? null, 'token' => $source->token ?? null])
            ->filter(fn ($value) => $value === null || trim((string) $value) === '')
            ->keys()
            ->all();

        $mailerName = 'tenant';

        if ($incompleteSmtp !== []) {
            $mailerName = 'sendmail';
            Log::warning('(WC) Credenziali SMTP del ristorante incomplete, spedisco con sendmail', [
                'flow' => $flow,
                'missing' => $incompleteSmtp,
                'source_id' => $source->id ?? null,
            ]);
        }

        // Si configura 'tenant_smtp', non 'smtp': il mailer di sistema resta
        // pulito e l'avviso di errore può partire anche quando la casella del
        // ristorante è quella che non funziona. Chi spedisce è 'tenant', il
        // failover che dopo questa casella prova sendmail.
        config()->set([
            'mail.mailers.tenant_smtp.transport'    => 'smtp',
            'mail.mailers.tenant_smtp.host'         => $source->host,
            'mail.mailers.tenant_smtp.port'         => 465,
            'mail.mailers.tenant_smtp.username'     => $source->username,
            'mail.mailers.tenant_smtp.password'     => $source->token,
            'mail.mailers.tenant_smtp.encryption'   => 'ssl',
            'mail.mailers.tenant_smtp.from.address' => $source->username,
            'mail.mailers.tenant_smtp.from.name'    => $source->app_name,
        ]);

        $this->purgeTenantMailer();

        $attempts = 2;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                Mail::mailer($mailerName)
                    ->to($recipient)
                    ->locale($lang)
                    ->send(new confermaOrdineAdmin($bodymail, $source->username, $source->app_name, $lang));

                Log::info("(WC) Mail al cliente inviata a {$recipient} da {$source->username}", [
                    'flow' => $flow,
                    'attempt' => $attempt,
                    'resource' => $resource,
                ]);

                return;
            } catch (\Throwable $e) {
                // Un template che esplode esplode uguale al secondo giro: si
                // ritenta solo su quello che può essere andato storto sulla rete.
                $retryable = !($e instanceof \Illuminate\View\ViewException) && !($e instanceof \ErrorException);
                $last = $attempt === $attempts || !$retryable;

                Log::error('(WC) Invio mail al cliente fallito', [
                    'flow' => $flow,
                    'attempt' => $attempt,
                    'retry' => !$last,
                    'recipient' => $recipient,
                    'mail_host' => $source->host ?? null,
                    'mail_account' => $source->username ?? null,
                    'resource' => $resource,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => collect($e->getTrace())->take(5)->all(),
                ]);

                if ($last) {
                    $alert('Invio della mail al cliente fallito: ' . $e->getMessage(), $e);

                    return;
                }

                $this->purgeTenantMailer();
                usleep(500000);
            }
        }
    }

    /**
     * Laravel tiene in cache il mailer già risolto: senza svuotarlo la
     * configurazione appena impostata viene ignorata e la mail parte con le
     * credenziali di chi l'ha risolto per primo — o non parte. Se il manager
     * non sa svuotare (succede con Mail::fake) si tira dritto: è una pulizia,
     * non una condizione per spedire.
     */
    private function purgeTenantMailer(): void
    {
        try {
            Mail::purge('tenant');
            Mail::purge('tenant_smtp');
        } catch (\Throwable $e) {
            Log::debug('(WC) Mailer del ristorante non svuotato: ' . $e->getMessage());
        }
    }

    private function decodeDynamicModifierJsonArray(mixed $value, string $modifierType, $order = null, $product = null): array
    {
        if (is_array($value)) {
            return array_values($value);
        }

        if ($value === null) {
            return [];
        }

        $raw = trim((string) $value);
        if ($raw === '' || $raw === '[]') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            Log::warning('(WC) JSON modifiche ordine non valido; continuo senza bloccare la mail', [
                'modifier_type' => $modifierType,
                'order_id' => $order->id ?? null,
                'product_id' => $product->id ?? null,
                'json_error' => json_last_error_msg(),
                'raw' => Str::limit($raw, 500),
            ]);

            return [];
        }

        return array_values($decoded);
    }

    private function modifierNameFromValue(mixed $value): string
    {
        if (is_array($value)) {
            return trim((string) ($value['name'] ?? $value['title'] ?? $value['label'] ?? $value['value'] ?? ''));
        }

        if (is_object($value)) {
            return trim((string) ($value->name ?? $value->title ?? $value->label ?? $value->value ?? ''));
        }

        return trim((string) $value);
    }

    private function orderModifierLogContext($order): array
    {
        try {
            return collect($order->products ?? [])->map(function ($product) {
                return [
                    'product_id' => $product->id ?? null,
                    'product_name' => $product->name ?? null,
                    'add' => $product->pivot->add ?? null,
                    'remove' => $product->pivot->remove ?? null,
                    'option' => $product->pivot->option ?? null,
                ];
            })->values()->all();
        } catch (\Throwable $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    private function appendResolvedDynamicIngredient(array &$items, mixed $name, string $modifierType, $order, $product): void
    {
        $ingredientName = $this->modifierNameFromValue($name);

        if ($ingredientName === '') {
            Log::warning('(WC) Modifica ordine ignorata: nome ingrediente/opzione vuoto', [
                'modifier_type' => $modifierType,
                'order_id' => $order->id ?? null,
                'product_id' => $product->id ?? null,
            ]);

            return;
        }

        $ingredient = $this->findIngredientOnDynamicByName($ingredientName);

        if ($ingredient === null) {
            Log::warning('(WC) Ingrediente/opzione non trovato su dynamic; continuo senza bloccare la mail', [
                'modifier_type' => $modifierType,
                'name' => $ingredientName,
                'order_id' => $order->id ?? null,
                'product_id' => $product->id ?? null,
            ]);

            $items[] = (object) [
                'name' => $ingredientName,
                'price' => 0,
                'missing' => true,
            ];

            return;
        }

        $items[] = $ingredient;
    }

    private function findIngredientOnDynamicByName(?string $name): ?Ingredient
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        try {
            if (!Schema::connection('dynamic')->hasTable('ingredients')) {
                Log::warning('(WC) Tabella ingredients non trovata su dynamic', [
                    'name' => $name,
                ]);

                return null;
            }

            if (Schema::connection('dynamic')->hasTable('ingredient_translations')) {
                $ingredient = Ingredient::on('dynamic')
                    ->join('ingredient_translations', 'ingredient_translations.ingredient_id', '=', 'ingredients.id')
                    ->select('ingredients.*')
                    ->addSelect('ingredient_translations.name as name')
                    ->where('ingredient_translations.name', $name)
                    ->distinct()
                    ->first();

                if ($ingredient !== null) {
                    return $ingredient;
                }
            }

            if (Schema::connection('dynamic')->hasColumn('ingredients', 'name')) {
                return Ingredient::on('dynamic')->where('name', $name)->first();
            }
        } catch (\Throwable $e) {
            Log::warning('(WC) Errore risoluzione ingrediente/opzione su dynamic; continuo senza bloccare la mail', [
                'name' => $name,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }

        return null;
    }

    protected function statusRes($c_a, $res, $source, $lang = 'it')
    {
        Log::info("(WC) Inizio statusRes");

        if ($c_a == 1 && in_array($res->status, [1, 5])) {
            return;
        } elseif ($c_a == 0 && in_array($res->status, [0, 6])) {
            return;
        } elseif (in_array($res->status, [1, 5, 0, 6])) {
            return;
        }

        // Imposta la locale per le traduzioni della mail cliente
        app()->setLocale($lang);

        $adv_s        = Setting::on('dynamic')->where('name', 'advanced')->first();
        $property_adv = $adv_s ? json_decode($adv_s->property, 1) : [];

        if ($c_a == 1) {
            $res->status = 1;
            // $m è un messaggio interno (log + co-worker WA), resta in italiano
            $m = 'La prenotazione è stata confermata correttamente';
        } else {
            if ($res->status == 0) {
                $m = 'La prenotazione era già stata annullata correttamente';
                return;
            }
            $res->status = 0;
            $m = 'La prenotazione è stata annullata correttamente';
        }

        $res->update();

        $set   = DB::connection('dynamic')->table('settings')->where('name', 'Contatti')->first();
        $p_set = $set ? json_decode($set->property, true) : [];

        $bodymail = [
            'type'     => 'res',
            'to'       => 'user',
            'lang'     => $lang,

            'title'    => $c_a
                ? __('admin.controllers.reservations.accepted_title_full')
                : __('admin.controllers.reservations.cancelled_title'),
            'subtitle' => '',

            'res_id'   => $res->id,
            'name'     => $res->name,
            'surname'  => $res->surname,
            'email'    => $res->email,
            'date_slot' => $res->date_slot,
            'message'  => $res->message,
            'sala'     => $res->sala,
            'phone'    => $res->phone,
            'admin_phone' => $p_set['telefono'] ?? '',

            'app_domain' => $source->app_domain,
            'app_url'    => $source->app_url,
            'app_name'   => $source->app_name,

            'whatsapp_message_id' => $res->whatsapp_message_id,
            'n_person'   => $res->n_person,
            'status'     => $res->status,

            'property_adv' => $property_adv,
        ];

        try {
            $this->sendCustomerMail($bodymail, $source, $lang, $res->email, 'wa_reservation', [
                'res_id'   => $res->id ?? null,
                'status'   => $res->status ?? null,
                'response' => $c_a,
            ], null, $res);
        } catch (\Throwable $e) {
            Log::error('(WC) Errore in statusRes', [
                'res_id' => $res->id ?? null,
                'lang'   => $lang,
                'error'  => $e->getMessage(),
                'file'   => $e->getFile(),
                'line'   => $e->getLine(),
            ]);
            app(WaFailureAlertService::class)->notify('wa_reservation', [
                'wa_id'       => $res->whatsapp_message_id ?? null,
                'lang'        => $lang,
                'source'      => $source,
                'reservation' => $res,
                'resource'    => ['res_id' => $res->id ?? null, 'status' => $res->status ?? null],
            ], $e);
        }

        return;
    }

    /**
     * Vicolo cieco nella lavorazione di una risposta WhatsApp.
     *
     * Sono i punti in cui il pulsante premuto dal ristoratore non arriva a
     * cambiare la prenotazione: da qui in poi il cliente non riceve nessuna
     * mail. Prima uscivano con una riga di log che non leggeva nessuno, ora
     * mandano un avviso a info@future-plus.it dicendo dove si sono fermati.
     */
    protected function abortReply(string $reason, array $context = [], $source = null)
    {
        Log::warning('(WC) ' . $reason . ': ' . $this->encodePayload($context));

        $waId = $context['wa_id'] ?? null;
        $alertKey = 'wa_reply_alert:' . ($waId ?: md5($reason . $this->encodePayload($context)));

        // WhatsApp ripete i webhook: un avviso per risposta, non uno per tentativo.
        if (Cache::add($alertKey, 1, now()->addHours(6))) {
            app(WaFailureAlertService::class)->notify('wa_reply', [
                'wa_id' => $waId,
                'source' => $source,
                'error_type' => 'whatsapp_reply_unprocessed',
                'message' => $reason,
                'resource' => $context,
            ]);
        }

        return response('EVENT_RECEIVED', 200);
    }

    /**
     * JSON completo e leggibile per il log: nessun troncamento in profondità,
     * al contrario di quello che fa Monolog con gli array di contesto.
     */
    protected function encodePayload($value): string
    {
        $json = json_encode(
            $value,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR
        );

        return $json !== false ? $json : '[payload non serializzabile]';
    }

    /**
     * Ricevute di consegna di WhatsApp (sent, delivered, read, failed).
     *
     * Gli invii falliti sono la ragione per cui un cliente non riceve né il
     * messaggio né, di conseguenza, la mail di conferma: qui vengono presi e
     * segnalati subito a info@future-plus.it con l'errore per esteso.
     */
    protected function handleStatusCallback(array $data): void
    {
        $statuses = data_get($data, 'entry.0.changes.0.value.statuses');

        if (!is_array($statuses) || $statuses === []) {
            // Né messaggi né ricevute: questo sì che è un payload inatteso.
            Log::warning('(WC) Webhook senza messaggi né ricevute: ' . $this->encodePayload($data));

            return;
        }

        foreach ($statuses as $status) {
            if (data_get($status, 'status') !== 'failed') {
                continue; // sent, delivered e read sono normale amministrazione
            }

            $waId = data_get($status, 'id');
            $recipient = data_get($status, 'recipient_id');
            $errors = data_get($status, 'errors', []);
            $reason = $this->describeStatusErrors($errors);

            Log::error('(WC) Invio WhatsApp fallito: ' . $this->encodePayload([
                'wa_id' => $waId,
                'recipient' => $recipient,
                'reason' => $reason,
                'errors' => $errors,
            ]));

            // WhatsApp ripete i webhook: senza questo blocco la stessa
            // consegna fallita manderebbe la stessa mail più volte.
            $alertKey = 'wa_failed_alert:' . ($waId ?: md5($this->encodePayload($status)));

            if (!Cache::add($alertKey, 1, now()->addHours(6))) {
                continue;
            }

            // Il ristorante di appartenenza è un di più: se il database non
            // risponde l'avviso deve partire lo stesso, magari più scarno.
            $storedMessage = null;
            $source = null;

            try {
                $storedMessage = $waId ? Message::where('wa_id', $waId)->first() : null;
                $source = $storedMessage ? Source::find($storedMessage->source) : null;
            } catch (\Throwable $lookupException) {
                Log::error('(WC) Consegna fallita: non risalgo al ristorante: '
                    . $lookupException->getMessage());
            }

            // WhatsApp ha appena detto che con questo numero la finestra di 24
            // ore è chiusa: il timestamp che ci aveva fatto scegliere il
            // messaggio libero è sbagliato e va buttato, altrimenti ogni
            // notifica successiva rifà lo stesso errore fino a scadenza.
            if ($source && $this->isOutsideServiceWindowError($errors)) {
                $this->reopenTemplateModeFor($source, $recipient);
            }

            app(WaFailureAlertService::class)->notify('wa_delivery', [
                'wa_id' => $waId,
                'lang' => $storedMessage->lang ?? 'it',
                'source' => $source,
                'error_type' => 'whatsapp_delivery_failed',
                'message' => $reason,
                'resource' => [
                    'recipient' => $recipient,
                    'message_type' => $storedMessage->type ?? null,
                    'errors' => $errors,
                ],
            ]);
        }
    }

    /** Riassume in una riga gli errori che WhatsApp allega alla ricevuta. */
    protected function describeStatusErrors($errors): string
    {
        if (!is_array($errors) || $errors === []) {
            return 'WhatsApp non ha consegnato il messaggio e non ha indicato un motivo.';
        }

        $parts = [];

        foreach ($errors as $error) {
            if (is_string($error)) {
                $parts[] = $error;
                continue;
            }

            $code = data_get($error, 'code');
            $title = data_get($error, 'title') ?: data_get($error, 'message');
            $detail = data_get($error, 'error_data.details');

            $parts[] = trim(
                ($code !== null ? "[$code] " : '')
                . (string) $title
                . ($detail ? ' — ' . $detail : '')
            );
        }

        return implode(' | ', array_filter($parts)) ?: 'Errore non specificato da WhatsApp.';
    }

    /**
     * La finestra di servizio si misura da quando il ristoratore ha risposto.
     * Il margine è di 23 ore e non di 24: fra il momento in cui salviamo il
     * timestamp e quello in cui WhatsApp fa i suoi conti c'è sempre uno
     * scarto, e sul filo di lana è lui a vincere con l'errore 131047.
     */
    protected function isLastResponseWaWithin24Hours($p): bool
    {
        $setting = Setting::on('dynamic')->where('name', 'wa')->first();

        if (!$setting) {
            return false;
        }

        $property = json_decode($setting->property, true);

        if (!is_array($property)) {
            return false;
        }

        $lastResponse = $property[$this->lastResponseKey($p)] ?? null;

        if (empty($lastResponse)) {
            return false;
        }

        try {
            return Carbon::parse($lastResponse)->greaterThanOrEqualTo(Carbon::now()->subHours(23));
        } catch (\Throwable $e) {
            Log::warning('(WC) Data ultima risposta illeggibile: ' . $e->getMessage());

            return false;
        }
    }

    protected function lastResponseKey($p): string
    {
        return ((int) $p === 0) ? 'last_response_wa_1' : 'last_response_wa_2';
    }

    /** Apre la connessione al database del singolo ristorante. */
    protected function connectToTenant($source): void
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
    }

    /**
     * Confronta i numeri per sole cifre: nelle impostazioni possono essere
     * scritti con +, spazi o prefisso omesso, e resterebbero diversi da quello
     * che manda WhatsApp pur essendo lo stesso telefono.
     */
    protected function matchWhatsappNumberIndex($number, array $numbers): ?int
    {
        $needle = preg_replace('/\D+/', '', (string) $number);

        if ($needle === '') {
            return null;
        }

        foreach ($numbers as $index => $candidate) {
            $haystack = preg_replace('/\D+/', '', (string) $candidate);

            if ($haystack === '') {
                continue;
            }

            if ($haystack === $needle) {
                return (int) $index;
            }

            // Uno dei due può essere senza prefisso internazionale. Sotto le
            // otto cifre il confronto per coda non distingue più niente.
            $shortest = min(strlen($haystack), strlen($needle));

            if ($shortest >= 8 && (str_ends_with($needle, $haystack) || str_ends_with($haystack, $needle))) {
                return (int) $index;
            }
        }

        return null;
    }

    /** Gli errori con cui WhatsApp dice che serve un template, non un testo libero. */
    protected function isOutsideServiceWindowError($errors): bool
    {
        if (!is_array($errors)) {
            return false;
        }

        foreach ($errors as $error) {
            if (in_array((int) data_get($error, 'code'), [131047, 131026, 470], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cancella il timestamp dell'ultima risposta del destinatario: al prossimo
     * invio il sistema sceglierà il template, che fuori finestra passa sempre.
     */
    protected function reopenTemplateModeFor($source, $recipient): void
    {
        try {
            $this->connectToTenant($source);

            $setting = Setting::on('dynamic')->where('name', 'wa')->first();

            if (!$setting) {
                return;
            }

            $property = json_decode($setting->property, true);

            if (!is_array($property)) {
                return;
            }

            $numbers = is_array($property['numbers'] ?? null) ? array_values($property['numbers']) : [];
            $index = $this->matchWhatsappNumberIndex($recipient, $numbers);

            if ($index === null) {
                return;
            }

            $key = $this->lastResponseKey($index);

            if (empty($property[$key])) {
                return;
            }

            $property[$key] = null;
            $setting->property = json_encode($property);
            $setting->save();

            Log::info('(WC) Finestra 24h azzerata dopo il rifiuto di WhatsApp', [
                'recipient' => $recipient,
                'key' => $key,
                'db' => $source->db_name ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('(WC) Non riesco ad azzerare la finestra 24h: ' . $e->getMessage());
        }
    }
    protected function updateLastResponseWa($c)
    {
        $setting = Setting::on('dynamic')->where('name', 'wa')->first();

        if (!$setting) {
            Log::warning('(WC) Impostazione wa assente: finestra 24h non aggiornata');

            return;
        }

        $property = json_decode($setting->property, true);

        if (!is_array($property)) {
            $property = [];
        }

        // toDateTimeString e non l'oggetto Carbon: è il formato che scrive il
        // backoffice, e nel JSON resta leggibile invece di diventare una data
        // ISO in UTC che sembra sfasata di due ore a chi apre le impostazioni.
        $property[$this->lastResponseKey($c)] = Carbon::now()->toDateTimeString();

        Log::info("update-Last-Response-Wa ==> {$c}");

        $setting->property = json_encode($property);
        $setting->save();
    }

}

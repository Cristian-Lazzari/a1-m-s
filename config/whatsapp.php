<?php

/**
 * Tutti i segreti WhatsApp/Meta arrivano dal .env: nel codice non ce n'è
 * nessuno. Il .env non va mai in git (vedi .env.example).
 */
return [
    // App Meta di Future Plus (Tech Provider): una sola per tutti i ristoranti.
    'app_id' => env('META_APP_ID'),
    // Serve anche a verificare la firma X-Hub-Signature-256 dei webhook.
    'app_secret' => env('META_APP_SECRET'),
    // Facebook Login for Business → configurazione "WhatsApp Embedded Signup".
    'embedded_signup_config_id' => env('META_ES_CONFIG_ID'),
    'graph_version' => env('META_GRAPH_VERSION', 'v24.0'),

    // Stringa scelta da noi e scritta uguale nell'app Meta (Webhook → Verifica token).
    'webhook_verify_token' => env('WA_WEBHOOK_VERIFY_TOKEN'),

    // Numero di piattaforma: quello che avvisa i ristoratori.
    'platform' => [
        'phone_number_id' => env('WA_PLATFORM_PHONE_NUMBER_ID'),
        'token' => env('WA_PLATFORM_TOKEN'),
    ],

    // Template creato su ogni numero di ristorante (vedi ReminderTemplate).
    'reminder_template' => 'promemoria_prenotazione',

    // Tentativi di consegna di un evento a un ristorante prima di arrendersi.
    'delivery_max_attempts' => 6,

    // Destinatario degli avvisi di errore.
    'alert_recipient' => env('WA_ALERT_RECIPIENT', 'info@future-plus.it'),
];

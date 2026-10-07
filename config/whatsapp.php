<?php

/**
 * App Meta di Future Plus come Tech Provider: una sola app per tutti i
 * ristoranti. I numeri si collegano con l'Embedded Signup.
 */
return [
    'app_id' => env('META_APP_ID'),
    'app_secret' => env('META_APP_SECRET'),
    // Facebook Login for Business → configurazione "WhatsApp Embedded Signup".
    'embedded_signup_config_id' => env('META_ES_CONFIG_ID'),
    'graph_version' => env('META_GRAPH_VERSION', 'v24.0'),

    // Template creato su ogni numero collegato (vedi App\Services\Whatsapp\ReminderTemplate).
    'reminder_template' => 'promemoria_prenotazione',
];

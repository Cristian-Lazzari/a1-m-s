<?php

return [
    'auto_mail' => 'Diese E-Mail wird automatisch vom System generiert, bitte antworten Sie nicht auf diese Nachricht',
    'Data_prenotata' => 'Reserviertes Datum',
    'Messaggio' => 'Nachricht',
    'Sala_prenota' => 'Reservierter Raum',
    'Numero_di_adulti' => 'Anzahl Erwachsene',
    'Numero_di_bambini' => 'Anzahl Kinder',
    'Indirizzo_per_la_consegna' => 'Lieferadresse',
    'Costo_della_consegna_a_domicilio' => 'Kosten der Hauslieferung',
    'Totale_carrello' => 'Warenkorb Gesamt',
    'modalita_consegna_asporto' => 'Liefermethode: Abholung bei :name',
    'contatta_tel_mail' => 'Kontaktieren Sie :name wenn Sie Ihre Reservierung ändern oder stornieren möchten:',
    'end_copy' => '© 2025 :name. Alle Rechte vorbehalten.',
    'Chiama' => 'Anrufen',
    'Visualizza_nella_Dashboard' => 'Im Dashboard anzeigen',
    'Prodotti_scelti' => 'Ausgewählte Produkte',
    'Prodotti_nel_menu' => 'Produkte im Menü',
    'Opzioni' => 'Optionen',
    'Ingredienti_extra' => 'Zusätzliche Zutaten',
    'Ingredienti_rimossi' => 'Entfernte Zutaten',

    'common' => [
        'menu' => 'Menü',
        'product' => 'Produkt',
    ],

    'emails' => [
        'order_summary' => 'Bestellübersicht',
        'call_name' => 'Rufen Sie :name an',
        'status_cancelled' => 'Abgesagt',
        'status_confirmed' => 'Bestätigt',
        'status_refunded' => 'Rückerstattung',
        'status_pending' => 'Ausstehend',
    ],

    'wa' => [
        'order_label'        => 'Die Bestellung wurde',
        'res_label'          => 'Die Reservierung wurde',
        'confirmed_word'     => 'bestätigt ✅',
        'cancelled_word'     => 'storniert ❌',
        'colleague'          => 'Ihr Kollege',
        'order_msg_confirmed' => 'Die Bestellung wurde von *Ihrem Kollegen* *bestätigt* ✅',
        'order_msg_cancelled' => 'Die Bestellung wurde von *Ihrem Kollegen* *storniert* ❌',
        'res_msg_confirmed'  => 'Die Reservierung wurde von *Ihrem Kollegen* *bestätigt* ✅',
        'res_msg_cancelled'  => 'Die Reservierung wurde von *Ihrem Kollegen* *storniert* ❌',
    ],

    'controllers' => [
        'orders' => [
            'accepted_title' => 'Wir bestätigen, dass Ihre Bestellung angenommen wurde.',
            'cancelled_title' => 'Wir bedauern, Ihnen mitteilen zu müssen, dass Ihre Bestellung storniert wurde.',
            'refund_subtitle' => 'Ihre Rückerstattung wird innerhalb von 5-10 Werktagen bearbeitet.',
        ],
        'reservations' => [
            'accepted_title_full' => 'Wir bestätigen, dass Ihre Reservierung angenommen wurde.',
            'cancelled_title' => 'Ihre Reservierung wurde storniert.',
        ],
    ],
];

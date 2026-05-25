<?php

return [
    'auto_mail' => 'Este correo se genera automáticamente por el sistema, por favor no respondas a este email',
    'Data_prenotata' => 'Fecha reservada',
    'Messaggio' => 'Mensaje',
    'Sala_prenota' => 'Sala reservada',
    'Numero_di_adulti' => 'Número de adultos',
    'Numero_di_bambini' => 'Número de niños',
    'Indirizzo_per_la_consegna' => 'Dirección de entrega',
    'Costo_della_consegna_a_domicilio' => 'Costo de entrega a domicilio',
    'Totale_carrello' => 'Total del carrito',
    'modalita_consegna_asporto' => 'Modo de entrega: recogida para llevar en :name',
    'contatta_tel_mail' => 'Contacta con :name si deseas cancelar o modificar tu reserva:',
    'end_copy' => '© 2025 :name. Todos los derechos reservados.',
    'Chiama' => 'Llamar',
    'Visualizza_nella_Dashboard' => 'Ver en el Dashboard',
    'Prodotti_scelti' => 'Productos elegidos',
    'Prodotti_nel_menu' => 'Productos en el menú',
    'Opzioni' => 'Opciones',
    'Ingredienti_extra' => 'Ingredientes extra',
    'Ingredienti_rimossi' => 'Ingredientes eliminados',

    'common' => [
        'menu' => 'Menú',
        'product' => 'Producto',
    ],

    'emails' => [
        'order_summary' => 'Resumen del pedido',
        'call_name' => 'Llama al :name',
        'status_cancelled' => 'Cancelado',
        'status_confirmed' => 'Confirmado',
        'status_refunded' => 'Reintegrado',
        'status_pending' => 'Pendiente',
    ],

    'wa' => [
        'order_label'        => 'El pedido ha sido',
        'res_label'          => 'La reserva ha sido',
        'confirmed_word'     => 'confirmado ✅',
        'cancelled_word'     => 'cancelado ❌',
        'colleague'          => 'tu compañero',
        'order_msg_confirmed' => 'El pedido ha sido *confirmado* ✅ por *tu compañero*',
        'order_msg_cancelled' => 'El pedido ha sido *cancelado* ❌ por *tu compañero*',
        'res_msg_confirmed'  => 'La reserva ha sido *confirmada* ✅ por *tu compañero*',
        'res_msg_cancelled'  => 'La reserva ha sido *cancelada* ❌ por *tu compañero*',
    ],

    'controllers' => [
        'orders' => [
            'accepted_title' => 'Confirmamos que su pedido ha sido aceptado.',
            'cancelled_title' => 'Lamentamos informarle que su pedido ha sido cancelado.',
            'refund_subtitle' => 'Su reembolso se procesará en un plazo de 5 a 10 días hábiles.',
        ],
        'reservations' => [
            'accepted_title_full' => 'Confirmamos que su reserva ha sido aceptada.',
            'cancelled_title' => 'Su reserva ha sido cancelada.',
        ],
    ],
];

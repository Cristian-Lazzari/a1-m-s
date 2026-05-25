<?php

return [
    'auto_mail' => 'このメールはシステムによって自動生成されています。返信しないでください。',
    'Data_prenotata' => '予約日',
    'Messaggio' => 'メッセージ',
    'Sala_prenota' => '予約された部屋',
    'Numero_di_adulti' => '大人の人数',
    'Numero_di_bambini' => '子供の人数',
    'Indirizzo_per_la_consegna' => '配送先住所',
    'Costo_della_consegna_a_domicilio' => '宅配料金',
    'Totale_carrello' => 'カート合計',
    'modalita_consegna_asporto' => '配送方法: :name でのテイクアウト受け取り',
    'contatta_tel_mail' => '予約を変更またはキャンセルする場合は :name に連絡してください:',
    'end_copy' => '© 2025 :name. 無断転載禁止。',
    'Chiama' => '電話する',
    'Visualizza_nella_Dashboard' => 'ダッシュボードで表示',
    'Prodotti_scelti' => '選択された商品',
    'Prodotti_nel_menu' => 'メニュー内の商品',
    'Opzioni' => 'オプション',
    'Ingredienti_extra' => '追加材料',
    'Ingredienti_rimossi' => '削除された材料',

    'common' => [
        'menu' => 'メニュー',
        'product' => '商品',
    ],

    'emails' => [
        'order_summary' => '注文概要',
        'call_name' => ':nameに電話してください',
        'status_cancelled' => 'キャンセル',
        'status_confirmed' => '確認済み',
        'status_refunded' => '返金済み',
        'status_pending' => '保留中',
    ],

    'wa' => [
        'order_label'        => 'ご注文は',
        'res_label'          => 'ご予約は',
        'confirmed_word'     => '確認されました ✅',
        'cancelled_word'     => 'キャンセルされました ❌',
        'colleague'          => 'あなたの同僚',
        'order_msg_confirmed' => 'ご注文は*あなたの同僚*によって *確認されました* ✅',
        'order_msg_cancelled' => 'ご注文は*あなたの同僚*によって *キャンセルされました* ❌',
        'res_msg_confirmed'  => 'ご予約は*あなたの同僚*によって *確認されました* ✅',
        'res_msg_cancelled'  => 'ご予約は*あなたの同僚*によって *キャンセルされました* ❌',
    ],

    'controllers' => [
        'orders' => [
            'accepted_title' => 'ご注文が受理されたことを確認いたしました。',
            'cancelled_title' => '誠に申し訳ございませんが、ご注文はキャンセルとなりました。',
            'refund_subtitle' => '返金処理は5～10営業日以内に行われます。',
        ],
        'reservations' => [
            'accepted_title_full' => 'ご予約が受理されたことを確認いたしました。',
            'cancelled_title' => 'ご予約はキャンセルされました',
        ],
    ],
];

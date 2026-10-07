<?php

namespace App\Services\Whatsapp;

/**
 * Il promemoria che template-pro-be manda X ore prima della prenotazione.
 *
 * L'ordine dei parametri è quello di ReservationReminderService::components()
 * in template-pro-be: nome, ristorante, giorno, ora, persone. I pulsanti sono
 * quick reply: il payload (CONFIRM:id / CANCEL:id) lo mette chi invia.
 * Categoria UTILITY: niente frasi promozionali, o Meta lo passa a MARKETING.
 */
class ReminderTemplate
{
    public static function definitions(): array
    {
        return [
            self::definition(
                'it',
                "Ciao {{1}}, ti ricordiamo la prenotazione da {{2}} per {{3}} alle {{4}}, {{5}} persone.\n\nSe non riesci più a venire, avvisaci con un tocco: libererai il tavolo per qualcun altro.",
                ['Luca', 'Trattoria da Mario', 'sabato 12 ottobre', '20:30', '4'],
                ['Confermo', 'Devo disdire'],
            ),
            self::definition(
                'en',
                "Hi {{1}}, this is a reminder of your booking at {{2}} on {{3}} at {{4}} for {{5}} people.\n\nIf you can no longer make it, let us know with one tap: you will free the table for someone else.",
                ['Luke', 'Trattoria da Mario', 'Saturday 12 October', '8:30 PM', '4'],
                ["I'll be there", 'Cancel booking'],
            ),
        ];
    }

    private static function definition(string $lang, string $body, array $example, array $buttons): array
    {
        return [
            'name' => config('whatsapp.reminder_template'),
            'language' => $lang,
            'category' => 'UTILITY',
            'components' => [
                [
                    'type' => 'BODY',
                    'text' => $body,
                    'example' => ['body_text' => [$example]],
                ],
                [
                    'type' => 'BUTTONS',
                    'buttons' => array_map(fn ($text) => ['type' => 'QUICK_REPLY', 'text' => $text], $buttons),
                ],
            ],
        ];
    }
}

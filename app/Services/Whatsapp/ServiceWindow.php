<?php

namespace App\Services\Whatsapp;

use App\Models\WaContact;

/**
 * La finestra di 24 ore di WhatsApp: dopo l'ultimo messaggio di una persona
 * a un nostro numero, per 24 ore le si può scrivere testo libero (gratis);
 * dopo servono i template. L'hub riceve tutti i webhook, quindi la tiene lui
 * per tutti: i ristoranti non devono più ricordarsela.
 */
class ServiceWindow
{
    /** 23 ore e non 24: fra il nostro orologio e quello di Meta c'è sempre uno scarto. */
    private const HOURS = 23;

    public function touch(string $phoneNumberId, string $waId): void
    {
        WaContact::updateOrCreate(
            ['phone_number_id' => $phoneNumberId, 'wa_id' => self::digits($waId)],
            ['last_inbound_at' => now()],
        );
    }

    public function isOpen(string $phoneNumberId, string $waId): bool
    {
        $contact = WaContact::where('phone_number_id', $phoneNumberId)
            ->where('wa_id', self::digits($waId))
            ->first();

        return $contact !== null && $contact->last_inbound_at->greaterThanOrEqualTo(now()->subHours(self::HOURS));
    }

    /** Meta ci ha appena detto che è chiusa: il nostro dato era sbagliato. */
    public function close(string $phoneNumberId, string $waId): void
    {
        WaContact::where('phone_number_id', $phoneNumberId)
            ->where('wa_id', self::digits($waId))
            ->delete();
    }

    public static function digits(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone);
    }
}

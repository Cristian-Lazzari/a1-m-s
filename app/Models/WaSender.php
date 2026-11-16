<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Numero di piattaforma di un partner (white label): avvisa i ristoratori dei
 * locali venduti con il suo marchio al posto del numero di Future Plus.
 */
class WaSender extends Model
{
    protected $fillable = [
        'name',
        'waba_id',
        'phone_number_id',
        'display_phone',
        'token',
    ];

    protected $casts = [
        'token' => 'encrypted',
    ];

    protected $hidden = ['token'];

    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }

    /** Il numero è uno dei nostri numeri di piattaforma (nostro o di un partner)? */
    public static function isPlatformNumber(string $phoneNumberId): bool
    {
        if ($phoneNumberId === (string) config('whatsapp.platform.phone_number_id')) {
            return true;
        }

        return self::where('phone_number_id', $phoneNumberId)->exists();
    }
}

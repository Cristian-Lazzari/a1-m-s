<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Un ristorante (un'installazione di template-pro-be).
 */
class Tenant extends Model
{
    protected $fillable = [
        'name',
        'app_url',
        'api_key_hash',
        'private_token',
        'active',
    ];

    protected $casts = [
        'private_token' => 'encrypted',
        'active' => 'boolean',
    ];

    protected $hidden = ['api_key_hash', 'private_token'];

    public function accounts()
    {
        return $this->hasMany(WaAccount::class);
    }

    public function deliveries()
    {
        return $this->hasMany(WaDelivery::class);
    }

    /** Numero del ristorante collegato e funzionante. */
    public function activeAccount(): ?WaAccount
    {
        return $this->accounts()->where('status', WaAccount::STATUS_ACTIVE)->latest('id')->first();
    }

    /**
     * Nuova chiave API: si restituisce in chiaro una volta sola, nel database
     * resta solo l'hash. Le chiavi sono casuali e lunghe, quindi basta SHA-256.
     */
    public static function generateApiKey(): array
    {
        $key = 'fp_' . Str::random(48);

        return [$key, self::hashApiKey($key)];
    }

    public static function hashApiKey(string $key): string
    {
        return hash('sha256', $key);
    }

    public static function findByApiKey(?string $key): ?self
    {
        if (! is_string($key) || $key === '') {
            return null;
        }

        return self::where('api_key_hash', self::hashApiKey($key))->where('active', true)->first();
    }
}

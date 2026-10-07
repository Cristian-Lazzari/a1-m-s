<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Numero WhatsApp di un ristorante, collegato con l'Embedded Signup.
 */
class WaAccount extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ERROR = 'error';

    protected $fillable = [
        'tenant_id',
        'waba_id',
        'phone_number_id',
        'business_id',
        'display_phone',
        'verified_name',
        'token',
        'pin',
        'status',
        'last_error',
    ];

    protected $casts = [
        'token' => 'encrypted',
        'pin' => 'encrypted',
    ];

    protected $hidden = ['token', 'pin'];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}

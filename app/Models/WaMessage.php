<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaMessage extends Model
{
    public const CHANNEL_PLATFORM = 'platform';
    public const CHANNEL_RESTAURANT = 'restaurant';

    protected $fillable = [
        'tenant_id',
        'channel',
        'phone_number_id',
        'recipient',
        'wa_message_id',
        'type',
        'ref',
        'status',
        'error',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}

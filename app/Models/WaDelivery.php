<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaDelivery extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'tenant_id',
        'endpoint',
        'payload',
        'status',
        'attempts',
        'next_attempt_at',
        'last_error',
    ];

    protected $casts = [
        'payload' => 'array',
        'next_attempt_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Una versione del modello del promemoria su un numero di un ristorante.
 */
class WaTemplate extends Model
{
    public const PENDING = 'PENDING';
    public const APPROVED = 'APPROVED';
    public const REJECTED = 'REJECTED';
    /** Sostituita da una versione più nuova prima di essere approvata. */
    public const SUPERSEDED = 'SUPERSEDED';

    protected $fillable = [
        'wa_account_id',
        'name',
        'language',
        'meta_id',
        'status',
        'category',
        'body',
        'params',
        'buttons',
        'reason',
        'current',
    ];

    protected $casts = [
        'params' => 'array',
        'buttons' => 'array',
        'current' => 'boolean',
    ];

    public function account()
    {
        return $this->belongsTo(WaAccount::class, 'wa_account_id');
    }

    /** Quello che il gestionale deve sapere: niente id di Meta, niente token. */
    public function toTenantArray(): array
    {
        return [
            'name' => $this->name,
            'language' => $this->language,
            'status' => $this->status,
            'category' => $this->category,
            'body' => $this->body,
            'params' => $this->params,
            'buttons' => $this->buttons,
            'reason' => $this->reason,
            'current' => $this->current,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

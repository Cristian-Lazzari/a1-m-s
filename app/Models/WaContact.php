<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaContact extends Model
{
    protected $fillable = ['phone_number_id', 'wa_id', 'last_inbound_at'];

    protected $casts = ['last_inbound_at' => 'datetime'];
}

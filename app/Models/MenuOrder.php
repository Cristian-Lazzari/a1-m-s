<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuOrder extends Model
{
    use HasFactory;
    protected $table = 'menu_order';
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'menu_id',
        'quantity',
        'choices',
    ];
}

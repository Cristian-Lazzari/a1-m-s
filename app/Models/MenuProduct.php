<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuProduct extends Model
{
    use HasFactory;
    protected $table = 'menu_product';
    protected $fillable = [
        'product_id',
        'menu_id',
        'label',
        'extra_price',
    ];
}

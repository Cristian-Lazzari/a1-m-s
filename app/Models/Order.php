<?php

namespace App\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;


    public function products()
    {
        return $this->belongsToMany(Product::class)
        ->withPivot('quantity', 'remove', 'add', 'option'); // Aggiungi qui tutti i campi che vuoi accedere

    }
}

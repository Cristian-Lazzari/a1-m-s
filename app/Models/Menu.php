<?php

namespace App\Models;

use App\Models\Order;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Menu extends Model
{
    use HasFactory;
    public function category() {
        return $this->belongsTo(Category::class);
    }
    public function orders(){
        return $this->belongsToMany(Order::class);
    }
    public function products(){
        return $this->belongsToMany(Product::class)
        ->withPivot('label', 'extra_price')->orderBy('created_at', 'asc'); // <- fondamentale!
    }
}

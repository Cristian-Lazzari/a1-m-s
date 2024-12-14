<?php

namespace App\Models;

use App\Models\Order;
use App\Models\Category;
use App\Models\Ingredient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;
    public function category() {
        return $this->belongsTo(Category::class);
    }
    public function ingredients() {
        return $this->belongsToMany(Ingredient::class);
    }

    public function orders(){
        return $this->belongsToMany(Order::class);
    }
}

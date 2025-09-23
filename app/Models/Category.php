<?php

namespace App\Models;

use App\Models\Menu;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;
    public function product() {    
        return $this->hasMany(Product::class)->orderBy('created_at', 'asc');
    }
    public function menu() {    
        return $this->hasMany(Menu::class);
    }
}

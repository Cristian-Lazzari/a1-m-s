<?php

namespace App\Models;

use App\Models\Month;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Date extends Model
{
    use HasFactory;

    protected $fillable = [
        'reserving', 
        'year',
        'month',
        'day',
        'day_w', 
        'time', 
        'availability', 
        'date_slot', 
        'status', 
        'visible',
    ];
    public $timestamps = false;

    // public function month() {
    //     // belongsTo si usa nel model della tabella che ha la chiave esterna, di conseguenza quella che sta dalla parte del molti
    //     return $this->belongsTo(Month::class);
    // }
}

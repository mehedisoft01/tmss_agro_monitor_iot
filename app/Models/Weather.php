<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Weather extends Model
{
    use HasFactory;
    protected $guarded = [];
    
    public function forecasts() {
        return $this->hasMany(Forecast::class, 'weather_id', 'id');
    }
}

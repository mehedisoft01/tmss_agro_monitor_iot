<?php

namespace App\Models\TmssIot;

use Illuminate\Database\Eloquent\Model;

class FarmerDevice extends Model
{
    protected $table = 'farmer_devices';

    protected $fillable = [
        'farmer_id',
        'device_id',
        'status',
    ];
}

<?php

namespace App\Models;

use App\Models\Scopes\ModelScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceStatusWarehouse extends Model
{
    use HasFactory;
    use ModelScopes;

    protected $table = 'device_statuses2_warehouse';
    protected $fillable = [
        'device_id',
        'online',
        'temperature',
        'humidity',
        'battery_percentage',
        'temp_alarm',
        'hum_alarm',
        'recorded_at'
    ];

}

<?php

namespace App\Models;

use App\Models\Scopes\ModelScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceStatus extends Model
{
    use HasFactory;
    use ModelScopes;

    protected $table = 'device_statuses_report';
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

    public function device()
    {
        return $this->belongsTo(Device::class, 'device_id', 'device_id');
    }

    protected $casts = [
        'recorded_at' => 'array',
    ];
}

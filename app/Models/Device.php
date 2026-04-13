<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'name', 'model', 'product_id', 'product_name', 'online','display_name','device_category','device_category',
        'lat', 'lon', 'local_key', 'time_zone','device_id','client_id','client_secret'
    ];

    public function statuses() {
        return $this->hasMany(DeviceStatus::class);
    }
}

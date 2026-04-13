<?php

namespace App\Models;

use App\Models\Scopes\ModelScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class SoilDevice extends Model
{
    use HasFactory;
    use ModelScopes;

    protected $table = 'soil_devices';


    protected $fillable = [
        'device_id',
        'device_name',
        'farmer_type',
        'device_location',
        'device_lat',
        'device_long',
    ];

    public function validate($input = [])
    {
        $validate = Validator::make($input, [

        ]);

        return $validate;
    }
}

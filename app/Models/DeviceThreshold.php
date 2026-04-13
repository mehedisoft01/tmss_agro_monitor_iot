<?php

namespace App\Models;
use App\Models\Scopes\ModelScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;



class DeviceThreshold extends model
{
    use ModelScopes;

    protected $table = 'device_thresholds';


    protected $fillable = [
        'device_category_id',
        'sensor_id',
        'min_value',
        'max_value',
        'remarks',
    ];

    public function validate($input)
    {
        $validate = Validator::make($input, [
            'device_category_id' => 'required',
            'sensor_id' => 'required',
            'min_value' => 'required',
            'max_value' => 'required',
            'remarks' => 'nullable',
        ]);

        return $validate;
    }

}

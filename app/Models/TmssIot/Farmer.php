<?php

namespace App\Models\TmssIot;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Farmer extends Model
{
    use HasFactory;

    protected $fillable =[
        'device_id',
        'name',
        'email',
        'phone_number',
        'address',
        'status',
    ];

    public function validate($input){

        $validate = Validator::make($input, [
            'name' => 'required',
        ]);

        return $validate;
    }
    public function device()
    {
        return $this->belongsTo(
            \App\Models\Device::class,
            'device_id',
            'device_id'
        );
    }


}

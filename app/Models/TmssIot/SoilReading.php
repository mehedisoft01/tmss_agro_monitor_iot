<?php

namespace App\Models\TmssIot;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class SoilReading extends Model
{
    use HasFactory;

    protected $fillable =['farmer_id', 'device_id', 'status',];

    public function validate($input){

        $validate = Validator::make($input, [

        ]);

        return $validate;
    }
}

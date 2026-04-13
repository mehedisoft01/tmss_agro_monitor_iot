<?php

namespace App\Models\Address;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Upazila extends Model
{
    protected $table = 'thana';
    protected $fillable = ['upazila_name','district_id','division_id','status'];


    public function validate($input = [])
    {
        $validate = Validator::make($input, [
            'upazila_name' => 'required',
            'district_id' => '',
            'division_id' => '',

        ]);

        return $validate;
    }
}

<?php

namespace App\Models\Address;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class District extends Model
{
    protected $table = 'district';
    protected $fillable = ['district_name','division_id','status'];


    public function validate($input = [])
    {
        $validate = Validator::make($input, [
            'district_name' => 'required',
            'division_id' => '',

        ]);

        return $validate;
    }
}

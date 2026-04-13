<?php

namespace App\Models\Address;

use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{

    protected $table = 'divison';
    protected $fillable = ['division_name','status'];


    public function validate($input = [])
    {
        $validate = Validator::make($input, [
            'division_name' => 'required',
        ]);

        return $validate;
    }

}

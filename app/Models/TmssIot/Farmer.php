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

}

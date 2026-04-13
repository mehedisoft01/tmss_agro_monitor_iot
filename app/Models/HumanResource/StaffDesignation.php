<?php

namespace App\Models\HumanResource;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class StaffDesignation extends Model
{
    protected $fillable = ['designation_name', 'status'];

    public function validate($data)
    {
        return Validator::make($data, [
            'designation_name' => 'required',
        ]);
    }
}

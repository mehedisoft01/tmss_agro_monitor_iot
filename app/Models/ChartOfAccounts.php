<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class ChartOfAccounts extends Model
{
    protected $table = 'chart_of_accounts';

    protected $fillable = [
        'name',
        'chart_code',
        'status',
    ];

    public function validate($input = [])
    {
        $validate = Validator::make($input, [
            'name' => 'required',
            'chart_code' => 'required',
        ]);

        return $validate;
    }
}

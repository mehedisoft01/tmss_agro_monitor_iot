<?php

namespace App\Models;

use App\Models\Scopes\ModelScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Configuration extends Model
{
    use HasFactory;
    use ModelScopes;

    protected $fillable = [
        'type',
        'key',
        'display_name',
        'value',
    ];

    public function validate($input)
    {
        $validate = Validator::make($input, [
            'type' => 'required',
            'key' => 'required',
            'display_name' => 'required',
            'value' => 'required',
        ]);

        return $validate;
    }
}

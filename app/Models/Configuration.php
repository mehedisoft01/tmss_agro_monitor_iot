<?php

namespace App\Models;

use App\Models\Scopes\ModelScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Configuration extends Model
{
    use HasFactory;
    protected $table = 'configurations';

    protected $fillable = [
        'key',
        'is_visible',
        'setting_type',
        'type',
        'value'
    ];

}

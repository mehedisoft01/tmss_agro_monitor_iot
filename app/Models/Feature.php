<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Feature extends Model
{
    use HasFactory;
    protected $table = 'features';
    protected $primaryKey = 'id';

    protected $fillable = ['title','images','status'];

    public function validate($input = [])
    {
        $validate = Validator::make($input, [
            'title' => '',
        ]);

        return $validate;
    }
}

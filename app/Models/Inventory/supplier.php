<?php

namespace App\Models\Inventory;

use App\Models\Scopes\ModelScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class supplier extends Model
{
    protected $table = 'suppliers';

    use ModelScopes;
    use HasFactory;

    protected $fillable = ['user_id','name','phone','email','address'];


    public function validate($input = [])
    {
        $validate = Validator::make($input,[
            'user_id' => '',
            'name' => 'required',
            'phone' => '',
            'email' => 'required',
            'address' => 'required',
            'status' => ''

        ]);

        return $validate;
    }
}

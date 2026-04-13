<?php

namespace App\Models\RBAC;

use App\Models\Scopes\ModelScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Role extends Model
{
    protected $table = 'roles';

    use ModelScopes;

    use HasFactory;

    protected $fillable = ['name', 'display_name'];

    public function validate($input = [])
    {
        $validate = Validator::make($input, [
            'name' => 'required',
        ]);

        return $validate;
    }
}

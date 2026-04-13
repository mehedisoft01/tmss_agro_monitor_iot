<?php

namespace App\Models\RBAC;

use App\Models\Scopes\ModelScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class RoleModules extends Model
{
    use ModelScopes;

    use HasFactory;
    protected $table = 'roles_modules';

    public function validate($input = [])
    {
        $validate = Validator::make($input, [
            'role_id' => 'required',
            'modules' => 'required|array|min:1',
        ]);

        return $validate;
    }
}

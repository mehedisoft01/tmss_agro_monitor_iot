<?php

namespace App\Models\RBAC;

use App\Models\Scopes\ModelScopes;
use function Carbon\this;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Validator;

class Module extends Model
{
    use HasFactory;

    use ModelScopes;

    use SoftDeletes;

    protected $table = 'modules';

    protected $hidden = ['created_at', 'updated_at'];

    protected $appends = ['checked'];

    protected $fillable = ['id', 'name','icon', 'parent_id', 'meta', 'link', 'component', 'is_visible'];

    public function validate($input){

        $validate = Validator::make($input, [
            'name' => 'required',
            'link' => 'required',
            'component' => 'required',
            'permissions' => 'array',
        ]);

        return $validate;
    }

    public function permissions()
    {
        return $this->hasMany(Permission::class, 'module_id', 'id');
    }

    public function submenus()
    {
        return $this->hasMany(Module::class, 'parent_id', 'id');
    }
    public function children()
    {
        return $this->hasMany(Module::class, 'parent_id', 'id');
    }

    public function role_modules()
    {
        return $this->hasMany(RoleModules::class, 'module_id','id')
            ->join('roles', 'roles_modules.role_id', '=', 'roles.id');
    }

    public function getCheckedAttribute()
    {
        return false;
    }

    public function getMetaAttribute($value)
    {
        return $value && is_string($value) ? json_decode($value) : [];
    }

    public function getLinkAttribute($value)
    {
        return ($value == '#') ? '' : $value;
    }

    public function getComponentAttribute($value)
    {
        return ($value == '#') ? '' : $value;
    }
}

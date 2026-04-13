<?php

namespace App\Models\RBAC;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $appends = ['actual'];

    protected $fillable = ['module_id', 'name', 'display_name'];

    public function role_permissions(){
        return $this->hasMany(RolePermission::class, 'permission_id', 'id');
    }

    public function getActualAttribute($value)
    {
        $parts = explode('.', $this->name);
        return isset($parts[1]) ? end($parts) : $this->name;
    }
}

<?php

namespace App\Models\RBAC;

use App\Models\Scopes\ModelScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    use ModelScopes;
    use HasFactory;
    
    protected $table = 'role_permissions';
}

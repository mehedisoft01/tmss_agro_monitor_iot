<?php

namespace App\Models\HumanResource;

use App\Models\Scopes\ModelScopes;
use App\Models\Inventory\Warehouse;
use App\Models\User;
use App\Traits\CheckWarehouseTrait;
use function Carbon\this;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Salesman extends Model
{
    protected $table = 'salesmen';

    use ModelScopes;
    use HasFactory;
    use CheckWarehouseTrait;

    protected $fillable = ['user_id','designation_id','name', 'status', 'phone', 'email', 'address', 'join_date', 'national_id', 'dob', 'salesman_code', 'warehouse_id','photo','signature','official_sill'];

    public function validate($input = [])
    {
        $validate = Validator::make($input, [
            'name' => 'required',
            'phone' => 'required',
            'address' => 'required',
        ]);

        return $validate;
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }
    public function user() {
        return $this->hasOne(User::class, 'salesman_id');
    }

    public function files()
    {
        return $this->hasMany(SalesmanFile::class,'salesman_id','id');
    }

    public function designation()
    {
        return $this->belongsTo(StaffDesignation::class, 'designation_id');
    }
}

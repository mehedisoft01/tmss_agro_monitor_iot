<?php

namespace App\Models\HumanResource;

use App\Models\Address\District;
use App\Models\Address\Division;
use App\Models\Address\Upazila;
use App\Models\Inventory\Warehouse;
use App\Models\ProductManagement\Product;
use App\Models\Scopes\ModelScopes;
use App\Traits\CheckWarehouseTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AssignTerritory extends Model
{
    protected $table = 'assign_territories';

    use ModelScopes;
    use HasFactory;
    use CheckWarehouseTrait;

    protected $fillable = ['user_id','salesman_id', 'target_start_date', 'target_end_date', 'target_amount','division_id','district_id','upazila_id','area', 'achieved_amount', 'achievement_percent', 'remarks', 'status', 'assigned_date', 'warehouse_id'];

    public function validate($input = [])
    {
        $validate = Validator::make($input, [
            'salesman_id' => 'required',
            'warehouse_id' => '',
            'target_start_date' => 'required',
            'target_end_date' => 'required',
            'target_amount' => '',
            'assigned_date' => 'required',
        ]);

        return $validate;
    }
    public function assign_target()
    {
        return $this->hasMany(TargetProduct::class, 'assing_territories_id', 'id');
    }
    public function sales_man()
    {
        return $this->belongsTo(Salesman::class, 'salesman_id', 'id');
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }
    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function upazila()
    {
        return $this->belongsTo(Upazila::class, 'upazila_id');
    }
}

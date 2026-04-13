<?php

namespace App\Models\HumanResource;

use App\Models\Scopes\ModelScopes;
use App\Models\Inventory\Warehouse;
use App\Traits\CheckWarehouseTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Salary extends Model
{
    protected $table = 'salaries';

    use ModelScopes;
    use HasFactory;
    use CheckWarehouseTrait;

    protected $fillable = ['user_id','salesman_id', 'salesman_code', 'status', 'basic_salary', 'target_type', 'sales_target_bonus', 'target_loss', 'warehouse_id'];

    public function validate($input = [])
    {
        $validate = Validator::make($input, [
            'salesman_id' => 'required',
            'warehouse_id' => 'required',
            'basic_salary' => 'required',
        ]);

        return $validate;
    }
    public function sales_man()
    {
        return $this->belongsTo(Salesman::class, 'salesman_id', 'id');
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }
}

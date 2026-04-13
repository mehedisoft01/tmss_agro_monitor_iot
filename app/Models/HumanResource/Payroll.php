<?php

namespace App\Models\HumanResource;

use App\Models\Scopes\ModelScopes;
use App\Traits\CheckWarehouseTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payroll extends Model
{
    protected $table = 'payrolls';

    use ModelScopes;
    use HasFactory;
    use CheckWarehouseTrait;

    protected $fillable = ['user_id','warehouse_id','salesman_id',';allowance','over_time','dp_commission','mrp_commission', 'bonus','late','attendance',
'netpay','salesman_code', 'status', 'basic_salary', 'target_bonus', 'target_loss', 'gross_salary', 'net_salary', 'tax', 'salary_month'];

    public function validate($input = [])
    {
        $validate = Validator::make($input, [
            'salesman_id' => 'required',
            'salary_month' => 'required',
            'basic_salary' => 'required',
//            'target_bonus' => 'required',
//            'net_salary' => 'required',
        ]);

        return $validate;
    }

    public function salesman()
    {
        return $this->belongsTo(Salesman::class, 'salesman_id', 'id');
    }
}

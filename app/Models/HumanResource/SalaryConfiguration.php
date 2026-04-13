<?php

namespace App\Models\HumanResource;

use App\Models\Scopes\ModelScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class SalaryConfiguration extends Model
{
    use ModelScopes;
    use HasFactory;

    protected $fillable = [
        'salesman_id',
        'basic_salary',
        'daily_salary',
        'hourly_salary',
        'allowance',
        'is_salesman',
        'is_commission_applicable',
    ];
    public function validate($input = [])
    {
        $validate = Validator::make($input, [

        ]);

        return $validate;
    }
    public function salesman()
    {
        return $this->belongsTo(Salesman::class, 'salesman_id');
    }
}

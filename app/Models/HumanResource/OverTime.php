<?php

namespace App\Models\HumanResource;

use App\Models\Scopes\ModelScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class OverTime extends Model
{
    use ModelScopes;
    use HasFactory;

    protected $fillable = [
        'salesman_id',
        'ot_date',
        'over_time_hours',
        'over_time_amount',
        'late_hours',
        'reason',
    ];
    public function validate($input = [])
    {
        $validate = Validator::make($input, [

        ]);

        return $validate;
    }
    public function salaryConfig()
    {
        return $this->hasOne(SalaryConfiguration::class, 'salesman_id', 'id');
    }
    public function salesman()
    {
        return $this->belongsTo(Salesman::class, 'salesman_id');
    }
}

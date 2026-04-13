<?php

namespace App\Models;

use App\Models\Scopes\ModelScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Holiday extends Model
{
    protected $table = 'holiday_configurations';
    use ModelScopes;
    use HasFactory;

    protected $fillable = ['holiday_type', 'holiday_days','holiday_date','holiday_year','remarks','status'];
    protected $appends = ['day_name'];

    public function getDayNameAttribute($value)
    {
        return date('l', strtotime($this->attributes['holiday_days']));
    }
    public function validate($input = [])
    {
        $validate = Validator::make($input, [
            'holiday_type' => 'required',
        ]);
        return $validate;
    }
    public function getHolidayDateAttribute($value)
    {
        if (is_null($value)) {
            return null;
        }
        return \Carbon\Carbon::parse($value)->format('d/m/Y');
    }
}

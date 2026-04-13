<?php

namespace App\Models\HumanResource;

use App\Models\Scopes\ModelScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Attendance extends Model
{
    use ModelScopes;
    use HasFactory;

    protected $fillable = [
        'salesman_id',
        'attendance_date',
        'status'
    ];
    public function validate($input = [])
    {
        $validate = Validator::make($input, [

        ]);

        return $validate;
    }
}

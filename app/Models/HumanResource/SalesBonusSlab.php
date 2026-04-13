<?php

namespace App\Models\HumanResource;

use App\Models\Scopes\ModelScopes;
use App\Traits\CheckWarehouseTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalesBonusSlab extends Model
{
    protected $table = 'sales_bonus_slabs';

    use ModelScopes;
    use HasFactory;
    use CheckWarehouseTrait;

    protected $fillable = ['user_id','warehouse_id','slab_min_percent', 'status', 'slab_max_percent', 'bonus_amount', 'bonus_type'];

    public function validate($input = [])
    {
        $validate = Validator::make($input, [
            'slab_min_percent' => 'required',
            'slab_max_percent' => 'required',
            'bonus_amount' => 'required',
            'bonus_type' => 'required',
        ]);

        return $validate;
    }
}

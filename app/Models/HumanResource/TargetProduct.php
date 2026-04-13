<?php

namespace App\Models\HumanResource;

use App\Models\ProductManagement\Product;
use App\Models\Scopes\ModelScopes;
use App\Traits\CheckWarehouseTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class TargetProduct extends Model
{
    protected $table = 'assign_target_products';
    use ModelScopes;
    use HasFactory;
    use CheckWarehouseTrait;

    protected $fillable = [
        'assing_territories_id',
        'salesman_id',
        'product_id',
        'target_qty',
        'target_type',
        'from_amount',
        'to_amount',
        'commission'];

    public function validate($input = [])
    {
        $validate = Validator::make($input, [
            'salesman_id' => 'required',

        ]);

        return $validate;
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}

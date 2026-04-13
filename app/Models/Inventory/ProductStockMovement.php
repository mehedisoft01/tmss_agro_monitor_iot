<?php

namespace App\Models\Inventory;

use App\Models\ProductManagement\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class ProductStockMovement extends Model
{
    protected $fillable = [
        'user_id',
        'dealer_id',
        'product_id',
        'warehouse_id',
        'shipping_cost',
        'ref_id',
        'type',
        'quantity',
        'note',
        'status',
    ];

    public function validate($input){

        $validate = Validator::make($input, [
            'product_id'        => 'required',
            'warehouse_id'      => 'required',
            'ref_id'     => 'required',
            'type'     => 'required',
            'quantity'     => 'required',
            'note'     => 'required',
        ]);

        return $validate;
    }

    public function product(){
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function warehouse(){
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
}

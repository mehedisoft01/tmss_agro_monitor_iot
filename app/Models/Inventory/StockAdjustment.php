<?php

namespace App\Models\Inventory;

use App\Models\ProductManagement\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class StockAdjustment extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'warehouse_id',
        'serial_id',
        'quantity',
        'adjust_status',
        'reason',
        'date',
    ];

    public function validate($input){

        $validate = Validator::make($input, [
            'product_id'      => 'required',
            'quantity'        => 'required',
            'adjust_status'   => 'required',
            'reason'          => 'nullable',
        ]);

        return $validate;
    }

    public function product(){
        return $this->belongsTo(Product::class, 'product_id', 'id')
            ->select('id', 'product_name', 'product_code');
    }

    public function stock()
    {
        return $this->hasOne(Stock::class, 'product_id', 'product_id')
            ->select('id', 'product_id', 'current_stock');
    }

    public function warehouse(){
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id')
            ->select('id', 'warehouse_name');
    }
}

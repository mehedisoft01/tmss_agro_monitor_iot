<?php

namespace App\Models\Sales;

use App\Models\ProductManagement\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class OrderItem extends Model
{
    protected $fillable = [
        'customer_order_id','serial_group_id', 'dealer_order_id', 'product_id', 'product_code', 'warehouse_id', 'quantity', 'unit_price', 'total_price', 'remarks','status'
    ];


    public function validate($input = [])
    {
        $validate = Validator::make($input,[

            'product_id'   => 'required',
            'product_code'   => '',
            'warehouse_id'   => '',
            'quantity'     => '',
            'unit_price'   => '',
            'total_price'  => '',
            'remarks'      => '',
            'status'       => '',

        ]);

        return $validate;
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'customer_order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}

<?php

namespace App\Models\Inventory;

use App\Models\ProductManagement\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class StockTransfer extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'from_warehouse_id',
        'to_warehouse_id',
        'transfer_qty',
        'shipping_cost',
        'subtotal',
        'serials_item',
        'source',
        'destination',
        'transfer_status',
        'transfer_date',
        'attachment',
        'note',
        'total',
    ];


    public function validate($input){

        $validate = Validator::make($input, [
            'product_id'        => 'required|integer',
            'from_warehouse_id' => 'required|integer',
            'to_warehouse_id'   => 'required|integer|different:from_warehouse_id',
            'transfer_qty'      => 'required|numeric|min:1',
            'transfer_date'     => 'required|date',
        ]);

        return $validate;
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }
}

<?php

namespace App\Models\Inventory;

use App\Models\ProductManagement\Product;
use App\Models\ProductSerial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class StockPurchase extends Model
{

    protected $fillable = [
        'purchase_date',
        'user_id',
        'warehouse_id',
        'product_id',
        'serial_no',
        'stock_status',
        'note',
        'purchase_qty',
        'unit_cost',
        'tax',
        'shipping_cost',
        'selling_price',
        'dealer_price',
        'sub_total',
        'attachment',
    ];

    public function product(){
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function warehouse(){
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
    public function serials() {
        return $this->hasMany(ProductSerial::class, 'stock_id');
    }
}

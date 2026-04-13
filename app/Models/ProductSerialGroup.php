<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSerialGroup extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'warehouse_id',
        'date',
        'attachment',
        'quantity',
        'cost_price',
        'dealer_price',
        'selling_price',
        'stock_id'
    ];

    public function serials()
    {
        return $this->hasMany(ProductSerial::class, 'serial_group_id');
    }
}

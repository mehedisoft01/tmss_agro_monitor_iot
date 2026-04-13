<?php

namespace App\Models;

use App\Models\ProductManagement\Product;
use Illuminate\Database\Eloquent\Model;

class ProductSerial extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'serial_group_id',
        'serial',
        'status',
        'stock_id',
    ];

    public function group()
    {
        return $this->belongsTo(ProductSerialGroup::class, 'serial_group_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function serialGroup()
    {
        return $this->belongsTo(ProductSerialGroup::class);
    }


}

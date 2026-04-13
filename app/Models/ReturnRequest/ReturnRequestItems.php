<?php

namespace App\Models\ReturnRequest;

use App\Models\ProductManagement\Product;
use Illuminate\Database\Eloquent\Model;

class ReturnRequestItems extends Model
{
    protected $fillable = [
        'return_request_id',
        'product_id',
        'quantity',
        'unit_price',
        'serial_no',
        'total_price',
        'condition',
    ];


}

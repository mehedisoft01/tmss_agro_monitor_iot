<?php

namespace App\Models\Inventory;

use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use App\Models\ProductSerial;
use App\Models\ProductSerialGroup;
use App\Models\User;
use App\Traits\CheckWarehouseTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Stock extends Model
{
    use CheckWarehouseTrait;
    protected $fillable = [
        'user_id',
        'product_id',
        'warehouse_id',
        'serial_no',
        'unit_cost',
        'shipping_cost',
        'selling_price',
        'dealer_price',
        'tax',
        'minimum_stock',
        'current_stock',
    ];

    public function validate($input){

        $validate = Validator::make($input, [
            'product_id'    => 'required',
            'warehouse_id'  => 'required',
            'current_stock' => 'required',
            'unit_cost'     => 'required',
            'selling_price' => 'required',
            'dealer_price'  => 'required',
            'tax'           => 'nullable',
            'shipping_cost' => 'nullable',

        ]);

        return $validate;
    }


    public function product(){
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function warehouse(){
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function parentCategory() {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    public function subCategory() {
        return $this->belongsTo(ProductCategory::class, 'sub_category_id');
    }

    public function subSubCategory() {
        return $this->belongsTo(ProductCategory::class, 'sub_sub_category_id');
    }
    public function user()
{
    return $this->belongsTo(User::class, 'user_id');
}
    public function serials()
    {
        return $this->hasMany(ProductSerial::class, 'stock_id');
    }
}

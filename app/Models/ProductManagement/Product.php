<?php

namespace App\Models\ProductManagement;

use App\Models\Inventory\ProductStockMovement;
use App\Models\Inventory\Stock;
use App\Models\ProductSerial;
use App\Models\Scopes\ModelScopes;
use App\Traits\CheckWarehouseTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Product extends Model
{
    protected $table = 'products';

    use ModelScopes;
    use HasFactory;
    use CheckWarehouseTrait;

    protected $fillable = ['user_id', 'warehouse_id', 'product_name', 'size', 'model', 'net_weight', 'product_code', 'parent_id', 'sub_category_id', 'sub_sub_category_id', 'description', 'brand_id', 'tax_method', 'product_unit_id', 'sale_unit_id', 'cost_price', 'markup_percentage', 'selling_price', 'dealer_price', 'tax_amount', 'stock_quantity', 'image', 'custom_document_file','alert_quantity','app_download_link','device_connection_code'];


    public function validate($input = [])
    {
        $validate = Validator::make($input, [
            'parent_id' => 'required',
            'product_name' => 'required',
//            'product_code' => 'required',
            'tax_method' => 'required',
        ]);

        return $validate;
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function parentCategory()
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    public function subCategory()
    {
        return $this->belongsTo(ProductCategory::class, 'sub_category_id');
    }

    public function subSubCategory()
    {
        return $this->belongsTo(ProductCategory::class, 'sub_sub_category_id');
    }

    public function brand()
    {
        return $this->belongsTo(ProductBrand::class, 'brand_id');
    }

    public function productUnit()
    {
        return $this->belongsTo(ProductUnit::class, 'product_unit_id');
    }

    public function saleUnit()
    {
        return $this->belongsTo(ProductUnit::class, 'sale_unit_id');
    }


    protected $casts = [
        'image' => 'array',
        'custom_document_file' => 'array',
    ];


    public function productStock()
    {
        return $this->hasMany(Stock::class);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class, 'product_id', 'id');
    }

    public function stockMovements()
    {
        return $this->hasMany(ProductStockMovement::class, 'product_id', 'id');
    }

    public function serials()
    {
        return $this->hasMany(ProductSerial::class, 'product_id');
    }


    protected static function booted()
    {
        static::deleting(function ($product) {

            Stock::where('product_id', $product->id)->delete();

            ProductStockMovement::where('product_id', $product->id)->delete();

            ProductDocument::where('product_id', $product->id)->delete();

        });
    }
}

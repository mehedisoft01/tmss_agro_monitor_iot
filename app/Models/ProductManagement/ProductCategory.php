<?php

namespace App\Models\ProductManagement;

use App\Models\Scopes\ModelScopes;
use App\Traits\CheckWarehouseTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class ProductCategory extends Model
{
    protected $table = 'product_categories';

    use ModelScopes;
    use HasFactory;
    use CheckWarehouseTrait;

    protected $fillable = ['user_id','warehouse_id','parent_id','category_name','tag','description','image'];


    public function validate($input = [])
    {
        $validate = Validator::make($input, [
            'user_id' => '',
            'warehouse_id' => '',
            'parent_id' => '',
            'category_name' => 'required',
            'tag' => '',
            'description' => '',
            'image' => '',
            'status' => ''
        ]);

        return $validate;
    }

    public function parent_category()
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    public function subcategories()
    {
        return $this->hasMany(ProductCategory::class,'parent_id')->with('subcategories');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

}

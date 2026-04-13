<?php

namespace App\Models\ProductManagement;

use App\Models\Scopes\ModelScopes;
use App\Traits\CheckWarehouseTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class ProductBrand extends Model
{
    protected $table = 'brands';

    use ModelScopes;
    use HasFactory;
    use CheckWarehouseTrait;

    protected $fillable = ['user_id','warehouse_id','brand_name','brand_code','description','brand_logo'];

    public function validate($input = [])
    {
        $validate = Validator::make($input,[
            'user_id' => '',
            'brand_name' => 'required',
            'brand_code' => 'required',
            'description' => '',
            'brand_logo' => '',
            'status' => ''

        ]);

        return $validate;
    }
}

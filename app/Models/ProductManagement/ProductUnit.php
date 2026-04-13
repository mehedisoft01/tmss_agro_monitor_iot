<?php

namespace App\Models\ProductManagement;

use App\Models\Scopes\ModelScopes;
use App\Traits\CheckWarehouseTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class ProductUnit extends Model
{
    protected $table = 'product_units';

    use ModelScopes;
    use HasFactory;
    use CheckWarehouseTrait;

    protected $fillable = ['user_id','warehouse_id','name','symbol'];

    public function validate($input = [])
    {
        $validate = Validator::make($input,[
            'user_id' => '',
            'warehouse_id' => '',
            'name' => 'required',
            'symbol' => '',
            'status' => ''
        ]);

        return $validate;
    }
}

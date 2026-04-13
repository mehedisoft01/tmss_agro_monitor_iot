<?php

namespace App\Models\ProductManagement;

use App\Models\Scopes\ModelScopes;
use App\Traits\CheckWarehouseTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class ProductDocument extends Model
{
    protected $table = 'product_documents';

    use ModelScopes;
    use HasFactory;
    use CheckWarehouseTrait;

    protected $fillable = ['user_id','document_name','product_id','document_type','file_path'];


    public function validate($input = [])
    {
        $validate = Validator::make($input, [
            'user_id' => '',
            'document_name' => 'required',
            'product_id' => 'required',
            'document_type' => 'required',
            'file_path' => '',
            'status' => ''
        ]);

        return $validate;
    }

    protected $casts = [
        'image' => 'array',
        'custom_document_file' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class,'product_id');
    }
}

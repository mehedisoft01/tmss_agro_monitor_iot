<?php

namespace App\Models\Sales;

use App\Models\ProductManagement\Product;
use App\Models\ProductSerial;
use App\Models\Scopes\ModelScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use ModelScopes;
    use HasFactory;


    protected $fillable = ['invoice_id', 'product_id', 'warehouse_id', 'product_code', 'quantity', 'unit_price', 'total_price', 'remarks', 'status','serial_group_id'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class,'product_id');
    }

    public function productSerials()
    {
        return $this->hasMany(ProductSerial::class, 'invoice_id','invoice_id');
    }
}

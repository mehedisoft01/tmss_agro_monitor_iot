<?php

namespace App\Models\ProductManagement;

use App\Models\Dealer\Dealer;
use App\Models\Scopes\ModelScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Pricing extends Model
{
    protected $table = 'pricings';

    use ModelScopes;
    use HasFactory;

    protected $fillable = ['user_id','product_id','dealer_id','markup_percentage','sales_price','cost_price','dealer_price','effective_from','effective_to','pricing_type'];

    public function validate($input = [])
    {
        $validate = Validator::make($input,[
            'user_id' => '',
            'product_id' => 'required',
            'pricing_type' => 'required',
            'dealer_id' => '',
            'markup_percentage' => 'required',
            'sales_price' => 'required',
            'dealer_price' => 'required',
            'effective_from' => 'required',
            'effective_to' => '',
            'status' => ''

        ]);

        return $validate;
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function dealer(){
        return $this->belongsTo(Dealer::class,'dealer_id');
    }
}

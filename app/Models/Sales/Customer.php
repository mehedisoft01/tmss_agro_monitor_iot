<?php

namespace App\Models\Sales;

use App\Models\Address;
use App\Models\Dealer\Dealer;
use App\Models\Inventory\Warehouse;
use App\Models\Scopes\ModelScopes;
use App\Traits\CheckWarehouseTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Customer extends Model
{
    protected $table = 'customers';

    use ModelScopes;
    use HasFactory;
    use CheckWarehouseTrait;

    protected $fillable = ['user_id', 'warehouse_id', 'name', 'phone', 'email',];


    public function validate($input = [])
    {
        $validate = Validator::make($input, [
            'user_id' => '',
            'name' => 'required',
            'phone' => '',
            'email' => '',
            'address' => '',
            'status' => ''

        ]);

        return $validate;
    }

    public function dealer()
    {
        return $this->belongsTo(Dealer::class,'dealer_id','id');
    }

    public function address()
    {
        return $this->hasOne(Address::class, 'customer_id', 'id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class,'warehouse_id','id');
    }
}


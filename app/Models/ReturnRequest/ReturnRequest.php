<?php

namespace App\Models\ReturnRequest;

use App\Models\Dealer\Dealer;
use App\Models\Sales\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class ReturnRequest extends Model
{
    protected $fillable = [
        'return_no',
        'dealer_id',
        'invoice_id',
        'return_date',
        'status',
        'total_qty',
        'total_amount',
        'created_by',
        'approved_by',
    ];

    public function validate($input){

        $validate = Validator::make($input, [
            'dealer_id'      => 'required',
            'invoice_id'     => 'required',
            'return_date'     => 'required',
            'total_qty'     => 'required',
            'total_amount'     => 'required',

        ]);

        return $validate;
    }

    public function dealer()
    {
        return $this->belongsTo(Dealer::class, 'dealer_id', 'id');
    }

    public function items()
    {
        return $this->hasOne(ReturnRequestItems::class, 'return_request_id', 'id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'id');
    }

}

<?php

namespace App\Models\Sales;

use App\Models\Accounting\DealerLedger;
use App\Models\Address\District;
use App\Models\Address\Division;
use App\Models\Address\Upazila;
use App\Models\Dealer\Dealer;
use App\Models\Sales\Customer;
use App\Models\Sale;
use App\Models\Scopes\ModelScopes;
use App\Models\User;
use App\Traits\CheckWarehouseTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Order extends Model
{
    use ModelScopes;
    use HasFactory;
    use CheckWarehouseTrait;

    protected $table = 'orders';

    protected $fillable = [
        'order_no', 'customer_id', 'dealer_id', 'warehouse_id', 'order_date', 'status',
        'total_qty', 'total_amount', 'discount', 'net_amount', 'remarks', 'created_by', 'order_approved_by', 'invoice_id',
        'order_status','attachment','division_id','district_id','upazila_id','area','payment_status','payment_confirmed_by'
    ];

    public function validate($input = [])
    {
        $validate = Validator::make($input,[

            'customer_id'     => '',
            'order_date'    => '',
            'status'        => '',
            'total_qty'     => '',
            'total_amount'  => '',
            'discount'      => '',
            'net_amount'    => '',
            'remarks'       => '',

        ]);

        return $validate;
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'dealer_order_id')
            ->orWhere('customer_order_id', $this->id);
    }

    public function dealerItems()
    {
        return $this->hasMany(OrderItem::class, 'dealer_order_id');
    }

    public function customerItems()
    {
        return $this->hasMany(OrderItem::class, 'customer_order_id', 'id');
    }

    public function invoice()
    {
        return $this->belongsTo(Sale::class, 'invoice_id');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approve()
    {
        return  $this->belongsTo(User::class, 'approved_by', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class,'customer_id');
    }

    public function dealer()
    {
        return $this->belongsTo(Dealer::class,'dealer_id');
    }
    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id', 'id');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id', 'id');
    }

    public function upazila()
    {
        return $this->belongsTo(Upazila::class, 'upazila_id', 'id');
    }

    public function paymentConfirmedByUser()
    {
        return $this->belongsTo(User::class, 'payment_confirmed_by');
    }

    public function orderApprovedByUser()
    {
        return $this->belongsTo(User::class, 'order_approved_by');
    }


    protected static function booted()
    {
        static::deleting(function ($order) {
            $order->items()->delete();
            DealerLedger::where('order_id', $order->id)->delete();
        });
    }

}

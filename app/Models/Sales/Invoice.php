<?php

namespace App\Models\Sales;

use App\Models\Accounting\DealerLedger;
use App\Models\Accounting\PaymentCollection;
use App\Models\Dealer\Dealer;
use App\Models\HumanResource\Salesman;
use App\Models\Inventory\ProductStockMovement;
use App\Models\Inventory\Warehouse;
use App\Models\ProductSerial;
use App\Models\ReturnRequest\ReturnRequest;
use App\Models\Scopes\ModelScopes;
use App\Models\User;
use App\Traits\CheckWarehouseTrait;
use function Carbon\this;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use ModelScopes;
    use HasFactory;
    use CheckWarehouseTrait;

    protected $fillable = ['invoice_no','order_no','order_id','user_id','warehouse_id','customer_id','dealer_id','salesman_id','invoice_date',
                           'total_qty', 'total_amount','discount', 'net_amount', 'created_by', 'approved_by', 'status','invoice_status','payment_confirmed_by',
                           'division_id','district_id','order_approved_by','delivery_date'];


    public function items()
    {
        return $this->hasMany(InvoiceItem::class,'invoice_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class,'customer_id','id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class,'order_id');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by','id');
    }

    public function dealer()
    {
        return $this->belongsTo(Dealer::class,'dealer_id');
    }

    public function salesman()
    {
        return $this->belongsTo(Salesman::class,'salesman_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class,'warehouse_id');
    }

    public function returnRequests()
    {
        return $this->hasMany(ReturnRequest::class, 'invoice_id', 'id');
    }

    public function paymentConfirmedByUser()
    {
        return $this->belongsTo(User::class, 'payment_confirmed_by');
    }

    public function approvedByUser()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function productSerials()
    {
        return $this->hasMany(ProductSerial::class, 'invoice_id');
    }

    public function orderApprovedByUser()
    {
        return $this->belongsTo(User::class, 'order_approved_by');
    }


    protected static function booted()
    {
        static::deleting(function ($invoice) {

            InvoiceItem::where('invoice_id', $invoice->id)->delete();

            PaymentCollection::where('invoice_id', $invoice->id)->delete();

            DealerLedger::where('invoice_id', $invoice->id)->delete();

            ProductStockMovement::where('ref_id', $invoice->id)
                ->where('type', 2)
                ->delete();
        });
    }

}

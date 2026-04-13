<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class DealerLedger extends Model
{
    protected $table = 'dealer_ledgers';

    protected $fillable = [
        'dealer_id',
        'transaction_type',
        'type',
        'order_id',
        'invoice_id',
        'customer_id',
        'payment_id',
        'date',
        'debit',
        'credit',
        'balance',
        'remarks',
    ];
}

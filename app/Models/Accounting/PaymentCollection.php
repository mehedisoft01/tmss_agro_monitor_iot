<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class PaymentCollection extends Model
{
    protected $table = 'payment_collections';

    protected $fillable = [
        'invoice_id',
        'dealer_id',
        'user_id',
        'receipt_no',
        'customer_id',
        'date',
        'amount',
        'payment_mode',
        'reference_no',
        'remarks',
    ];
    public function validate($input = [])
    {
        $validate = Validator::make($input, [
            'invoice_id' => 'required',
            'date' => 'required',
            'amount' => 'required',
        ]);

        return $validate;
    }
    protected $appends = ['due_amount'];

    public function getDueAmountAttribute()
    {
        $totals = DealerLedger::where('invoice_id', $this->invoice_id)
            ->selectRaw("
            SUM(CASE WHEN transaction_type = 1 THEN COALESCE(debit,0) ELSE 0 END) as total_debit,
            SUM(CASE WHEN transaction_type = 2 THEN COALESCE(credit,0) ELSE 0 END) as total_credit
        ")
            ->first();

        $totalDebit  = $totals->total_debit  ?? 0;
        $totalCredit = $totals->total_credit ?? 0;
        $dueAmount = (float) $totalDebit - (float) $totalCredit;

        return $dueAmount;
    }
}

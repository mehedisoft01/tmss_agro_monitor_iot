<?php


namespace App\Services;


use App\Models\DealerLedger;
use App\Models\PaymentCollection;
use App\Models\Sale;

class AccountingService
{
    public static function postSale(Sale $sale)
    {
        DealerLedger::create([
            'dealer_id' => $sale->customer_id,
            'invoice_id' => $sale->id,
            'date' => $sale->date,
            'debit' => $sale->net_amount,
            'remarks' => 'Sales Invoice: '.$sale->invoice_no,
        ]);
    }

    public static function postPayment(PaymentCollection $payment)
    {
        DealerLedger::create([
            'dealer_id' => $payment->dealer_id,
            'date' => $payment->date,
            'credit' => $payment->amount,
            'remarks' => 'Payment Received: '.$payment->receipt_no,
        ]);
    }
}
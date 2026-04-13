<?php

namespace App\Http\Controllers\Backend\Accounting;

use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Accounting\DealerLedger;

class DealerLedgersController extends Controller
{
    use Helper;

    public function __construct()
    {
        $this->model = new DealerLedger();
    }

    public function index()
    {
        $dealer_id = request()->input('dealer_id');
        $user = auth()->user();
        $isSalesman = $this->isSalesManger();
        $rows = DB::table('dealer_ledgers')
            ->leftJoin('dealers', 'dealer_ledgers.dealer_id', '=', 'dealers.id')
            ->leftJoin('invoices', 'dealer_ledgers.invoice_id', '=', 'invoices.id')
            ->when($dealer_id, function ($query, $dealer_id) {
                $query->where('dealer_ledgers.dealer_id', $dealer_id);
            })
            ->where('dealer_ledgers.type', 1)
            ->select(
                'invoices.invoice_no',
                'dealer_ledgers.dealer_id',
                'dealers.name as dealer_name',
                'dealers.dealer_code',
                'dealer_ledgers.date',
                'dealer_ledgers.invoice_id',
                'dealer_ledgers.transaction_type',
                'dealer_ledgers.debit as debit_amount',
                'dealer_ledgers.credit as credit_amount',
                'dealer_ledgers.remarks'
            )
            ->where(function ($query) use ($user) {
                if ($user->division_id) {
                    $query->where('division_id', $user->division_id);
                }
                if ($user->district_id) {
                    $query->where('district_id', $user->district_id);
                }
                if ($user->warehouse_id) {
                    $query->where('warehouse_id', $user->warehouse_id);
                }
            })
            ->when($isSalesman, function ($query) use ($user) {
                $query->where('created_by', $user->id);
            })
            ->orderBy('dealers.name')
            ->orderBy('dealer_ledgers.invoice_id')
            ->orderBy('dealer_ledgers.date')
            ->get();

        $data = $rows->groupBy('dealer_id')->map(function ($dealerItems, $dealerId) {
            $dealerName = $dealerItems->first()->dealer_name ?? 'Unknown';
            $dealerCode = $dealerItems->first()->dealer_code ?? 'code';

            $invoices = $dealerItems->groupBy('invoice_id')->map(function ($invoiceItems, $invoiceId) {
                $entries = $invoiceItems->map(function ($r) {
                    return [
                        'date'             => $r->date,
                        'invoice_no'       => $r->invoice_no,
                        'invoice_id'       => $r->invoice_id,
                        'transaction_type' => $r->transaction_type == 1 ? 'invoice' : 'payment',
                        'debit_amount'     => (float) $r->debit_amount,
                        'credit_amount'    => (float) $r->credit_amount,
                        'remarks'          => $r->remarks,
                    ];
                })->values();

                return [
                    'invoice_id'   => $invoiceId,
                    'entries'      => $entries,
                    'totals'       => [
                        'debit'  => $invoiceItems->sum('debit_amount'),
                        'credit' => $invoiceItems->sum('credit_amount'),
                        'due' => $invoiceItems->sum('debit_amount') - $invoiceItems->sum('credit_amount'),
                    ],
                ];
            })->values();

            return [
                'dealer_id'   => $dealerId,
                'dealer_name' => $dealerName,
                'dealer_code' => $dealerCode,
                'invoices'    => $invoices,
                'total_invoice' => $invoices->count(),
                'print_date'    => date('Y-m-d H:i:a'),
                'totals'      => [
                    'debit'  => $dealerItems->sum('debit_amount'),
                    'credit' => $dealerItems->sum('credit_amount'),
                    'due' => $dealerItems->sum('debit_amount') - $dealerItems->sum('credit_amount'),
                ],
            ];
        })->values();
        // dd($data);

        return returnData(2000, $data);
    }


    public function customerLedgers()
    {
        $customer_id = request()->input('customer_id');
        $user = auth()->user();
        $isSalesman = $this->isSalesManger();

        $rows = DB::table('dealer_ledgers')
            ->leftJoin('customers', 'dealer_ledgers.customer_id', '=', 'customers.id')
            ->leftJoin('invoices', 'dealer_ledgers.invoice_id', '=', 'invoices.id')
            ->when($customer_id, function ($query, $customer_id) {
                $query->where('dealer_ledgers.customer_id', $customer_id);
            })
            ->where('dealer_ledgers.type', 2)
            ->select(
                'invoices.invoice_no',
                'dealer_ledgers.customer_id',
                'customers.name as customer_name',
                'dealer_ledgers.date',
                'dealer_ledgers.invoice_id',
                'dealer_ledgers.transaction_type',
                'dealer_ledgers.debit as debit_amount',
                'dealer_ledgers.credit as credit_amount',
                'dealer_ledgers.remarks'
            )
            ->where(function ($query) use ($user) {
                if ($user->division_id) {
                    $query->where('invoices.division_id', $user->division_id);
                }

                if ($user->district_id) {
                    $query->where('invoices.district_id', $user->district_id);
                }

                if ($user->warehouse_id) {
                    $query->where('invoices.warehouse_id', $user->warehouse_id);
                }
            })
            ->when($isSalesman, function ($query) use ($user) {
                $query->where('created_by', $user->id);
            })
            ->orderBy('customers.name')
            ->orderBy('dealer_ledgers.invoice_id')
            ->orderBy('dealer_ledgers.date')
            ->get();

        $data = $rows->groupBy('customer_id')->map(function ($dealerItems, $customerId) {
            $dealerName = $dealerItems->first()->customer_name ?? 'Unknown';

            $invoices = $dealerItems->groupBy('invoice_id')->map(function ($invoiceItems, $invoiceId) {
                $entries = $invoiceItems->map(function ($r) {
                    return [
                        'date'             => $r->date,
                        'invoice_id'       => $r->invoice_id,
                        'invoice_no'       => $r->invoice_no,
                        'transaction_type' => $r->transaction_type == 1 ? 'invoice' : 'payment',
                        'debit_amount'     => (float) $r->debit_amount,
                        'credit_amount'    => (float) $r->credit_amount,
                        'remarks'          => $r->remarks,
                    ];
                })->values();

                return [
                    'invoice_id'   => $invoiceId,
                    'entries'      => $entries,
                    'totals'       => [
                        'debit'  => $invoiceItems->sum('debit_amount'),
                        'credit' => $invoiceItems->sum('credit_amount'),
                        'due' => $invoiceItems->sum('debit_amount') - $invoiceItems->sum('credit_amount'),
                    ],
                ];
            })->values();

            return [
                'customer_id'   => $customerId,
                'customer_name' => $dealerName,
                'invoices'    => $invoices,
                'total_invoice' => $invoices->count(),
                'print_date' => date('Y-m-d H:i:a'),
                'totals'      => [
                    'debit'  => $dealerItems->sum('debit_amount'),
                    'credit' => $dealerItems->sum('credit_amount'),
                    'due' => $dealerItems->sum('debit_amount') - $dealerItems->sum('credit_amount'),
                ],
            ];
        })->values();
        // dd($data);

        return returnData(2000, $data);
    }

//    public function invoiceDetailsInformation($invoice_id)
//    {
//
//        $data = DB::table('invoice_items')
//            ->where('invoice_items.invoice_id', $invoice_id)
//            ->leftJoin('products', 'invoice_items.product_id', '=', 'products.id')
//            ->selectRaw('
//                invoice_items.id,
//                invoice_items.invoice_id,
//                products.product_name,
//                products.product_code,
//                invoice_items.quantity,
//                invoice_items.unit_price,
//                invoice_items.total_price')
//            ->get();
//        $invoice = DB::table('invoices')->where('id', $invoice_id)->first();
//
//        // dd($data);
//        return view('invoice.invoiceDetails', compact('data', 'invoice'));
//    }


    public function invoiceDetailsInformation($invoice_id)
    {
        $data = DB::table('invoice_items')
            ->where('invoice_items.invoice_id', $invoice_id)
            ->leftJoin('products', 'invoice_items.product_id', '=', 'products.id')
            ->selectRaw('
            invoice_items.id,
            invoice_items.invoice_id,
            invoice_items.product_id,
            invoice_items.serial_group_id,
            products.product_name,
            products.product_code,
            invoice_items.quantity,
            invoice_items.unit_price,
            invoice_items.total_price
        ')
            ->get();

        foreach ($data as $item) {
            $item->serials = DB::table('product_serials')
                ->where('invoice_id', $invoice_id)
                ->where('product_id', $item->product_id)
                ->where('serial_group_id', $item->serial_group_id)
                ->where('status', 1)
                ->pluck('serial');
        }

        $invoice = DB::table('invoices')->where('id', $invoice_id)->first();
        return view('invoice.invoiceDetails', compact('data', 'invoice'));
    }
}

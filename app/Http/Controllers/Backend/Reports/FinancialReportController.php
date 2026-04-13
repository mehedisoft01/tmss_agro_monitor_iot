<?php

namespace App\Http\Controllers\Backend\Reports;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Stock;
use App\Models\ProductManagement\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinancialReportController extends Controller
{
    public function collectionReport(Request $request)
    {
        $start = $request->start_date;
        $end = $request->end_date;

        $collections = DB::table('payment_collections')
            ->leftJoin('invoices', 'payment_collections.invoice_id', '=', 'invoices.id')
            ->leftJoin('users', 'payment_collections.user_id', '=', 'users.id')
            ->leftJoin('invoice_items', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->select(
                'payment_collections.id as collection_id',
                'payment_collections.receipt_no',
                'payment_collections.date as payment_date',
                'payment_collections.amount as paid_amount',
                'payment_collections.payment_mode',
                'payment_collections.reference_no',
                'invoices.id as invoice_id',
                'invoices.invoice_no',
                'invoices.invoice_date',
                'invoices.order_no',
                'users.name',
                DB::raw('GROUP_CONCAT(invoice_items.product_id) as product_ids'),
                DB::raw('GROUP_CONCAT(invoice_items.product_code) as product_codes'),
                DB::raw('GROUP_CONCAT(invoice_items.quantity) as product_qty'),
                DB::raw('GROUP_CONCAT(invoice_items.total_price) as product_total')
            )
            ->when($start && $end, function ($q) use ($start, $end) {
                $q->whereBetween('payment_collections.date', [$start, $end]);
            })
            ->groupBy(
                'payment_collections.id',
                'payment_collections.receipt_no',
                'payment_collections.date',
                'payment_collections.amount',
                'payment_collections.payment_mode',
                'payment_collections.reference_no',
                'invoices.id',
                'invoices.invoice_no',
                'invoices.invoice_date',
                'invoices.order_no',
                'users.name'
            )
            ->get();

        return returnData(2000, ['data' => $collections]);
    }

    public function dealerOutstanding(Request $request)
    {
        try {
            $startDate = $request->start_date;
            $endDate   = $request->end_date;
            $dealerId   = $request->dealer_id;
            $dealers = DB::table('dealers as d')
                ->join('dealer_ledgers as dl', function ($join) use ($startDate, $endDate) {
                    $join->on('d.id', '=', 'dl.dealer_id')
                        ->where('dl.type', 1);

                    if ($startDate && $endDate) {
                        $join->whereBetween('dl.date', [$startDate, $endDate]);
                    }

                })
                ->select(
                    'd.id as dealer_id',
                    'd.dealer_code',
                    'd.name as dealer_name',
                    'd.phone',
                    DB::raw('SUM(dl.debit) as total_bill'),
                    DB::raw('SUM(dl.credit) as total_paid'),
                    DB::raw('(SUM(dl.credit) - SUM(dl.debit)) as current_outstanding')
                )
                ->where('d.status', 1)
                ->where('d.approval_status', 1)
                ->when($dealerId, function ($query) use ($dealerId) {
                    return $query->where('d.id', $dealerId);
                })
                ->groupBy(
                    'd.id',
                    'd.dealer_code',
                    'd.name',
                    'd.phone'
                )
                ->orderByDesc('current_outstanding')
                ->get();
            foreach ($dealers as $dealer) {

                $dealer->due_invoices = DB::table('invoices as i')
                    ->leftJoin('dealer_ledgers as pay', function ($join) {
                        $join->on('i.id', '=', 'pay.invoice_id')
                            ->where('pay.type', 1)
                            ->where('pay.transaction_type', 2);
                    })
                    ->select(
                        'i.id as invoice_id',
                        'i.invoice_no',
                        'i.invoice_date',
                        'i.net_amount',
                        DB::raw('COALESCE(SUM(pay.credit),0) as paid_amount'),
                        DB::raw('(i.net_amount - COALESCE(SUM(pay.credit),0)) as due_amount')
                    )
                    ->where('i.dealer_id', $dealer->dealer_id)
                    ->where('i.status', 1)
                    ->groupBy(
                        'i.id',
                        'i.invoice_no',
                        'i.invoice_date',
                        'i.net_amount'
                    )
                    ->havingRaw('(i.net_amount - COALESCE(SUM(pay.credit),0)) > 0')
                    ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('i.invoice_date', [$startDate, $endDate]);
                    })->get();
            }

            return returnData(2000, ['data' => $dealers, 'summary' => [
                    'total_receivable' => $dealers->sum('current_outstanding'),
                    'total_dealers'    => $dealers->count(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['status'  => 5000, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function vatTax(Request $request)
    {
        $data = Stock::join('products', 'stocks.product_id', '=', 'products.id')
            ->select(
                'products.product_name',
                'products.product_code',
                'products.tax_method',
                'stocks.selling_price',
                'stocks.tax',
                'stocks.shipping_cost',
                'stocks.unit_cost',
                'stocks.current_stock as stock_quantity',
                DB::raw('ROUND(((stocks.current_stock * stocks.unit_cost) + stocks.shipping_cost) * (stocks.tax / 100), 2) as tax_amount')
            )
            ->when($request->start_date && $request->end_date, function ($q) use ($request) {
                $q->whereBetween('stocks.created_at', [
                    $request->start_date,
                    $request->end_date
                ]);
            })
            ->get();

        return returnData(2000, $data);
    }
}

<?php

namespace App\Http\Controllers\Backend\Reports;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Stock;
use App\Models\Sales\Invoice;
use App\Models\Sales\InvoiceItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Calculation\Token\Stack;

class InventoryReportController extends Controller
{

    public function stockValuation(Request $request)
    {
        try {

            $start     = $request->start_date;
            $end       = $request->end_date;
            $warehouse = $request->warehouse_id;
            $product   = $request->product_id;

            $query = Stock::with([
                'product:id,product_name',
                'user:id,name',
                'warehouse:id,warehouse_name'
            ])
                ->select('stocks.*')
                ->selectSub(function ($q) {
                    $q->from('product_serials')
                        ->join('product_serial_groups', 'product_serial_groups.id', '=', 'product_serials.serial_group_id')
                        ->whereColumn('product_serials.product_id', 'stocks.product_id')
                        ->whereColumn('product_serial_groups.warehouse_id', 'stocks.warehouse_id')
                        ->where('product_serials.status', 0)
                        ->selectRaw('COUNT(DISTINCT product_serials.serial)');
                }, 'stock_qty');

            if ($warehouse) {
                $query->where('stocks.warehouse_id', $warehouse);
            }

            if ($product) {
                $query->where('stocks.product_id', $product);
            }

            if ($start && $end) {
                $query->whereBetween('stocks.last_updated', [$start, $end]);
            }

            $stocks = $query->get();
            $result = [];

            foreach ($stocks as $stock) {
                $saleQuery = InvoiceItem::where('product_id', $stock->product_id)
                    ->whereHas('invoice', function ($q) use ($stock, $start, $end) {
                        $q->where('warehouse_id', $stock->warehouse_id);
                        if ($start && $end) {
                            $q->whereBetween('created_at', [$start, $end]);
                        }
                    });

                $total_sale_qty = $saleQuery->sum('quantity') ?? 0;
                $total_sale_amount = $saleQuery
                        ->selectRaw('SUM(quantity * unit_price) as total')
                        ->value('total') ?? 0;

                $stock_qty = $stock->stock_qty ?? 0; // from serial count
                $unit_cost = $stock->unit_cost ?? 0;

                $stock_amount = $stock_qty * $unit_cost;
                $remaining_stock = $stock_qty - $total_sale_qty;
                $remaining_stock_amount = $remaining_stock * $unit_cost;

                $result[] = [
                    "product_name"     => $stock->product->product_name ?? "",
                    "creator_name"     => $stock->user->name ?? "N/A",
                    "warehouse"        => $stock->warehouse->warehouse_name ?? "",
                    "stock_qty"        => $stock_qty,
                    "stock_amount"     => $stock_amount,
                    "sale_qty"         => $total_sale_qty,
                    "sale_amount"      => $total_sale_amount,
                    "in_stock"         => $remaining_stock,
                    "in_stock_amount"  => $remaining_stock_amount,
                    "serial_count"     => $stock_qty, // same as stock_qty
                ];
            }

            return returnData(2000, ['data' => $result]);

        } catch (\Exception $e) {

            return response()->json(["status"  => false, "message" => $e->getMessage()], 500);
        }
    }
    public function stockMovementReport(Request $request)
    {
        $product_id   = $request->input('product_id');
        $warehouse_id = $request->input('warehouse_id');
        $start_date   = $request->input('start_date');
        $end_date     = $request->input('end_date');

        $movementTypes = [
            1 => 'Stock',
            2 => 'Sale',
            3 => 'Return',
            4 => 'Adjustment',
            5 => 'Transfer',
        ];

        $query = DB::table('product_stock_movements as psm')
            ->join('products as p', 'psm.product_id', '=', 'p.id')
            ->join('warehouses as w', 'psm.warehouse_id', '=', 'w.id')
            ->leftJoin('users as u', 'psm.user_id', '=', 'u.id')

            // 🔥 Correct Serial Join (Handles All Types)
            ->leftJoin('product_serials as ps', function ($join) {
                $join->on('ps.product_id', '=', 'psm.product_id')
                    ->where(function ($q) {
                        $q->whereColumn('ps.serial_group_id', 'psm.ref_id')
                            ->orWhereColumn('ps.invoice_id', 'psm.ref_id');
                    });
            })

            ->select(
                'p.product_name',
                'w.warehouse_name',
                'u.name as creator_name',
                'psm.ref_id as reference_id',
                'psm.type',
                'psm.shipping_cost',
                'psm.note',
                DB::raw('DATE(psm.created_at) as movement_date'),

                // ✅ Actual Serial Count
                DB::raw('COUNT(DISTINCT ps.id) as quantity')
            )

            ->when($warehouse_id, function ($q) use ($warehouse_id) {
                $q->where('psm.warehouse_id', $warehouse_id);
            })

            ->when($product_id, function ($q) use ($product_id) {
                $q->where('psm.product_id', $product_id);
            })

            ->when($start_date && $end_date, function ($q) use ($start_date, $end_date) {
                $q->whereBetween('psm.created_at', [
                    $start_date . ' 00:00:00',
                    $end_date . ' 23:59:59'
                ]);
            })

            ->groupBy(
                'psm.id',
                DB::raw('DATE(psm.created_at)')
            )

            ->orderBy('psm.created_at', 'asc')
            ->get();

        $reportData = $query->map(function ($item) use ($movementTypes) {

            $item->movement_type_name = $movementTypes[$item->type] ?? 'Unknown';

            // IN / OUT Logic
            if (in_array($item->type, [1, 3])) {
                $item->in_out = 'IN';
            } else {
                $item->in_out = 'OUT';
            }

            return $item;
        });

        return returnData(2000, ['data' => $reportData]);
    }

//    public function bestSalesman(Request $request)
//    {
//        try {
//
//            $toDate   = Carbon::now()->endOfMonth()->format('Y-m-d');
//            $fromDate = Carbon::now()->subMonths(2)->startOfMonth()->format('Y-m-d');
//
//            $query = Invoice::select(
//                'salesman_id',
//                DB::raw('SUM(total_qty) as total_qty'),
//                DB::raw('SUM(net_amount) as total_sale')
//            )
//                ->with('salesman:id,name')
//                ->whereNotNull('salesman_id')
//                ->checkWarehouse()
//                ->whereBetween('invoice_date', [$fromDate, $toDate]);
//
//            if ($request->salesman_id) {
//                $query->where('salesman_id', $request->salesman_id);
//            }
//
//            // ✅ TOP 3
//            $top3 = $query
//                ->groupBy('salesman_id')
//                ->orderByDesc('total_sale')
//                ->limit(3)
//                ->get();
//
//            if ($top3->isEmpty()) {
//                return returnData(404, [], 'No sales data found');
//            }
//
//            return returnData(2000, [
//                'fromMonth' => Carbon::parse($fromDate)->format('F Y'),
//                'toMonth'   => Carbon::parse($toDate)->format('F Y'),
//                'top3'      => $top3
//            ], '');
//
//        } catch (\Exception $exception) {
//            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
//        }
//    }


    public function bestSalesman(Request $request)
    {
        try {
            $fromDate = $request->from_date ?? Carbon::now()->startOfMonth()->format('Y-m-d');
            $toDate   = $request->to_date ?? Carbon::now()->endOfMonth()->format('Y-m-d');
            $limit    = $request->limit ?? 3;

            $targetSub = DB::table('assign_target_products as atp')
                ->join('assign_territories as at', 'at.id', '=', 'atp.assing_territories_id')
                ->select(
                    'atp.salesman_id',
                    DB::raw(" MIN(CASE WHEN atp.from_amount > 0 THEN atp.from_amount END ) as target_amount"))
                ->where('at.status', 1)

                ->whereDate('at.target_start_date', '<=', $toDate)
                ->whereDate('at.target_end_date', '>=', $fromDate)
                ->groupBy('atp.salesman_id');
            $topSalesmen = Invoice::select(
                'invoices.salesman_id',
                DB::raw('SUM(invoices.total_qty) as total_qty'),
                DB::raw('SUM(invoices.net_amount) as total_sale'),
                DB::raw('COALESCE(ts.target_amount,0) as total_target'),
                DB::raw('
                    CASE 
                        WHEN COALESCE(ts.target_amount,0) > 0 
                        THEN ROUND((SUM(invoices.net_amount) / ts.target_amount) * 100, 2) 
                        ELSE 0 
                    END as achievement_percent
                ')
            )
                ->leftJoinSub($targetSub, 'ts', function ($join) {
                    $join->on('ts.salesman_id', '=', 'invoices.salesman_id');
                })
                ->with('salesman:id,name')
                ->where('invoice_status', 1)
                ->whereNotNull('invoices.salesman_id')
                ->checkWarehouse()
                ->whereBetween('invoices.invoice_date', [$fromDate, $toDate])
                ->groupBy('invoices.salesman_id', 'ts.target_amount')
                ->orderByDesc('total_sale')
                ->limit($limit)
                ->get();
            if ($topSalesmen->isEmpty()) {
                return returnData(404, [], 'No sales data found');
            }
            return returnData(2000, [
                'fromMonth' => Carbon::parse($fromDate)->format('F Y'),
                'toMonth'   => Carbon::parse($toDate)->format('F Y'),
                'top3'      => $topSalesmen
            ], '');
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }
}

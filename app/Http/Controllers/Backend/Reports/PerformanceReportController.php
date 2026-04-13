<?php

namespace App\Http\Controllers\Backend\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerformanceReportController extends Controller
{
    public function targetAchievement(Request $request)
    {
        $salesmanId  = $request->salesman_id;
        $warehouseId = $request->warehouse_id;

        $invoiceSub = DB::table('invoices')
            ->select(
                'salesman_id',
                DB::raw('MONTH(invoice_date) as month_no'),
                DB::raw('SUM(net_amount) as sold_amount')
            )
            ->where('status', 1)
            ->where('invoice_status', 1)

            ->groupBy('salesman_id', DB::raw('MONTH(invoice_date)'));

        $query = DB::table('assign_target_products as atp')
            ->join('assign_territories as at', function($join) {
                $join->on('at.id', '=', 'atp.assing_territories_id')
                    ->where('at.status', 1);
            })
            ->leftJoinSub($invoiceSub, 'sales', function($join) {
                $join->on('sales.salesman_id', '=', 'atp.salesman_id')
                    ->on(DB::raw('MONTH(at.target_start_date)'), '=', 'sales.month_no');
            })
            ->when($salesmanId, function($q) use ($salesmanId) {
                $q->where('atp.salesman_id', $salesmanId);
            })
            ->select(
                'atp.salesman_id',
                DB::raw('MONTH(at.target_start_date) as month_no'),
                DB::raw('MAX(MONTHNAME(at.target_start_date)) as month_name'),
                DB::raw('MIN(CASE WHEN atp.from_amount > 0 THEN atp.from_amount END) as target_amount'),
                DB::raw('COALESCE(sales.sold_amount,0) as sold_amount'),
                DB::raw('CASE
            WHEN MIN(CASE WHEN atp.from_amount > 0 THEN atp.from_amount END) > 0
            THEN ROUND(COALESCE(sales.sold_amount,0) / MIN(CASE WHEN atp.from_amount > 0 THEN atp.from_amount END) * 100, 2)
            ELSE 0
        END as achievement_amount_percent')
            )
            ->groupBy('atp.salesman_id', DB::raw('MONTH(at.target_start_date)'))
            ->orderBy(DB::raw('MONTH(at.target_start_date)'))
            ->get();
         return returnData(2000, ['data' => $query]);
    }


    public function salesmanPerformance(Request $request)
    {
        try {

            $startDate   = $request->start_date ?? now()->startOfMonth()->format('Y-m-d');
            $endDate     = $request->end_date ?? now()->endOfMonth()->format('Y-m-d');
            $warehouseId = $request->warehouse_id;
            $salesmanId  = $request->salesman_id;

            /*
            |--------------------------------------------------------------------------
            | 1️⃣ Sales Subquery (Actual Sales)
            |--------------------------------------------------------------------------
            */
            $salesSub = DB::table('invoices')
                ->select(
                    'salesman_id',
                    DB::raw('SUM(total_qty) as sold_qty'),
                    DB::raw('SUM(net_amount) as sold_amount')
                )
                ->where('status', 1)
                ->where('invoice_status', 1)
                ->whereNotNull('salesman_id')
                ->whereBetween('invoice_date', [$startDate, $endDate])
                ->when($warehouseId, function ($q) use ($warehouseId) {
                    $q->where('warehouse_id', $warehouseId);
                })
                ->groupBy('salesman_id');

            /*
            |--------------------------------------------------------------------------
            | 2️⃣ Main Query (Target + Sales)
            |--------------------------------------------------------------------------
            */
            $query = DB::table('assign_target_products as atp')
                ->join('assign_territories as at', 'at.id', '=', 'atp.assing_territories_id')
                ->join('salesmen as s', 's.id', '=', 'atp.salesman_id')
                ->join('warehouses as w', 'w.id', '=', 'at.warehouse_id')

                ->leftJoinSub($salesSub, 'sales', function ($join) {
                    $join->on('sales.salesman_id', '=', 'atp.salesman_id');
                })

                ->select(
                    's.id as salesman_id',
                    's.name as salesman_name',
                    'w.warehouse_name',

                    DB::raw("
                    MIN(
                        CASE 
                            WHEN atp.from_amount > 0 
                            THEN atp.from_amount 
                        END
                    ) as target_amount
                "),

                    DB::raw("COALESCE(sales.sold_qty,0) as sold_qty"),
                    DB::raw("COALESCE(sales.sold_amount,0) as sold_amount"),

                    DB::raw("
                    CASE 
                        WHEN MIN(CASE WHEN atp.from_amount > 0 THEN atp.from_amount END) > 0
                        THEN ROUND(
                            (COALESCE(sales.sold_amount,0) /
                            MIN(CASE WHEN atp.from_amount > 0 THEN atp.from_amount END)
                            ) * 100,
                            2
                        )
                        ELSE 0
                    END as achievement_percent
                ")
                )

                ->where('at.status', 1)

                // Territory date overlap with report range
                ->whereDate('at.target_start_date', '<=', $endDate)
                ->whereDate('at.target_end_date', '>=', $startDate)

                ->when($salesmanId, function ($q) use ($salesmanId) {
                    $q->where('atp.salesman_id', $salesmanId);
                })

                ->when($warehouseId, function ($q) use ($warehouseId) {
                    $q->where('at.warehouse_id', $warehouseId);
                })

                ->groupBy(
                    's.id',
                    's.name',
                    'w.warehouse_name',
                    'sales.sold_qty',
                    'sales.sold_amount'
                )

                ->orderByDesc('sold_amount')

                ->get();

            if ($query->isEmpty()) {
                return returnData(404, [], 'No performance data found');
            }

            return returnData(2000, [
                'start_date' => $startDate,
                'end_date'   => $endDate,
                'data'       => $query
            ], '');

        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }
}

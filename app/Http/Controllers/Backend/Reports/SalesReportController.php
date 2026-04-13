<?php

namespace App\Http\Controllers\Backend\Reports;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Sales\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesReportController extends Controller
{
    use Helper;
    public function saleProduct(Request $request)
    {
        $warehouse = $request->warehouse_id;
        $product = $request->product_id;
        $productCategory = $request->parent_id;
        $user = auth()->user();
        $isSalesman = $this->isSalesManger();
        $sales = DB::table('invoice_items as ii')
            ->join('invoices as inv', 'ii.invoice_id', '=', 'inv.id')
            ->join('products as p', 'ii.product_id', '=', 'p.id')
            ->leftJoin('product_categories as pc', 'p.parent_id', '=', 'pc.id')
            ->leftJoin('salesmen as s', 'inv.salesman_id', '=', 's.id')
            ->leftJoin('product_serial_groups as psg', function($join) {
                $join->on('ii.serial_group_id', '=', 'psg.id');
            })

            ->select(
                'ii.product_id',
                'p.product_name',
                'p.parent_id',
                'inv.invoice_no',
                'inv.invoice_date',
                'pc.category_name',
                's.name as creator_name',
                DB::raw('ii.quantity as total_qty'),
                DB::raw('ii.unit_price as unit_price'),

                // Total sale amount
                DB::raw('SUM(ii.total_price) as total_amount'),

                // Serial numbers
                DB::raw('(SELECT GROUP_CONCAT(ps.serial SEPARATOR ", ")
                FROM product_serials ps
                WHERE ps.product_id = ii.product_id
                AND ps.invoice_id = ii.invoice_id) as serial_numbers'),

                // Normalized cost price (cost_price / serial_group quantity)
                DB::raw('
                IFNULL((psg.cost_price / NULLIF(psg.quantity,0)),0) as normalized_cost_price
            '),

                // Total cost amount = normalized_cost_price * invoice_item quantity
                DB::raw('
                SUM(ii.quantity * IFNULL((psg.cost_price / NULLIF(psg.quantity,0)),0)) as total_cost_amount
            '),

                // Gross profit
                DB::raw('
                SUM(ii.total_price - (ii.quantity * IFNULL((psg.cost_price / NULLIF(psg.quantity,0)),0))) as gross_profit
            '),

                // Profit margin
                DB::raw('
                CASE
                    WHEN SUM(ii.total_price) > 0 THEN
                        (SUM(ii.total_price - (ii.quantity * IFNULL((psg.cost_price / NULLIF(psg.quantity,0)),0))) / SUM(ii.total_price)) * 100
                    ELSE 0
                END as profit_margin
            ')
            )
            ->when($warehouse, function($q) use ($warehouse) {
                $q->where('inv.warehouse_id', $warehouse);
            })
            ->when($product, function($q) use ($product) {
                $q->where('ii.product_id', $product);
            })
            ->when($productCategory, function($q) use ($productCategory) {
                $q->where('p.parent_id', $productCategory);
            })
            ->where(function ($query) use ($user){
                $query->when($user->division_id, function ($query) use ($user) {
                    $query->where('division_id', $user->division_id);
                })
                    ->when($user->district_id, function ($query) use ($user) {
                        $query->where('district_id', $user->district_id);
                    })
                    ->when($user->warehouse_id, function ($query) use ($user) {
                        $query->where('id', $user->warehouse_id);
                    });
            })
            ->when($isSalesman, function ($query) use ($user) {
                $query->where('inv.created_by', $user->id);
            })
            ->where('inv.invoice_status', 1)
        ->groupBy(
            'ii.invoice_id',
            'ii.product_id',
            'p.product_name',
            'pc.category_name',
            'inv.invoice_no',
            'inv.invoice_date',
            'ii.unit_price',
            's.name',
            'psg.cost_price',
            'psg.quantity'
        )
        ->get();

    return returnData(2000, $sales);
}

    public function salesSummary(Request $request)
    {
        $start = $request->start_date;
        $end   = $request->end_date;
        $warehouse = $request->warehouse_id;
        $product = $request->product_id;
        $partyType = $request->party_type;
        $dealerId = $request->dealer_id;
        $customerId = $request->customer_id;
        $user = auth()->user();
        $isSalesman = $this->isSalesManger();
        $query = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('products', 'invoice_items.product_id', '=', 'products.id')
            ->leftJoin('customers', 'invoices.customer_id', '=', 'customers.id')
            ->leftJoin('dealers', 'invoices.dealer_id', '=', 'dealers.id')
            ->leftJoin('salesmen', 'invoices.salesman_id', '=', 'salesmen.id')
            ->leftJoin('warehouses', 'invoices.warehouse_id', '=', 'warehouses.id')
            ->select(
                'invoices.invoice_no',
                'invoices.invoice_date',
                'invoices.warehouse_id',
                'invoices.discount as discount_amount',
                'warehouses.warehouse_name',

                'products.product_name',
                'salesmen.name as creator_name',

                DB::raw('IFNULL(customers.name, dealers.name) as party_name'),

                DB::raw("
                CASE
                    WHEN invoices.customer_id IS NOT NULL THEN 'Customer'
                    WHEN invoices.dealer_id IS NOT NULL THEN 'Dealer'
                    ELSE 'N/A'
                END as party_type
            "),

                DB::raw('SUM(invoice_items.quantity) as total_sale_qty'),
                DB::raw('SUM(invoice_items.total_price) as total_sale_amount'),

                DB::raw('(
                SELECT GROUP_CONCAT(ps.serial SEPARATOR ", ")
                FROM product_serials ps
                WHERE ps.product_id = invoice_items.product_id
                AND ps.invoice_id = invoice_items.invoice_id
            ) as serial_numbers')
            )

            ->where('invoices.status', 1)
            ->where('invoices.invoice_status', 1)
            ->where(function ($query) use ($user){
                $query->when($user->division_id, function ($query) use ($user) {
                    $query->where('invoices.division_id', $user->division_id);
                })
                    ->when($user->district_id, function ($query) use ($user) {
                        $query->where('invoices.district_id', $user->district_id);
                    })
                    ->when($user->warehouse_id, function ($query) use ($user) {
                        $query->where('invoices.warehouse_id', $user->warehouse_id);
                    });
            })
            ->when($isSalesman, function ($query) use ($user) {
                $query->where('invoices.created_by', $user->id);
            });

        if ($warehouse) {
            $query->where('invoices.warehouse_id', $warehouse);
        }

        if ($product) {
            $query->where('invoice_items.product_id', $product);
        }

        if ($dealerId) {
            $query->where('invoices.dealer_id', $dealerId);
        }

        if ($customerId) {
            $query->where('invoices.customer_id', $customerId);
        }

        if ($partyType) {
            if ($partyType == 'customer') {
                $query->whereNotNull('invoices.customer_id');
            } elseif ($partyType == 'dealer') {
                $query->whereNotNull('invoices.dealer_id');
            }
        }

        if ($start && $end) {
            $query->whereBetween('invoices.invoice_date', [$start, $end]);
        }

        $query->groupBy(
            'invoice_items.invoice_id',
            'invoices.invoice_no',
            'invoices.invoice_date',
            'invoices.warehouse_id',
            'invoices.discount',
            'warehouses.warehouse_name',
            'products.product_name',
            'salesmen.name',
            'customers.name',
            'dealers.name'
        );

        $data = $query->get();

        return returnData(2000, $data);
    }


//    public function salesByDealer(Request $request)
//    {
//        $warehouse = $request->warehouse_id;
//        $product = $request->product_id;
//
//        $sales = DB::table('invoice_items')
//            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
//            ->join('products', 'invoice_items.product_id', '=', 'products.id')
//            ->leftJoin('product_categories', 'products.parent_id', '=', 'product_categories.id')
//            ->leftJoin('dealers', 'invoices.dealer_id', '=', 'dealers.id')
//            ->select(
//                'invoices.dealer_id',
//                'dealers.name as dealer_name',
//                'invoice_items.product_id',
//                'products.product_name',
//                'products.cost_price',
//                'product_categories.category_name',
//                DB::raw('SUM(invoice_items.quantity) as total_qty'),
//                DB::raw('MAX(invoice_items.unit_price) as unit_price'),
//                DB::raw('SUM(invoice_items.total_price) as total_amount'),
//                DB::raw('SUM((invoice_items.unit_price - products.cost_price) * invoice_items.quantity) as gross_profit'),
//                DB::raw('
//                CASE
//                    WHEN SUM(invoice_items.total_price) > 0
//                    THEN
//                        (SUM((invoice_items.unit_price - products.cost_price) * invoice_items.quantity)
//                        / SUM(invoice_items.total_price)) * 100
//                    ELSE 0
//                END as profit_margin
//            ')
//            )
//            ->whereNotNull('invoices.dealer_id') // শুধুমাত্র dealer ID set থাকা invoices
//            ->when($warehouse, function ($q) use ($warehouse) {
//                $q->where('invoice_items.warehouse_id', $warehouse);
//            })
//            ->when($product, function ($q) use ($product) {
//                $q->where('invoice_items.product_id', $product);
//            })
//            ->groupBy(
//                'invoices.dealer_id',
//                'dealers.name',
//                'invoice_items.product_id',
//                'products.product_name',
//                'products.cost_price',
//                'product_categories.category_name'
//            )
//            ->get();
//
//        return returnData(2000, $sales);
//    }

    public function salesByDealer(Request $request)
    {
        if ($request->invoice_no && $request->from_date && $request->to_date && $request->dealer_id) {
            return returnData(400, [], 'Please select at least one filter: Invoice No, or Date Range');
        }

        $isSalesman = $this->isSalesManger();
        $user = auth()->user();

        $query = Invoice::with('dealer', 'items.product', 'createdByUser')
            ->where('invoice_status', 1)
            ->where(function ($query) use ($user){
                $query->when($user->division_id, function ($query) use ($user) {
                    $query->where('division_id', $user->division_id);
                })
                    ->when($user->district_id, function ($query) use ($user) {
                        $query->where('district_id', $user->district_id);
                    })
                    ->when($user->warehouse_id, function ($query) use ($user) {
                        $query->where('id', $user->warehouse_id);
                    });
            })
            ->when($isSalesman, function ($query) use ($user) {
                $query->where('created_by', $user->id);
            })
            ->whereNotNull('dealer_id');

        if ($request->invoice_no) {
            $query->where('invoice_no', 'LIKE', "%{$request->invoice_no}%");
        }

        if ($request->from_date && $request->to_date) {
            $query->whereBetween('invoice_date', [$request->from_date, $request->to_date]);
        } else {
            if ($request->from_date || $request->to_date) {
                return returnData(404, [], 'Please select both From Date and To Date');
            }
        }
        if ($request->dealer_id) {
            $query->where('dealer_id', $request->dealer_id);
        }
        $invoices = $query->orderBy('id', 'desc')->get();

        if ($invoices->isEmpty()) {
            return returnData(404, [], 'No invoice found for your search!');
        }

        return returnData(2000, $invoices, '');
    }


    public function salesBysalesMan(Request $request)
    {
        if ($request->invoice_no && $request->from_date && $request->to_date && $request->salesman_id) {
            return returnData(400, [], 'Please select at least one filter: Invoice No, or Date Range');
        }
        $isSalesman = $this->isSalesManger();
        $user = auth()->user();

        $query = Invoice::with('salesman','dealer', 'items.product', 'createdByUser')
            ->where('invoice_status', 1)
            ->where(function ($query) use ($user){
                $query->when($user->division_id, function ($query) use ($user) {
                    $query->where('division_id', $user->division_id);
                })
                    ->when($user->district_id, function ($query) use ($user) {
                        $query->where('district_id', $user->district_id);
                    })
                    ->when($user->warehouse_id, function ($query) use ($user) {
                        $query->where('id', $user->warehouse_id);
                    });
            })
            ->when($isSalesman, function ($query) use ($user) {
                $query->where('created_by', $user->id);
            });
        if ($request->invoice_no) {
            $query->where('invoice_no', 'LIKE', "%{$request->invoice_no}%");
        }

        if ($request->from_date && $request->to_date) {
            $query->whereBetween('invoice_date', [$request->from_date, $request->to_date]);
        } else {
            if ($request->from_date || $request->to_date) {
                return returnData(404, [], 'Please select both From Date and To Date');
            }
        }

        if ($request->salesman_id) {
            $query->where('salesman_id', $request->salesman_id);
        }
        $invoices = $query->orderBy('id', 'desc')->get();

        if ($invoices->isEmpty()) {
            return returnData(404, [], 'No invoice found for your search!');
        }

        return returnData(2000, $invoices, '');
    }

}

<?php

namespace App\Http\Controllers\api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Accounting\PaymentCollection;
use App\Models\Dealer\Dealer;
use App\Models\Inventory\StockPurchase;
use App\Models\Inventory\Warehouse;
use App\Models\ProductManagement\Product;
use App\Models\Sales\Customer;
use App\Models\Sales\Invoice;
use App\Models\Sales\InvoiceItem;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardApiController extends Controller
{
    use Helper;
    public function dashboardDataAPI()
    {
        $authUser = auth()->user();
        $user = auth()->user();
        $isSalesman = $this->isSalesManger();
        $color = ($authUser->theme && $authUser->theme == 'bg-default bg-theme2') ? '#000000' : '#FFFFFF';

        $userId = auth()->id();
        $allowedUserIds = getAllowedUserIds();
        $lowerUserIds = myLowerUserIds();


        $from_date = request()->input('from_date') ?? date('Y-m-01');
        $to_date = request()->input('to_date') ?? date('Y-m-t');

        $lastMonthStartDate = date('Y-m-01', strtotime($from_date . ' -1 month'));
        $lastMonthEndDate = date('Y-m-t', strtotime($to_date . ' -1 month'));


        $data = [];
        $data['active_products'] = Product::where('status', 1)
            ->when($isSalesman, function ($query) use ($user) {
                $query->where('user_id', $user->id);})
            ->where(function ($query) use ($user) {
                if ($user->division_id){
                    $query->where('division_id', $user->division_id);
                }
                if ($user->district_id){
                    $query->where('district_id', $user->district_id);
                }
                if ($user->warehouse_id){
                    $query->where('id', $user->warehouse_id);
                }
            })->count();
        $data['approved_dealers'] = Dealer::where('approval_status', 1)
            ->when($isSalesman, function ($query) use ($user) {
                $query->where('user_id', $user->id);})

            ->whereHas('address', function ($query) use ($user) {
                if ($user->division_id) {
                    $query->where('p_division_id', $user->division_id);
                }
                if ($user->district_id) {
                    $query->where('p_district_id', $user->district_id);
                }
            })
            ->count();
        $data['total_customers'] = Customer::where('status', 1)
            ->when($isSalesman, function ($query) use ($user) {
                $query->where('user_id', $user->id);})

            ->whereHas('address', function ($query) use ($user) {
                if ($user->division_id) {
                    $query->where('p_division_id', $user->division_id);
                }
                if ($user->district_id) {
                    $query->where('p_district_id', $user->district_id);
                }
            })
            ->count();
        $data['total_warehouses'] = Warehouse::where('status', 1)
            ->when($isSalesman, function ($query) use ($user) {
                $query->where('user_id', $user->id);})
            ->where(function ($query) use ($user) {
                if ($user->division_id){
                    $query->where('division_id', $user->division_id);
                }
                if ($user->district_id){
                    $query->where('district_id', $user->district_id);
                }
                if ($user->warehouse_id){
                    $query->where('id', $user->warehouse_id);
                }
            })->count();
        $data['total_order'] = Order::where('status', 1)
//            ->whereBetween('order_date', [$from_date, $to_date])
            ->where(function ($query) use ($user) {
                if ($user->division_id){
                    $query->where('division_id', $user->division_id);
                }
                if ($user->district_id){
                    $query->where('district_id', $user->district_id);
                }
                if ($user->warehouse_id){
                    $query->where('warehouse_id', $user->warehouse_id);
                }
            })
            ->when($isSalesman, function ($query) use ($user) {
                $query->where('created_by', $user->id);})
            ->count();
        $data['stock_products'] = StockPurchase::where('status', 1)
            ->where(function ($q) use ($userId, $lowerUserIds) {
                $q->where('user_id', $userId)->orWhereIn('user_id', $lowerUserIds);
            })->count();
        // Total Orders
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $data['current_month_total_order'] = Order::where('status', 1)
            ->whereMonth('order_date', $currentMonth)
            ->whereYear('order_date', $currentYear)
            ->where(function ($q) use ($userId, $lowerUserIds) {
                $q->where('created_by', $userId)
                    ->orWhereIn('created_by', $lowerUserIds);
            })
            ->count();

        $lastMonthDate = now()->subMonth();
        $data['last_month_total_order'] = Order::where('status', 1)
            ->whereMonth('order_date', $lastMonthDate->month)
            ->whereYear('order_date', $lastMonthDate->year)
            ->where(function ($q) use ($userId, $lowerUserIds) {
                $q->where('created_by', $userId)
                    ->orWhereIn('created_by', $lowerUserIds);
            })
            ->count();


        // Current month total sale items
        $currentMonth = now()->month;
        $currentYear = now()->year;
//        $currentDay = now()->day;
        $lastMonthDate = now()->subMonth();
        $currentDay = now()->toDateString();

        $data['current_month_total_sale_items'] = InvoiceItem::join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoice_items.status', 1)
            ->whereMonth('invoice_items.created_at', $currentMonth)
            ->whereYear('invoice_items.created_at', $currentYear)
            ->where(function ($q) use ($userId, $lowerUserIds) {
                $q->where('invoices.user_id', $userId)
                    ->orWhereIn('invoices.user_id', $lowerUserIds);
            })->count();
        $data['today_total_sale_items'] = InvoiceItem::join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoice_items.status', 1)
            ->whereDate('invoice_items.created_at', $currentDay)
            ->where(function ($q) use ($userId, $lowerUserIds) {
                $q->where('invoices.user_id', $userId)
                    ->orWhereIn('invoices.user_id', $lowerUserIds);
            })->count();;
        $data['last_month_total_sale_items'] = InvoiceItem::join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoice_items.status', 1)
            ->whereMonth('invoice_items.created_at', $lastMonthDate->month)
            ->whereYear('invoice_items.created_at', $lastMonthDate->year)
            ->where(function ($q) use ($userId, $lowerUserIds) {
                $q->where('invoices.user_id', $userId)
                    ->orWhereIn('invoices.user_id', $lowerUserIds);
            })->count();



//       ****************************---This one---******************************************
//        $allowedSalesmanIds = DB::table('salesmen')
//            ->whereIn('user_id', $allowedUserIds)
//            ->pluck('id')
//            ->toArray();
//        $data['target_vs_achievement'] = DB::table('assign_target_products as atp')
//            ->leftJoin('assign_territories as at', function ($join) {
//                $join->on('at.id', '=', 'atp.assing_territories_id')
//                    ->where('at.status', 1);
//            })
//            ->leftJoin('invoices as i', function ($join) {
//                $join->on('i.salesman_id', '=', 'atp.salesman_id')
//                    ->where('i.status', 1)
//                    ->whereColumn('i.invoice_date', '>=', 'at.target_start_date')
//                    ->whereColumn('i.invoice_date', '<=', 'at.target_end_date');
//            })
//            ->leftJoin('salesmen as s', 's.id', '=', 'atp.salesman_id')
//            ->leftJoin('users as u', 'u.id', '=', 's.user_id')
//            ->whereIn('atp.salesman_id', $allowedSalesmanIds)
//            ->whereMonth('at.target_start_date', $currentMonth)
//            ->whereYear('at.target_start_date', $currentYear)
//            ->select(
//                's.name as salesman_name',
//                DB::raw("MONTHNAME(at.target_start_date) as month_name"),
//                DB::raw("SUM(DISTINCT atp.target_amount) as target_amount"),
//                DB::raw("COALESCE(SUM(i.net_amount),0) as sold_amount"),
//                DB::raw("MONTH(at.target_start_date) as month_no"),
//                DB::raw("
//                    CASE
//                        WHEN SUM(DISTINCT atp.target_amount) > 0
//                        THEN ROUND((SUM(i.net_amount) / SUM(DISTINCT atp.target_amount)) * 100, 2)
//                        ELSE 0
//                    END as achievement_percent
//                ")
//            )
//            ->groupBy('atp.salesman_id', 'u.name', DB::raw("MONTH(at.target_start_date)"))
//            ->get();

        $yearStart = now()->startOfYear()->toDateString(); // Current Year like (2026-01-01)

        $allowedSalesmanIds = DB::table('salesmen')
            ->whereIn('user_id', $allowedUserIds)
            ->pluck('id')
            ->toArray();

        $data['target_vs_achievement'] = DB::table('assign_target_products as atp')
            ->join('assign_territories as at', function ($join) {
                $join->on('at.id', '=', 'atp.assing_territories_id')
                    ->where('at.status', 1);
            })
            ->leftJoin('invoices as i', function ($join) {
                $join->on('i.salesman_id', '=', 'atp.salesman_id')
                    ->where('i.status', 1)
                    ->whereColumn('i.invoice_date', '>=', 'at.target_start_date')
                    ->whereColumn('i.invoice_date', '<=', 'at.target_end_date');
            })
            ->leftJoin('salesmen as s', 's.id', '=', 'atp.salesman_id')
            ->whereIn('atp.salesman_id', $allowedSalesmanIds)
            ->whereDate('at.target_start_date', '>=', $yearStart)
            ->whereDate('at.target_start_date', '<=', $currentDay)

            ->select(
                's.name as salesman_name',
                DB::raw("MONTH(at.target_start_date) as month_no"),
                DB::raw("MONTHNAME(at.target_start_date) as month_name"),
                DB::raw("SUM(DISTINCT atp.from_amount) as target_amount"),
                DB::raw("COALESCE(SUM(i.net_amount),0) as sold_amount"),
                DB::raw("
                    CASE
                        WHEN SUM(DISTINCT atp.from_amount) > 0
                        THEN ROUND((SUM(i.net_amount)/SUM(DISTINCT atp.from_amount))*100,2)
                        ELSE 0
                    END as achievement_percent
                ")
            )
            ->groupBy('atp.salesman_id', 's.name', DB::raw("MONTH(at.target_start_date)"))
            ->orderBy(DB::raw("MONTH(at.target_start_date)"))
            ->get();
        //payment collection amount
        $todayPayment = PaymentCollection::whereDate('date', today())
            ->sum('amount');
        $currentMonthPayment = PaymentCollection::whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->sum('amount');
        $lastMonth = now()->subMonth()->month;
        $lastMonthYear = now()->subMonth()->year;
        $lastMonthPayment = PaymentCollection::whereMonth('date', $lastMonth)
            ->whereYear('date', $lastMonthYear)
            ->sum('amount');
        $data['today_payment'] = $todayPayment;
        $data['current_month_payment'] = $currentMonthPayment;
        $data['last_month_payment'] = $lastMonthPayment;


//        $salesAmount = DB::table('invoices')->selectRaw("SUM(total_amount) as total_amount,SUM(discount) as discount,SUM(net_amount) as net_amount")
//            ->first();
//        $data['sales_amount'] = $salesAmount->total_amount;
//        $data['sales_discount'] = $salesAmount->discount;
//        $data['sales_net_amount'] = $salesAmount->net_amount;
        // Order status donut chart
        $salesAmount = DB::table('invoices')->selectRaw("SUM(total_amount) as total_amount,SUM(discount) as discount,SUM(net_amount) as net_amount")
            ->first();
        $balance = $salesAmount->net_amount - $currentMonthPayment;
        $data['balance'] = ceil($balance);

        $data['rediaBar'] = [
            'series' => [
                (int)$salesAmount->total_amount,
                (int)$salesAmount->discount,
                (int)$salesAmount->net_amount,
                (int)$data['current_month_payment'],
                (int)$balance
            ],
            'chartOptions' => [
                'chart' => [
                    'type' => 'radialBar'],
                'plotOptions' => [
                    'radialBar' => [
                        'offsetY' => 0,
                        'startAngle' => 0,
                        'endAngle' => 270,
                        'hollow' => ['margin' => 5, 'size' => '30%', 'background' => 'transparent', 'image' => null,],
                        'dataLabels' => [
                            'name' => ['show' => false,],
                            'value' => ['show' => false,],
                        ],
                        'barLabels' => ['enabled' => true, 'useSeriesColors' => true, 'offsetX' => -8, 'fontSize' => '16px',],
                    ],
                ],
                'colors' => ['#bb8938', '#31ff42', '#79d8df', '#c5ea1f','#ff4d4f'],
                'labels' => ['Total', 'Discount', 'Payable', 'Paid','Balance'],
                'responsive' => [[
                    'breakpoint' => 100,
                    'options' => [
                        'legend' => ['show' => false],
                    ]],
                ],
            ],
        ];

        $dealerSales = DB::table('invoices')->select(DB::raw('COUNT(*) as total'))
            ->whereNull('customer_id')->whereNotNull('dealer_id')
            ->value('total');
        $CustomerSales = DB::table('invoices')->select(DB::raw('COUNT(*) as total'))
            ->whereNull('dealer_id')->whereNotNull('customer_id')
            ->value('total');

        $data['salesCountChart'] = [
            'series' => [
                $dealerSales,
                $CustomerSales,
            ],
            'chartOptions' => [
                'chart' => [
                    'type' => 'donut',
                    'height' => 250,
                    'foreColor' => "#fff"
                ],
                'colors' => [
                    '#00E396', // Dealer
                    '#FEB019', // Customer
                ],
                'labels' => [
                    'Sales By Dealer',
                    'Sales By Customer',
                ],
                'legend' => [
                    'position' => 'bottom'
                ]
            ]
        ];

        $orderCounts = Order::selectRaw('order_status, COUNT(*) as total')
            ->groupBy('order_status')
            ->get()->keyBy('order_status');

        $orderCounts0 = $orderCounts[0]->total ?? 0;
        $orderCounts1 = $orderCounts[1]->total ?? 0;
        $orderCounts2 = $orderCounts[2]->total ?? 10;
        $orderCounts3 = $orderCounts[3]->total ?? 15;
        $orderCounts4 = $orderCounts[4]->total ?? 0;

        $data['salesAmountChart'] = [
            'series' => [
                $orderCounts0,
                $orderCounts1,
                $orderCounts2,
                $orderCounts3,
                $orderCounts4,
            ],
            'chartOptions' => [
                'chart' => [
                    'type' => 'donut',
                    'height' => 250,
                    'foreColor' => "#fff"
                ],
                'labels' => [
                    "Pending ($orderCounts0)", "Approved ($orderCounts1)", "Rejected ($orderCounts2)",
                    "Invoiced ($orderCounts3)", "Delivered ($orderCounts4)"
                ],
                'legend' => [
                    'position' => 'bottom'
                ]
            ]
        ];


        // Monthly orders chart (last 6 months)
        $ordersByMonth = OrderItem::select(
            DB::raw('MONTH(created_at) as month'), // order_items created_at অনুযায়ী
            DB::raw('COUNT(*) as total')
        )
            ->where('status', 1)// Active items
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())// last 12 months
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        $months = [];
        $counts = [];
        for ($i = 11; $i >= 0; $i--) {
            $monthName = now()->subMonths($i)->format('F');
            $monthNumber = now()->subMonths($i)->month;
            $months[] = $monthName;

            $count = $ordersByMonth->firstWhere('month', $monthNumber)->total ?? 0;
            $counts[] = $count;
        }

        $data['monthly_order_items_chart'] = [
            'series' => [
                [
                    'name' => 'Order Items',
                    'data' => $counts
                ]
            ],
            'chartOptions' => [
                'chart' => [
                    'type' => 'bar',
                    'height' => 350,
                    'stacked' => true,
                    'foreColor' => '#fff'
                ],
                'plotOptions' => [
                    'bar' => [
                        'horizontal' => false,
                        'columnWidth' => '50%',
                        'endingShape' => 'rounded'
                    ]
                ],
                'dataLabels' => ['enabled' => false],
                'xaxis' => [
                    'categories' => $months
                ]
            ]
        ];


        $invoiceItemsByMonth = InvoiceItem::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('COUNT(*) as total')
        )
            ->where('status', 1)
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        $months = [];
        $counts = [];
        for ($i = 11; $i >= 0; $i--) {
            $monthName = now()->subMonths($i)->format('F');
            $monthNumber = now()->subMonths($i)->month;
            $months[] = $monthName;

            $count = $invoiceItemsByMonth->firstWhere('month', $monthNumber)->total ?? 0;
            $counts[] = $count;
        }

        $data['monthly_sale_items_chart'] = [
            'series' => [
                [
                    'name' => 'Sale Items',
                    'data' => $counts
                ]
            ],
            'chartOptions' => [
                'chart' => [
                    'type' => 'bar',
                    'height' => 350,
                    'foreColor' => '#fff'
                ],
                'plotOptions' => [
                    'bar' => [
                        'horizontal' => true, // 👈 horizontal chart
                        'columnWidth' => '50%',
                        'endingShape' => 'rounded'
                    ]
                ],
                'dataLabels' => ['enabled' => false],
                'xaxis' => [
                    'categories' => $months
                ]
            ]
        ];


        return returnData(2000, $data, null);
    }

    public function index()
    {
//        Invoice::
//        Product::
//        Warehouse::
//        Order::
//        Dealer::
//        Customer::

        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

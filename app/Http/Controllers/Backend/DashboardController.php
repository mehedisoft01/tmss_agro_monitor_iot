<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Accounting\PaymentCollection;
use App\Models\Dealer\Dealer;
use App\Models\Inventory\Warehouse;
use App\Models\ProductManagement\Product;
use App\Models\Sales\Customer;
use App\Models\Sales\Invoice;
use App\Models\Sales\InvoiceItem;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use Faker\Provider\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// ✅ DB facade add করা

class DashboardController extends Controller
{
    use Helper;

    public function singleApp()
    {
        return view('backend');
    }

    public function employeeApp()
    {
        return view('backend');
    }

    public function dashboardData()
    {
        $authUser = auth()->user();
        $user = auth()->user();
        $isSalesman = $this->isSalesManger();
        $color = ($authUser->theme && $authUser->theme == 'bg-default bg-theme2') ? '#000000' : '#FFFFFF';

        $userId = auth()->id();
        $lowerUserIds = myLowerUserIds();

        $from_date = request()->input('from_date') ?? date('Y-m-01');
        $to_date = request()->input('to_date') ?? date('Y-m-t');

        $lastMonthStartDate = date('Y-m-01', strtotime($from_date . ' -1 month'));
        $lastMonthEndDate = date('Y-m-t', strtotime($to_date . ' -1 month'));


        $data = [];
        $data['active_products'] = Product::where('status', 1)
            ->when($isSalesman, function ($query) use ($user) {
                $query->where('user_id', $user->id);})
            ->whereHas('stocks', function ($query) use ($user) {
                if ($user->warehouse_id) {
                    $query->where('warehouse_id', $user->warehouse_id);
                }
            })
            ->count();
        $data['total_dealers'] = Dealer::where('approval_status', 1)
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

        // Total Orders

        $data['current_month_total_order'] = Order::where('status', 1)
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

        $lastMonthDate = now()->subMonth();
        $data['last_month_total_order'] = Order::where('status', 1)
            ->whereBetween('order_date', [$lastMonthStartDate, $lastMonthEndDate])
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
            ->count();


        // Current month total sale items
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $data['current_month_total_sale_items'] = InvoiceItem::join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->when($isSalesman, function ($query) use ($user) {
                $query->where('invoices.created_by', $user->id);})
            ->where('invoice_items.status', 1)
//            ->whereBetween('invoice_items.created_at', [$from_date, $to_date])
            ->sum('invoice_items.quantity');
//        $data['last_month_total_sale_items'] = InvoiceItem::join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
//            ->where('invoice_items.status', 1)
//            ->whereBetween('invoice_items.created_at', [$lastMonthStartDate, $lastMonthEndDate])
//            ->where(function ($query) use ($user) {
//                if ($user->division_id){
//                    $query->where('division_id', $user->division_id);
//                }
//                if ($user->district_id){
//                    $query->where('district_id', $user->district_id);
//                }
//                if ($user->warehouse_id){
//                    $query->where('warehouse_id', $user->warehouse_id);
//                }
//            })->count();
        $data['last_month_total_sale_items'] = InvoiceItem::join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoice_items.status', 1)
            ->whereBetween('invoice_items.created_at', [$lastMonthStartDate, $lastMonthEndDate])
            ->where(function ($query) use ($user) {
                if ($user->division_id){
                    $query->where('invoices.division_id', $user->division_id);
                }
                if ($user->district_id){
                    $query->where('invoices.district_id', $user->district_id);
                }
                if ($user->warehouse_id){
                    $query->where('invoices.warehouse_id', $user->warehouse_id);
                }
            })->count();


        //payment collection amount
        $currentMonthPayment = PaymentCollection::
//        when(($from_date && $to_date),
//            function ($q) use ($from_date, $to_date) {
//                $q->whereBetween('date', [$from_date, $to_date]);
//            })
//            ->
        when($isSalesman, function ($query) use ($user) {
                $query->where('user_id', $user->id);})
            ->sum('amount');

        $lastMonthPayment = PaymentCollection::when(($lastMonthStartDate && $lastMonthEndDate),
            function ($q) use ($lastMonthStartDate, $lastMonthEndDate) {
                $q->whereBetween('date', [$lastMonthStartDate, $lastMonthEndDate]);
            })
            ->sum('amount');
        $data['current_month_payment'] = round($currentMonthPayment);
        $data['last_month_payment'] = $lastMonthPayment;


        $salesAmount = DB::table('invoices')->selectRaw("SUM(total_amount) as total_amount,SUM(discount) as discount,SUM(net_amount) as net_amount")
            ->when($isSalesman, function ($query) use ($user) {
                $query->where('created_by', $user->id);
            })
            ->first();

        $balance = $salesAmount->net_amount - $currentMonthPayment;
        $data['balance'] = ceil($balance);

        // Order status donut chart
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
            ->when($isSalesman, function ($query) use ($user) {
                $query->where('created_by', $user->id);
            })
            ->value('total');
        $CustomerSales = DB::table('invoices')->select(DB::raw('COUNT(*) as total'))
            ->whereNull('dealer_id')->whereNotNull('customer_id')
            ->when($isSalesman, function ($query) use ($user) {
                $query->where('created_by', $user->id);
            })
            ->value('total');

        $data['salesCountChart'] = [
            'series' => [
                $dealerSales,
                $CustomerSales,
            ],
            'chartOptions' => [
                'chart' => [
                    'type' => 'donut',
                    'foreColor' => "$color"
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
            ->when($isSalesman, function ($query) use ($user) {
                $query->where('created_by', $user->id);
            })
            ->groupBy('order_status')
            ->get()->keyBy('order_status');

        $orderCounts0 = $orderCounts[3]->total ?? 0;
        $orderCounts1 = $orderCounts[0]->total ?? 0;
        $orderCounts2 = $orderCounts[5]->total ?? 0;

        $data['salesAmountChart'] = [
            'series' => [
                $orderCounts0,
                $orderCounts1,
                $orderCounts2,
            ],
            'chartOptions' => [
                'chart' => [
                    'type' => 'donut',
                    'foreColor' => "$color"
                ],
                'labels' => [
                    "Confirmed ($orderCounts0)",
                    "Pending ($orderCounts1)",
                    "Cancel ($orderCounts2)",
                ],
                'colors' => [
                    '#00E396',
                    '#FFC107',
                    '#DC3545',
                ],
                'legend' => [
                    'position' => 'bottom'
                ]
            ]
        ];

        // Monthly orders chart (last 6 months)
        $ordersByMonth = OrderItem::join('orders', function ($join) {
            $join->on('order_items.customer_order_id', '=', 'orders.id')
                ->orOn('order_items.dealer_order_id', '=', 'orders.id');
        })
            ->select(
            DB::raw("DATE_FORMAT(order_items.created_at, '%Y-%m') as ym"),
            DB::raw('COUNT(*) as total')
        )
            ->where('order_items.status', 1)
            ->when($isSalesman, function ($query) use ($user) {
                $query->where('orders.created_by', $user->id);
            })
            ->where('order_items.created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('ym')
            ->orderBy('ym', 'asc')
            ->get()
            ->keyBy('ym');


        $months = [];
        $counts = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $key = $date->format('Y-m');

            $months[] = $date->format('F Y');
            $counts[] = $ordersByMonth[$key]->total ?? 0;
        }


        $data['monthly_order_items_chart'] = [
            'series' => [
                ['name' => 'Order Items', 'data' => $counts]
            ],
            'chartOptions' => [
                'chart' => ['type' => 'bar', 'stacked' => true, 'foreColor' => "$color"],
                'plotOptions' => [
                    'bar' => [
                        'horizontal' => false,
                        'columnWidth' => '50%',
                        'endingShape' => 'rounded'
                    ]
                ],
                'dataLabels' => ['enabled' => false],
                'xaxis' => ['categories' => $months],
                'tooltip' => ['theme' => 'dark']
            ]
        ];

        $salesBySalesMan = Invoice::join('users', function ($join) {
            $join->on('users.id', '=', 'invoices.created_by');
        })->when($isSalesman, function ($query) use ($user) {
            $query->where('created_by', $user->id);
        })
            ->selectRaw('users.name, SUM(invoices.total_amount) as amount')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('amount')
            ->limit(10)
            ->get();


        $users = [];
        $saleAmount = [];
        foreach ($salesBySalesMan as $eachData) {
            $users[] = $eachData->name;
            $saleAmount[] = $eachData->amount;
        }

        $data['top_ten_sales_man_sales'] = [
            'series' => [
                ['name' => 'Sale Amounts', 'data' => $saleAmount]
            ],
            'chartOptions' => [
                'chart' => ['type' => 'bar', 'stacked' => true, 'foreColor' => "$color"],
                'plotOptions' => [
                    'bar' => ['horizontal' => false, 'columnWidth' => '50%', 'endingShape' => 'rounded']
                ],
                'dataLabels' => ['enabled' => false],
                'xaxis' => ['categories' => $users],
                'tooltip' => [
                    'theme' => 'dark'
                ]
            ]
        ];

//        $invoiceItemsByMonth = InvoiceItem::select(
//            DB::raw('MONTH(created_at) as month'),
//            DB::raw('COUNT(*) as total')
//        )
//            ->where('status', 1)
//            ->when($isSalesman, function ($query) use ($user) {
//                $query->where('created_by', $user->id);
//            })
//            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
//            ->groupBy('month')
//            ->orderBy('month', 'asc')
//            ->get();
        $invoiceItemsByMonth = InvoiceItem::join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->select(
                DB::raw('MONTH(invoice_items.created_at) as month'),
                DB::raw('COUNT(*) as total')
            )
            ->where('invoice_items.status', 1)
            ->when($isSalesman, function ($query) use ($user) {
                $query->where('invoices.created_by', $user->id);
            })
            ->where('invoice_items.created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy(DB::raw('MONTH(invoice_items.created_at)'))
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
                    'foreColor' => "$color"
                ],
                'plotOptions' => [
                    'bar' => [
                        'horizontal' => true, // 👈 horizontal chart
                        'columnWidth' => '50%',
                        'endingShape' => 'rounded'
                    ]
                ],
                'dataLabels' => ['enabled' => false],
                'xaxis' => ['categories' => $months],
                'tooltip' => ['theme' => 'dark']
            ]
        ];

        return $data;


//        return returnData(2000, $data, null);
    }
}

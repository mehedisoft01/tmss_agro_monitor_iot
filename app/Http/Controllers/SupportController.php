<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Http\Controllers\Backend\DashboardController;
use App\Models\ActivityLog;
use App\Models\Address\District;
use App\Models\Address\Division;
use App\Models\Address\Upazila;
use App\Models\AppNotification;
use App\Models\Dealer\Dealer;
use App\Models\HumanResource\Salesman;
use App\Models\HumanResource\StaffDesignation;
use App\Models\Inventory\Stock;
use App\Models\Inventory\Warehouse;
use App\Models\ProductManagement\Pricing;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductBrand;
use App\Models\ProductManagement\ProductCategory;
use App\Models\ProductManagement\ProductUnit;
use App\Models\RBAC\Module;
use App\Models\RBAC\Permission;
use App\Models\RBAC\Role;
use App\Models\Sales\Customer;
use App\Models\Sales\Invoice;
use App\Models\Sales\InvoiceItem;
use App\Models\Sales\Order;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use SebastianBergmann\CodeCoverage\Report\Html\Dashboard;
use function Carbon\this;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SupportController extends Controller
{
    use Helper;

    public function appConfigurations()
    {
        $role_id = auth()->user()->role_id;
        $user_id = auth()->user()->id;

        $data['user'] = User::where('id', $user_id)->first();
        $data['configs'] = configs(['logo', 'app_name', 'app_logo', 'notify_per_minuit', 'salesman_role']);

        $permissions = Permission::whereHas('role_permissions', function ($query) use ($role_id) {
            $query->where('role_id', $role_id);
        })->get();

        $permittedModules = collect($permissions)->pluck('module_id');
        $data['permissions'] = collect($permissions)->pluck('name');

        $locals = [];
        $files = glob(resource_path('lang/*.json'));
        foreach ($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            $locals[] = ['locale' => $name, 'name' => $name];
        }
        $data['localization'] = $this->getLocals();

        $data['menus'] = Module::where('parent_id', 0)->where('is_visible', 1)
            ->whereIn('id', $permittedModules)
            ->with(['submenus' => function ($query) use ($permittedModules) {
                $query->with('submenus')->where('is_visible', 1);
                $query->whereIn('id', $permittedModules);
                $query->with(['submenus' => function ($query) use ($permittedModules) {
                    $query->with('submenus')->where('is_visible', 1);
                    $query->whereIn('id', $permittedModules);
                }]);
            }])->get();

        return returnData(2000, $data);
    }

    public function getLocals()
    {
        $locals = [];
        $files = glob(resource_path('lang/*.json'));
        foreach ($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            $locals[] = ['locale' => $name, 'name' => $name];
        }

        return $locals;
    }

    public function loadJson()
    {
        $jsonData = [
            'locale' => $this->getLocalization(true),
            'routes' => $this->getRoutes(true),
        ];
        return response()->json(json_encode($jsonData));
    }

    public function addLocalization(Request $request)
    {
        $item = $request->input('item');
        if ($item) {
            try {
                $string = str_replace('_', ' ', $item);
                $file = resource_path('lang/en.json');
                $json = file_get_contents($file);
                $data = json_decode($json, true);
                $data[$item] = ucwords($string);
                $newJson = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                file_put_contents($file, $newJson);
                return true;
            } catch (\Exception $exception) {
                return false;
            }
        }
    }

    public function getLocalization($inSide = false)
    {
        $locals = [];
        $files = glob(resource_path('lang/*.json'));

        foreach ($files as $file) {
            $locale = pathinfo($file, PATHINFO_FILENAME);
            $locals[$locale] = json_decode(file_get_contents($file), true) ?? [];
        }

        if ($inSide) {
            return $locals;
        }

        return response()->json(json_encode($locals));
    }

    public function getRoutes($inSide = false)
    {
        $select = "id, name, link as path, component, meta, parent_id, icon";
        $routes = [
            [
                "path" => "/",
                "name" => "app",
                "component" => "views/layouts/AppLayouts.vue",
                "children" => Module::selectRaw($select)->where('parent_id', 0)
                    ->with(['children' => function ($query) use ($select) {
                        $query->selectRaw($select);
                        $query->with(['children' => function ($query2) use ($select) {
                            $query2->selectRaw($select);
                        }]);
                    }])->get()
            ],
        ];

        if ($inSide) {
            return $routes;
        }

        return response()->json(json_encode($routes));
    }

    public function getGeneralData()
    {
        $input = request()->all();
        $data = [];
        $user = auth()->user();

        if (isset($input['authUser']) || in_array('authUser', $input)) {
            $getRoll = DB::table('settings')->where('key', 'salesman_role')->first();
            $authId = auth()->user()->id;
            $key = isset($input['authUser']['key']) ? isset($input['authUser']['key']) : 'authUser';
            $data[$key] = User::where('status', 1)->where('id', $authId)->where('role_id', $getRoll->value)->orderBy('id', 'DESC')->get();

        }

        if (isset($input['permissions']) || in_array('permissions', $input)) {
            $key = isset($input['permissions']['key']) ? isset($input['permissions']['key']) : 'permissions';
            $data[$key] = ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy', 'status'];
        }
        if (isset($input['roles']) || in_array('roles', $input)) {
            $key = isset($input['roles']['key']) ? isset($input['roles']['key']) : 'roles';
            $data[$key] = Role::where('status', 1)->get();
        }


        if (isset($input['product_categories']) || in_array('product_categories', $input)) {
            $key = isset($input['product_categories']['key']) ? $input['product_categories']['key'] : 'product_categories';
            $data[$key] = ProductCategory::checkWarehouse()
                ->where('status', 1)
                ->orderBy('id', 'DESC')
                ->get();
        }


        if (isset($input['products']) || in_array('products', $input)) {
            $key = isset($input['products']['key']) ? $input['products']['key'] : 'products';
            $warehouseId = isset($input['products']['warehouseId']) ? $input['products']['warehouseId'] : null;
            $withSerial = isset($input['products']['withSerial']) ? $input['products']['withSerial'] : null;

            $data[$key] = Product::Join('product_serial_groups as psg', 'psg.product_id', '=', 'products.id')
                ->leftJoin('warehouses as w', 'w.id', '=', 'psg.warehouse_id')
                ->checkWarehouse()
                ->where('products.status', 1)
                ->selectRaw("psg.id as serial_group_id, products.id, CONCAT(product_name,' (MRP: ', psg.selling_price,', DP: ', psg.dealer_price, ')') AS product_name, product_code,psg.selling_price,psg.dealer_price,psg.warehouse_id,warehouse_name,markup_percentage,psg.cost_price")
                ->withCount([
                    'serials as quantity' => function ($q) {
                        $q->where('status', 0)
                            ->whereColumn('product_serials.serial_group_id', 'psg.id');
                    }
                ])
                ->when($warehouseId, function ($query) use ($warehouseId) {
                    $query->where('psg.warehouse_id', $warehouseId);
                })
                ->whereHas('serials', function ($q) {
                    $q->where('status', 0)
                        ->whereColumn('product_serials.serial_group_id', 'psg.id');

                })
                ->orderBy('id', 'DESC')
                ->get();
//                ->map(function ($product) {
//                    return [
//                        'id' => $product->id,
//                        'product_name' => $product->product_display_name,
//                        'product_code' => $product->product_code,
//                        'selling_price' => $product->selling_price,
//                        'dealer_price' => $product->dealer_price,
//                        'warehouse_id' => $product->warehouse_id,
//                        'markup_percentage' => $product->markup_percentage,
//                        'cost_price' => $product->cost_price,
//                        'quantity' => $product->stocks->sum('current_stock')
//                    ];
//                });
        }

        if (isset($input['productsItem']) || in_array('productsItem', $input)) {
            $key = isset($input['productsItem']['key']) ? $input['productsItem']['key'] : 'productsItem';

            $data[$key] = Product::checkWarehouse()
                ->where('status', 1)
                ->select('id', 'product_name', 'product_code', 'selling_price', 'dealer_price', 'warehouse_id', 'markup_percentage', 'cost_price')
                ->with(['stocks' => function ($query) {
                    $query->select('product_id', 'current_stock');
                }])
                ->orderBy('id', 'DESC')
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'product_name' => $product->product_name,
                        'product_code' => $product->product_code,
                        'selling_price' => $product->selling_price,
                        'dealer_price' => $product->dealer_price,
                        'warehouse_id' => $product->warehouse_id,
                        'markup_percentage' => $product->markup_percentage,
                        'cost_price' => $product->cost_price,
                        'quantity' => $product->stocks->sum('current_stock')
                    ];
                });
        }

        if (isset($input['productTransfer']) || in_array('productTransfer', $input)) {

            $key = isset($input['productTransfer']['key'])
                ? $input['productTransfer']['key']
                : 'productTransfer';

            $data[$key] = Stock::join('products', 'products.id', '=', 'stocks.product_id')
                ->leftJoin('product_serials as ps', function ($join) {
                    $join->on('ps.product_id', '=', 'stocks.product_id')
                        ->whereRaw('ps.id = (
                     SELECT MIN(id)
                     FROM product_serials
                     WHERE product_id = stocks.product_id
                 )');
                })
                ->checkWarehouse()
                ->where('stocks.status', 1)
                ->selectRaw("
            stocks.product_id as id,
            stocks.warehouse_id,
            products.product_name as product_name,
            products.product_code,
            ps.serial_group_id,
            stocks.unit_cost as cost_price,
            stocks.shipping_cost,
            stocks.selling_price,
            stocks.dealer_price
        ")->groupBy('stocks.product_id')
                ->orderBy('stocks.id', 'DESC')
                ->get();
        }


        if (isset($input['stockAdjustments']) || in_array('stockAdjustments', $input)) {
            $key = isset($input['stockAdjustments']['key']) ? $input['stockAdjustments']['key'] : 'stockAdjustments';

            $data[$key] = Product::checkWarehouse()
                ->where('status', 1)
                ->select('id', 'product_name', 'product_code', 'selling_price', 'dealer_price', 'warehouse_id', 'cost_price')
                ->with(['stocks' => function ($query) {
                    $query->select('id', 'product_id', 'warehouse_id', 'current_stock');
                }])
                ->orderBy('id', 'DESC')
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'product_name' => $product->product_name,
                        'product_code' => $product->product_code,
                        'selling_price' => $product->selling_price,
                        'dealer_price' => $product->dealer_price,
                        'warehouse_id' => $product->warehouse_id,
                        'cost_price' => $product->cost_price,
                        'quantity' => $product->stocks
                            ->where('warehouse_id', $product->warehouse_id)
                            ->sum('current_stock')
                    ];
                });
        }


        if (isset($input['invoices']) || in_array('invoices', $input)) {
            $key = isset($input['invoices']['key']) ? isset($input['invoices']['key']) : 'invoices';
            $data[$key] = Invoice::where('status', 1)->get();
        }

        if (isset($input['invoiceItem']) || in_array('invoiceItem', $input)) {
            $key = isset($input['invoiceItem']['key']) ? isset($input['invoiceItem']['key']) : 'invoiceItem';
            $data[$key] = InvoiceItem::where('status', 1)->get();
        }
        if (isset($input['settings']) || in_array('settings', $input)) {
            $settings = isset($input['settings']) ? $input['settings'] : [];
            $settings = Setting::when((count($settings) > 0), function ($query) use ($settings) {
                return $query->whereIn('key', $settings);
            })->get()->pluck('value', 'key')->toArray();

            $data['settings'] = (object)$settings;
        }

        if (isset($input['warehouses']) || in_array('warehouses', $input)) {
            $key = isset($input['warehouses']['key']) ? $input['warehouses']['key'] : 'warehouses';
            $user = auth()->user();

            $data[$key] = Warehouse::where('status', 1)
                ->select('warehouses.*')
                ->selectRaw("warehouses.id, CONCAT(warehouse_name, IF(office_type = 2, ' (Display Office)', '')) AS warehouse_name")
                ->where(function ($query) use ($user) {
                    if ($user->division_id) {
                        $query->where('division_id', $user->division_id);
                    }
                    if ($user->district_id) {
                        $query->where('district_id', $user->district_id);
                    }
                    if ($user->warehouse_id) {
                        $query->where('id', $user->warehouse_id);
                    }
                })
                ->orderBy('id', 'DESC')
                ->get();
        }

        if (isset($input['dealers']) || in_array('dealers', $input)) {
            $key = isset($input['dealers']['key']) ? isset($input['dealers']['key']) : 'dealers';
            $data[$key] = Dealer::where('status', 1)
                ->whereHas('address', function ($query) use ($user) {

                    $query->where('type', 1);

                    if ($user->division_id) {
                        $query->where('p_division_id', $user->division_id);
                    }

                    if ($user->district_id) {
                        $query->where('p_district_id', $user->district_id);
                    }
                })
                ->where('approval_status', 1)
                ->orderBy('id', 'DESC')
                ->get();
        }

        if (isset($input['product_units']) || in_array('product_units', $input)) {
            $key = isset($input['product_units']['key']) ? isset($input['product_units']['key']) : 'product_units';
            $data[$key] = ProductUnit::where('status', 1)->get();
        }

        if (isset($input['customers']) || in_array('customers', $input)) {
            $key = isset($input['customers']['key']) ? $input['customers']['key'] : 'customers';
            $data[$key] = Customer::where('status', 1)
                ->whereHas('address', function ($query) use ($user) {

                    $query->where('type', 1);

                    if ($user->division_id) {
                        $query->where('p_division_id', $user->division_id);
                    }

                    if ($user->district_id) {
                        $query->where('p_district_id', $user->district_id);
                    }
                })
                ->when($user->warehouse_id, function ($query) use ($user) {
                    $query->where(function ($q) use ($user) {
                        $q->whereNull('warehouse_id')
                            ->orWhere('warehouse_id', $user->warehouse_id);
                    });
                })
                ->orderBy('id', 'DESC')
                ->get();
        }

        if (isset($input['orders']) || in_array('orders', $input)) {
            $key = isset($input['orders']['key']) ? $input['orders']['key'] : 'orders';
            $data[$key] = Order::where('orders.order_status', 0)
                ->checkWarehouse('orders.warehouse_id')
                ->leftJoin('customers', 'customers.id', '=', 'orders.customer_id')
                ->leftJoin('dealers', 'dealers.id', '=', 'orders.dealer_id')
                ->select(
                    'orders.id',
                    'orders.order_no',
                    DB::raw("
                   CASE WHEN orders.customer_id IS NOT NULL THEN customers.name WHEN orders.dealer_id IS NOT NULL THEN CONCAT(dealers.name, ' (Dealer)') END as name")
                )
                ->orderBy('orders.id', 'DESC')
                ->get();
        }


        if (isset($input['invoice']) || in_array('invoice', $input)) {
            $key = isset($input['invoice']['key']) ? isset($input['invoice']['key']) : 'invoice';

            $user = auth()->user();
            $isSalesman = $this->isSalesManger();

            $data[$key] = Invoice::leftJoin('dealer_ledgers', 'invoices.id', '=', 'dealer_ledgers.invoice_id')
                ->where('invoices.status', 1)
                ->selectRaw("
                    invoices.id,
                    invoices.invoice_no,
                    invoices.order_no,
                    SUM(CASE WHEN dealer_ledgers.transaction_type = 1 THEN COALESCE(dealer_ledgers.debit,0) ELSE 0 END) AS total_debit,
                    SUM(CASE WHEN dealer_ledgers.transaction_type = 2 THEN COALESCE(dealer_ledgers.credit,0) ELSE 0 END) AS total_credit
                ")

                ->when($isSalesman, function ($query) use ($user) {
                    $query->where('created_by', $user->id);
                })

                ->groupBy('invoices.id', 'invoices.invoice_no', 'invoices.order_no')
                ->havingRaw('total_debit > total_credit')
                ->orderBy('id', 'DESC')
                ->get();
        }


        if (isset($input['brands']) || in_array('brands', $input)) {
            $key = isset($input['brands']['key']) ? $input['brands']['key'] : 'brands';

            $data[$key] = ProductBrand::checkWarehouse()
                ->orderBy('id', 'DESC')
                ->get();
        }

        if (isset($input[0]['salesman']) || in_array('salesman', $input)) {
            $users = auth()->user();
            $key = isset($input['salesman']['key']) ? $input['salesman']['key'] : 'salesman';
            $warehouseId = isset($input['salesman']['warehouseId']) ? $input['salesman']['warehouseId'] : $users->warehouse_id;

            $data[$key] = Salesman::selectRaw("salesmen.*,0 as basic, 0 as daily_salary, 0 as hourly_salary, 0 as allowance, 1 as is_salesman, 1 as is_commission_applicable, staff_designations.designation_name")
                ->leftJoin('warehouses', 'warehouses.id', '=', 'salesmen.warehouse_id')
                ->leftJoin('staff_designations', 'staff_designations.id', '=', 'salesmen.designation_id')
                ->where('salesmen.status', 1)
                ->when($warehouseId, function ($query) use ($warehouseId) {
                    $query->where('salesmen.warehouse_id', $warehouseId);
                })
                ->where(function ($query) use ($user) {
                    if ($user->division_id) {
                        $query->where('warehouses.division_id', $user->division_id);
                    }
                    if ($user->district_id) {
                        $query->where('warehouses.district_id', $user->district_id);
                    }
                    if ($user->warehouse_id) {
                        $query->where('warehouses.id', $user->warehouse_id);
                    }
                })
                ->orderBy('salesmen.id', 'DESC')
                ->get();
        }
        if (isset($input[0]['salespersons']) || in_array('salespersons', $input)) {
            $key = isset($input[0]['salespersons']['key']) ? $input[0]['salespersons']['key'] : 'salespersons';
            $salesmanRoles = ['district_manager_role', 'salesman_role'];
            $salesmanRoleIds = Setting::whereIn('key', $salesmanRoles)->get()->pluck('value')->toArray();

            $data[$key] = User::selectRaw("salesmen.id, salesmen.name, display_name as role_name")
                ->join('roles', 'roles.id', '=', 'users.role_id')
                ->join('salesmen', 'salesmen.id', '=', 'users.salesman_id')
                ->where('users.status', 1)
                ->whereIn('roles.id', $salesmanRoleIds)
                ->where(function ($query) use ($user) {
                    if ($user->division_id) {
                        $query->where('users.division_id', $user->division_id);
                    }
                    if ($user->district_id) {
                        $query->where('users.district_id', $user->district_id);
                    }
                    if ($user->warehouse_id) {
                        $query->where('users.warehouse_id', $user->warehouse_id);
                    }
                })
                ->orderBy('users.id', 'ASC')
                ->get();
        }

        if (isset($input['components']) || in_array('components', $input)) {
            $files = File::allFiles(resource_path('js/views'));
            $components = [];

            $basePath = resource_path('js');
            foreach ($files as $file) {
                $relativePath = str_replace($basePath . '/', '', $file->getPathname());
                $components[] = $relativePath;
            }
            $data['components'] = $components;
        }

        if (isset($input['division']) || in_array('division', $input)) {
            $key = isset($input['division']['key']) ? isset($input['division']['key']) : 'division';
            $data[$key] = Division::when($user->division_id, function ($query) use ($user) {
                $query->where('id', $user->division_id);
            })->where('status', 1)->get();
        }
        if (isset($input['district']) || in_array('district', $input)) {
            $key = isset($input['district']['key']) ? isset($input['district']['key']) : 'district';
            $data[$key] = District::when($user->district_id, function ($query) use ($user) {
                $query->where('id', $user->district_id);
            })
                ->when($user->division_id, function ($query) use ($user) {
                    $query->where('division_id', $user->division_id);
                })
                ->where('status', 1)->get();
        }
        if (isset($input['upazila']) || in_array('upazila', $input)) {
            $key = isset($input['upazila']['key']) ? isset($input['upazila']['key']) : 'upazila';
            $data[$key] = Upazila::where('status', 1)->get();
        }

        if (isset($input['app_download_link']) || in_array('app_download_link', $input)) {
            $key = isset($input['app_download_link']['key']) ? $input['app_download_link']['key'] : 'app_download_link';

            $data[$key] = Setting::where('key', 'app_download_link')
                ->where('is_visible', 1)
                ->value('value');
        }

        if (isset($input['staff_designation']) || in_array('staff_designation', $input)) {
            $key = isset($input['staff_designation']['key']) ? isset($input['staff_designation']['key']) : 'staff_designation';
            $data[$key] = StaffDesignation::where('status', 1)->get();
        }
        if (isset($input['icons']) || in_array('icons', $input)) {
            $data['icons'] = [
                'bx bx-home-alt',
                'bx bx-lock',
                'bx bx-user-circle',
                'bx bx-radio-circle',
                'bx bx-group',
                'bx bx-tachometer',
                'bx bx-receipt',
                'bx bx-credit-card',
                'bx bx-calculator',
                'bx bx-bolt-circle',
                'bx bx-error',
                'bx bx-bar-chart-alt-2',
                'bx bx-cog',
                'bx bx-help-circle',
            ];
        }
        if (isset($input['fix_holiday']) || in_array('fix_holiday', $input)) {
            $data['fix_holiday'] = [
                ['name' => 'Shaheed Dibash', 'value' => '02-21', 'checked' => 0],
                ['name' => 'Independence Day', 'value' => '03-26', 'checked' => 0],
                ['name' => 'Pahela Baisakh', 'value' => '04-14', 'checked' => 0],
                ['name' => 'May Day', 'value' => '05-01', 'checked' => 0],
                ['name' => 'Victory Day', 'value' => '12-16', 'checked' => 0],
                ['name' => 'Christmas Day', 'value' => '12-25', 'checked' => 0],
            ];
        }

        return returnData(2000, $data);
    }

    public function appDashboard()
    {
        $dashboardObj = new DashboardController();
        $notificationController = new AppNotificationController();
        $notifications = $notificationController->index(true);
        $dashboard = $dashboardObj->dashboardData();


        return returnData(2000, [
            'dashboard' => $dashboard,
            'notifications' => $notifications,
        ]);
    }

    public function userActivities()
    {
        $data = ActivityLog::orderBy('id', 'DESC')->with('user:id,name')
            ->paginate(input('per_page'));

        return returnData(2000, $data);
    }
}

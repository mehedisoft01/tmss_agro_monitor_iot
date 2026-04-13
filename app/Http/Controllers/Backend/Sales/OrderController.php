<?php

namespace App\Http\Controllers\Backend\Sales;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Dealer\Dealer;
use App\Models\Inventory\Stock;
use App\Models\Inventory\Warehouse;
use App\Models\ProductManagement\Product;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Setting;
use function Carbon\this;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    use Helper;

    public function __construct()
    {
        //        if (!can(request()->route()->action['as'])) {
        //            return returnData(5001, null, 'You are not authorized to access this page');
        //        }
        $this->model = new Order();
    }


    public function index()
    {
        if (!can('order_information.index')) {
            return $this->notPermitted();
        }

        try {
            $keyword = request()->input('keyword');
            $dealerId = request()->input('dealer_id');
            $customerId = request()->input('customer_id');
            $dateFrom     = request()->input('order_date_from');
            $dateTo       = request()->input('order_date_to');
            $perPage = request()->input('per_page');
            $user = auth()->user();
            $isSalesman = $this->isSalesManger();

            $data = $this->model
//                ->checkWarehouse()
                ->with(['customer', 'approve:id,name', 'dealer', 'paymentConfirmedByUser', 'createdByUser', 'orderApprovedByUser'])
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where('order_no', 'like', "%$keyword%");
                })
                ->when($dealerId, function ($query) use ($dealerId) {
                    $query->where('dealer_id', 'like', "%$dealerId%");
                })
                ->when($customerId, function ($query) use ($customerId) {
                    $query->where('customer_id', 'like', "%$customerId%");
                })
                ->when($dateFrom && $dateTo, function ($query) use ($dateFrom, $dateTo) {
                    $query->whereBetween('order_date', [$dateFrom, $dateTo]);
                })

                ->when($dateFrom && !$dateTo, function ($query) use ($dateFrom) {
                    $query->whereDate('order_date', '>=', $dateFrom);
                })

                ->when(!$dateFrom && $dateTo, function ($query) use ($dateTo) {
                    $query->whereDate('order_date', '<=', $dateTo);
                })
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
                ->orderBy('id', 'DESC')
                ->paginate($perPage);

            return returnData(2000, $data);
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }


    public function store(Request $request)
    {
        if (!can('order_information.store')) {
            return $this->notPermitted();
        }

        try {
            $auth = auth()->user();

            $input = $request->all();
            $input['user_id'] = auth()->id();

            $validate = $this->model->validate($input);
            if ($validate->fails()) {
                return returnData(2000, $validate->errors());
            }

            $order = new Order();
            $order->order_no = 'ORD-' . date('YmdHis');
            $order->customer_id = $request->customer_id;
            $order->dealer_id = $request->dealer_id;
            $order->division_id = $request->division_id;
            $order->district_id = $request->district_id;
            $order->upazila_id = $request->upazila_id;
            $order->area = $request->area;

            Address::createIfNew(
                $request->dealer_id,
                $request->customer_id,
                $request->division_id,
                $request->district_id,
                $request->upazila_id,
                $request->area
            );

            $order->order_date = $request->order_date;
            $order->order_status = is_numeric($request->order_status) ? (int)$request->order_status : 0;
            $order->total_qty = $request->total_qty;
            $order->total_amount = $request->total_amount;
            $order->discount = $request->discount ?? 0;
            $order->net_amount = $request->net_amount;
            $order->remarks = $request->remarks ?? null;

            if ($request->has('attachment') && is_array($request->attachment)) {
                $order->attachment = json_encode($request->attachment);
            } else {
                $order->attachment = json_encode([]);
            }

            $order->warehouse_id = $auth->warehouse_id ?? null;
            $order->created_by = auth()->id();
            $order->save();

            // 💾 Save Order Items
            foreach ($request->items as $item) {
                $stock = Stock::where('product_id', $item['product_id'])
                    ->where('current_stock', '>', 0)
                    ->orderBy('current_stock', 'desc')
                    ->first();

                if (!$stock) {
                    throw new \Exception("Product out of stock!");
                }

                $product = Product::find($item['product_id']);
                if (!$product) {
                    throw new \Exception("Product not found!");
                }

                OrderItem::create([
                    'customer_order_id' => $order->customer_id ? $order->id : null,
                    'dealer_order_id' => $order->dealer_id ? $order->id : null,
                    'product_id' => $item['product_id'],
                    'warehouse_id' => $stock->warehouse_id,
                    'product_code' => $product->product_code,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                    'remarks' => $item['remarks'] ?? null,
                    'status' => 1,
                    'serial_group_id' => $item['serial_group_id'],
                ]);
            }

            $upperUserIds = myUpperUserIds();
            foreach ($upperUserIds as $userId) {
                $this->addNotification(
                    $auth->id,
                    $userId,
                    'New Order Created',
                    "A new order ({$order->order_no}) has been created.",
                    '/orders/list',
                    1,
                    $order->id
                );
            }


            return returnData(2000, null, 'Order Successfully Inserted');
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }


    public function show($id)
    {
        if (!can('order_information.show')) {
            return $this->notPermitted();
        }

        try {
            $data = $this->model->with([
                'customer', 'createdByUser', 'approve:id,name', 'division:id,division_name',
                'district:id,district_name', 'upazila:id,upazila_name', 'dealer', 'paymentConfirmedByUser', 'orderApprovedByUser',
            ])
                ->findOrFail($id);

            $itemsQuery = OrderItem::join('products', 'products.id', '=', 'order_items.product_id')
                ->join('product_serial_groups as psg', 'psg.id', '=', 'order_items.serial_group_id')
                ->selectRaw("order_items.*, psg.id as serial_group_id, products.id, CONCAT(product_name,' (MRP: ', psg.selling_price,', DP: ', psg.dealer_price, ')') AS product_name, products.product_code,psg.selling_price,psg.dealer_price,psg.warehouse_id,markup_percentage,psg.cost_price")
                ->where(function ($q) use ($data) {

                    if ($data->customer_id) {
                        $q->where('customer_order_id', $data->id);
                    }

                    if ($data->dealer_id) {
                        $q->where('dealer_order_id', $data->id);
                    }

                });

            $items = $itemsQuery->get();
            $data->setAttribute('items', $items);
            $data->attachment = json_decode($data->attachment, true);


            $productIds = $items
                ->pluck('product_id')
                ->filter()
                ->unique()
                ->values();

            $serialGroupId = collect($items)->pluck('serial_group_id')->toArray();


            $user = auth()->user();
            $warehouses = Warehouse::where('status', 1)
                ->when($user->division_id, function ($query) use ($user) {
                    $query->where('division_id', $user->division_id);
                })
                ->when($user->district_id, function ($query) use ($user) {
                    $query->where('district_id', $user->district_id);
                })
                ->when($user->warehouse_id, function ($query) use ($user) {
                    $query->where('id', $user->warehouse_id);
                })
                ->whereHas('serialGroups.serials', function ($query) use ($serialGroupId) {
                    $query->where('status', 0);
                })
                ->withCount(['serialGroups as serial_count' => function ($query) use ($serialGroupId) {
                    $query->whereHas('serials', function ($q) use ($serialGroupId) {
                        $q->where('status', 0);
                    });
                }
                ])
                ->get(['id', 'warehouse_name']);

            $warehouses->transform(function ($warehouse) use ($productIds) {
                $warehouse->products = Product::selectRaw("*, id as product_id")->withCount(['serials as stock' => function ($query) use ($warehouse) {
                    $query->where('status', 0);
                    $query->whereHas('serialGroup', function ($q) use ($warehouse) {
                        $q->where('warehouse_id', $warehouse->id);
                    });
                }
                ])->get();

                return $warehouse;
            });


            $invoice = Invoice::where('order_id', $id)
                ->leftJoin('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
                ->leftJoin('warehouses', 'invoice_items.warehouse_id', '=', 'warehouses.id')
                ->leftJoin('products', 'invoice_items.product_id', '=', 'products.id')
                ->select([
                    'invoice_items.invoice_id as invoice_id',
                    'invoice_items.warehouse_id as warehouse_id',
                    'warehouses.warehouse_name as warehouse_name',
                    'invoice_items.product_id as product_id',
                    'products.product_name as product_name',
                    'invoice_items.product_code as product_code',
                    'invoice_items.quantity as qty',
                ])
                ->get();

            $data->invoiceData = $invoice;
            $data->warehouses = $warehouses;


            $user = auth()->user();

            $isFinanceManager = false;

            $financeRoleSetting = Setting::where('key', 'financial_manager_role')->value('value');

            if ($user && $financeRoleSetting && $user->role_id == (int)$financeRoleSetting) {
                $isFinanceManager = true;
            }

            $data->is_finance_manager = $isFinanceManager;


            return returnData(2000, $data);

        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Record not found or something went wrong.');
        }
    }


    public function edit($id)
    {
        try {
            $data = $this->model->with(['customer'])->findOrFail($id);

            $items = OrderItem::join('products', 'products.id', '=', 'order_items.product_id')
                ->join('product_serial_groups as psg', 'psg.id', '=', 'order_items.serial_group_id')
                ->selectRaw("order_items.*, psg.id as serial_group_id, products.id, CONCAT(product_name,' (MRP: ', psg.selling_price,', DP: ', psg.dealer_price, ')') AS product_name, products.product_code,psg.selling_price,psg.dealer_price,psg.warehouse_id,markup_percentage,psg.cost_price")
                ->where(function ($q) use ($data) {
                    if ($data->customer_id) {
                        $q->where('customer_order_id', $data->id);
                    }

                    if ($data->dealer_id) {
                        $q->orWhere('dealer_order_id', $data->id);
                    }
                })->get();
            $data->setAttribute('items', $items);

            $data->attachment = json_decode($data->attachment, true);

            return returnData(2000, $data);
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }


    public function update(Request $request, $id)
    {
        if (!can('order_information.update')) {
            return $this->notPermitted();
        }

        try {
            $input = $request->all();
            $input['user_id'] = auth()->id();

            $validate = $this->model->validate($input);
            if ($validate->fails()) {
                return returnData(2000, $validate->errors());
            }

            $order = Order::find($id);
            if (!$order) {
                return returnData(2000, null, 'Order Not Found');
            }

            $order->customer_id = $request->customer_id;
            $order->dealer_id = $request->dealer_id;
            $order->division_id = $request->division_id;
            $order->district_id = $request->district_id;
            $order->upazila_id = $request->upazila_id;
            $order->area = $request->area;
            $order->order_date = $request->order_date;
            $order->order_status = $request->order_status ?? $order->order_status;
            $order->total_qty = $request->total_qty;
            $order->total_amount = $request->total_amount;
            $order->discount = $request->discount ?? 0;
            $order->net_amount = $request->net_amount;
            $order->remarks = $request->remarks ?? null;
            $order->attachment = isset($input['attachment']) && !empty($input['attachment']) ? json_encode($input['attachment']) : json_encode([]);
            $order->save();


            OrderItem::where('customer_order_id', $order->id)->delete();
            OrderItem::where('dealer_order_id', $order->id)->delete();

            foreach ($request->items as $item) {
                $stock = Stock::where('product_id', $item['product_id'])
                    ->where('current_stock', '>', 0)
                    ->orderBy('current_stock', 'desc')
                    ->first();
                $product = Product::find($item['product_id']);
                OrderItem::create([
                    'customer_order_id' => $order->customer_id ? $order->id : null,
                    'dealer_order_id' => $order->dealer_id ? $order->id : null,
                    'product_id' => $item['product_id'],
                    'warehouse_id' => $stock->warehouse_id ?? null,
                    'product_code' => $product->product_code ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                    'remarks' => $item['remarks'] ?? null,
                    'serial_group_id' => $item['serial_group_id'] ?? null,
                    'status' => 1,
                ]);
            }

            return returnData(2000, null, 'Order Successfully Updated');
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }


    public function updateAttachment(Request $request, $id)
    {
        try {
            $order = Order::find($id);
            if (!$order) {
                return returnData(2000, null, 'Order Not Found');
            }

            $existingAttachments = $order->attachment ? json_decode($order->attachment, true) : [];

            if ($request->has('attachment') && is_array($request->attachment)) {
                $order->attachment = json_encode(array_merge($existingAttachments, $request->attachment));
            }


            $order->save();

            return returnData(2000, null, 'Attachment Successfully Updated');
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }


    public function destroy($id)
    {
        if (!can('order_information.destroy')) {
            return $this->notPermitted();
        }

        try {
            $data = $this->model->where('id', $id)->first();
            if (!$data) {
                return returnData(5000, null, 'Data Not found');
            }

            $data->delete();

            return returnData(2000, $data, 'Order Successfully Deleted');
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }


    public function orderHistory(Request $request)
    {
        if ($request->customer_id && $request->dealer_id && $request->order_no && $request->order_date_from && $request->order_date_to && $request->order_status === null) {
            return returnData(400, [], 'Please select at least one filter: Order No, Customer, Status, or Date Range');
        }

        $user = auth()->user();
        $lowerUserIds = myLowerUserIds();
        $isSalesman = $this->isSalesManger();

        $query = Order::with('customer', 'dealerItems.product', 'customerItems.product', 'createdByUser', 'dealer')
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
            });

        if ($request->order_no) {
            $query->where('order_no', 'LIKE', "%{$request->order_no}%");
        }

        // if ($user->vi)

        //     if ($request->dealer_id) {
        //         $query->where('dealer_id', $request->dealer_id);
        //     }

        if ($request->dealer_id) {
            $query->where('dealer_id', $request->dealer_id);
        }

        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->order_status !== null) {
            $query->where('order_status', $request->order_status);
        }

        if ($request->order_date_from && $request->order_date_to) {
            $query->whereBetween('order_date', [$request->order_date_from, $request->order_date_to]);
        } else {
            if ($request->order_date_from || $request->order_date_to) {
                return returnData(404, [], 'Please select both From Date and To Date');
            }
        }

        $order = $query->orderBy('id', 'desc')->get();

        if ($order->isEmpty()) {
            return returnData(404, [], 'No order found for your search!');
        }

        return returnData(2000, $order, '');
    }

    public function multiple(Request $request)
    {
        if (!can('order_information.destroy')) {
            return $this->notPermitted();
        }

        try {
            $selectedKeys = $request->input('selectedKeys', []);
            $ids = is_array($selectedKeys) ? $selectedKeys : [];

            if (empty($ids)) {
                return returnData(4000, null, 'No IDs provided');
            }
            $deleted = [];
            $errors = [];

            foreach ($ids as $id) {
                $data = Order::where('id', $id)->first();

                if (!$data) {
                    $errors[] = "ID {$id} not found";
                    continue;
                }

                $data->delete();
                $deleted[] = $id;
            }

            return returnData(2000, null, count($deleted) . " Item Deleted and " . json_encode($errors) . " Item Not Deleted");
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }

    }
}

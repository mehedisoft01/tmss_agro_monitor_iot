<?php

namespace App\Http\Controllers\Backend\Sales;

use App\Helpers\Helper;
use App\Models\Accounting\PaymentCollection;
use App\Models\Inventory\ProductStockMovement;
use App\Models\Inventory\Stock;
use App\Models\Inventory\Warehouse;
use App\Models\Sales\Order;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Sales\Invoice;
use App\Models\Sales\InvoiceItem;
use App\Models\Sales\OrderItem;
use Illuminate\Support\Facades\DB;

class OrderPendingApproveController extends Controller
{
    use Helper;

    public function __construct()
    {
        $this->model = new Order();
    }

    public function index()
    {
        try {
            $keyword = request()->input('keyword');
            $dealerId = request()->input('dealer_id');
            $customerId = request()->input('customer_id');
            $perPage = request()->input('per_page');
            $user = auth()->user();
            $userId = auth()->id();
            $isSalesman = $this->isSalesManger();

            $data = $this->model
                ->with(['approve:id,name', 'customer','dealer'])
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where('order_no', 'like', "%$keyword%");
                })
                ->when($dealerId, function ($query) use ($dealerId) {
                    $query->where('dealer_id', 'like', "%$dealerId%");
                })
                ->when($customerId, function ($query) use ($customerId) {
                    $query->where('customer_id', 'like', "%$customerId%");
                })
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
                    $query->where('created_by', $user->id);
                })
                ->where('order_status', 0)
//                ->where('payment_status', 0)

                ->orderBy('id', 'DESC')
                ->paginate($perPage);

            return returnData(2000, $data);
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }



    public function store(Request $request)
    {

        DB::beginTransaction();
        try {
            $input = $request->all();
            $order = $this->model->find($input['id']);
            $authId = auth()->user()->id;

            if (!$order) {
                return returnData(4040, null, 'Order not found.');
            }

            $order->update([
                'order_status' => $input['order_status'],
                'order_approved_by'  => $authId,
            ]);
            $order->refresh();

            $order = Order::where('id',$input['id'])->first();

            if($order->dealer_id){
                $orderItem = OrderItem::where('dealer_order_id',$order->id)->get();
            }else{
                $orderItem = OrderItem::where('customer_order_id',$order->id)->get();
            }

            if ($request->has('deliveryRows')) {
                foreach ($request->deliveryRows as $row) {
                    foreach ($row['products'] as $p) {
                        $orderItem
                            ->where('product_id', $p['product_id'])
                            ->where('serial_group_id', $p['serial_group_id'])
                            ->each(function ($item) use ($row, $p) {
                                $item->warehouse_id = $row['warehouse_id'];
                                $item->quantity     = $p['qty'];
                                $item->save();
                            });
                    }
                }
            }

            $groupedItems = $orderItem->groupBy('warehouse_id');

            if($order->customer_id){
                $type = 2;
            }else{
                $type = 1;
            }

            $totalOrderAmount = $orderItem->sum('total_price');
            $discount = $order->discount ?? 0;

            if ($totalOrderAmount == 0) {
                $totalOrderAmount = 1;
            }

            foreach ($groupedItems as $warehouseId => $items) {

                $warehouseTotal = $items->sum('total_price');
                $warehouseDiscount = $totalOrderAmount > 0 ? ($warehouseTotal / $totalOrderAmount) * $discount : 0;
                $netAmount = $warehouseTotal - $warehouseDiscount;

                $warehouse = Warehouse::find($warehouseId);
                $divisionId = $warehouse->division_id ?? null;
                $districtId = $warehouse->district_id ?? null;
                $user = User::find($order->created_by);
                $salesmanId = $user->salesman_id ?? null;
                $invoice = Invoice::create([
                    'user_id'       => $authId,
                    'invoice_no'    => 'INV-' . str_pad($order->id, 6, '0', STR_PAD_LEFT) . '-' . $warehouseId,
                    'order_no'      => $order->order_no,
                    'order_id'      => $order->id,
                    'salesman_id'      => $salesmanId,
                    'warehouse_id'  => $warehouseId,
                    'division_id'   => $divisionId,
                    'district_id'   => $districtId,
                    'dealer_id'     => $order->dealer_id,
                    'customer_id'   => $order->customer_id,
                    'invoice_date'  => $order->order_date,
                    'total_qty'     => $items->sum('quantity'),
                    'total_amount'  => $warehouseTotal,
                    'discount'      => $warehouseDiscount,
                    'net_amount'    => $netAmount,
                    'created_by'    => $order->created_by,
                    'invoice_status'=> 0,
                    'payment_confirmed_by' => $order->payment_confirmed_by,
                    'order_approved_by' => $order->order_approved_by,
                ]);

                PaymentCollection::create([
                    'invoice_id'   => $invoice->id,
                    'dealer_id'    => $order->dealer_id,
                    'customer_id'  => $order->customer_id,
                    'user_id'      => $authId,
                    'receipt_no'   => generateUniqueCode('PY', 'receipt_no', 'payment_collections'),
                    'date'         => $invoice->invoice_date,
                    'amount'       => $invoice->net_amount,
                    'payment_mode' => 1,
                    'remarks'      => 'Auto payment on order confirm',
                ]);


                foreach ($items as $item) {
                    InvoiceItem::create([
                        'invoice_id'   => $invoice->id,
                        'product_id'   => $item->product_id,
                        'serial_group_id'   => $item->serial_group_id,
                        'product_code' => $item->product_code,
                        'warehouse_id' => $warehouseId,
                        'quantity'     => $item->quantity,
                        'unit_price'   => $item->unit_price,
                        'total_price'  => $item->total_price,
                        'remarks'      => $item->remarks,
                    ]);
                };

                dealerLeagerBalance([
                    'order_id'       => $order->id,
                    'invoice_id'       => $invoice->id,
                    'customer_id'      => $order->customer_id,
                    'dealer_id'        => $order->dealer_id,
                    'transaction_type' => 1,
                    'type'              => $type,
                    'date'             => $order->order_date,
                    'debit'            => $invoice->net_amount ?? 0,
                    'credit'           => 0,
                    'remarks'          =>  'purchase',
                ]);
                dealerLeagerBalance([
                    'order_id'       => $order->id,
                    'invoice_id'       => $invoice->id,
                    'customer_id'      => $order->customer_id,
                    'dealer_id'        => $order->dealer_id,
                    'transaction_type' => 2,
                    'type'              => $type,
                    'date'             => $order->order_date,
                    'debit'            => 0,
                    'credit'           => $invoice->net_amount ?? 0,
                    'remarks'          =>  'Payment',
                ]);
            }



            foreach ($orderItem as $item) {
                $stock = Stock::where('product_id', $item->product_id)
                    ->where('warehouse_id', $item->warehouse_id)
                    ->lockForUpdate()
                    ->first();

//                $productName = $item->product_name ?? 'This product';
//
//                if (!$stock) {
//                    throw new \Exception("{$productName} stock not found in selected warehouse.");
//                }
//
//                if ($stock->current_stock < $item->quantity) {
//                    throw new \Exception(
//                        "{$productName} stock is insufficient. Available: {$stock->current_stock}, Required: {$item->quantity}"
//                    );
//                }


//                $stock->decrement('current_stock', $item->quantity);


                ProductStockMovement::create([
                    'user_id'      => auth()->id(),
                    'product_id'   => $item->product_id,
                    'warehouse_id' => $item->warehouse_id,
                    'type'         => 2,
                    'ref_id' => $invoice->id,
                    'quantity'     => $item->quantity,
                    'direction'    => 'out',
                    'note'         => 'Stock deducted on order confirm',
                ]);
            }



            $creatorId = $order->created_by;

            $creator = User::where('id', $creatorId)
                ->where('status', 1)
                ->first();

            if ($creator) {
                $this->addNotification(
                    auth()->id(),
                    $creator->id,
                    'Order Ready for Delivery',
                    "Order ({$order->order_no}) is now ready for delivery.",
                    '/orders_details/' . $order->id,
                    2,
                    $order->id
                );

                info("Delivery notification sent to user ID: {$creator->id} for order ID: {$order->id}");
            } else {
                info("Creator not found or inactive for delivery notification, order ID: {$order->id}");
            }

            DB::commit();

            return returnData(2000, $order, 'Order confirmed successfully...!!');
        } catch (\Exception $exception) {
            DB::rollBack();
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }



    public function approveIndex()
    {
        try {
            $keyword = request()->input('keyword');
            $data = $this->model->with('approve:id,name')
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where('name', 'Like', "%$keyword%");
                })
                ->where('order_status', 1)
                ->paginate(input('perPage'));

            return returnData(2000, $data);
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }



    public function updatePaymentStatus(Request $request)
    {
        try {
            $input = $request->all();
            $orderId = $input['id'] ?? null;
            $paymentStatus = $input['payment_status'] ?? null;

            if (!$orderId || is_null($paymentStatus)) {
                return returnData(4000, null, 'Order ID and Payment Status are required.');
            }

            $order = $this->model->find($orderId);

            if (!$order) {
                return returnData(4040, null, 'Order not found.');
            }

            $order->update([
                'payment_status' => $paymentStatus,
                'payment_confirmed_by' => auth()->id(),
            ]);

            $roles = Setting::whereIn('key', ['division_manager_role', 'batter_go_admin_role'])
                ->pluck('value', 'key');

            $divisionManagers = User::where('division_id', $order->division_id)
                ->where('role_id', $roles['division_manager_role'])
                ->where('status', 1)
                ->get();

            $batterGoAdmins = User::where('role_id', $roles['batter_go_admin_role'])
                ->where('status', 1)
                ->get();

            $usersToNotify = $divisionManagers->merge($batterGoAdmins);

            if ($usersToNotify->isEmpty()) {
                return returnData(4040, null, 'No active manager/admin found to notify.');
            }

            foreach ($usersToNotify as $user) {
                $this->addNotification(
                    auth()->id(),
                    $user->id,
                    'Order Payment Verified',
                    "Order ({$order->order_no}) payment has been verified. You can now approve it.",
                    '/orders_details/' . $order->id,
                    2,
                    $order->id
                );
            }

            return returnData(2000, $order, '');
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Something went wrong while updating payment status.');
        }
    }
}

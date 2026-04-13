<?php

namespace App\Http\Controllers\Frontend;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\ProductManagement\Product;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DealerOrderController extends Controller
{
    use Helper;

    public function __construct()
    {
        $this->model = new Order();
    }

    public function index()
    {
        try {
            $dealer_id = Auth::guard('dealers')->id();

            $address = Address::select(
                'id',
                'dealer_id',
                'p_division_id',
                'p_district_id',
                'p_upazila_id',
                'p_area'
            )
                ->with(['division','district','upazila'])
                ->where('dealer_id', $dealer_id)
                ->first();

            return returnData(2000, $address);

        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }

    public function orderHistory(){
        try {
            $dealer_id = Auth::guard('dealers')->id();
            $orders = Order::where('dealer_id', $dealer_id)
            ->with('dealerItems.product')->paginate(10);

            return returnData(2000, $orders);

        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }

    public function store(Request $request){
//        ddA($request);
     try {
          $dealer_id = Auth::guard('dealers')->id();

          $validate = $this->model->validate();
            if ($validate->fails()) {
                return returnData(2000, $validate->errors());
            }
          
            $order = new Order();
            $order->order_no = 'ORD-' . date('YmdHis');
            $order->dealer_id = $dealer_id;
            $order->division_id = $request->division_id;
            $order->district_id = $request->district_id;
            $order->upazila_id = $request->upazila_id;
            $order->area = $request->area;

            Address::createIfNew(
            $dealer_id,
            null,
            $request->division_id,
            $request->district_id,
            $request->upazila_id,
            $request->area,
            );

           $order->order_date = now();
           $order->delivery_date = Carbon::parse($order->order_date)->addDays(3);
           $order->order_status = is_numeric($request->order_status) ? (int) $request->order_status : 0;
           $order->total_qty = $request->total_qty;
           $order->total_amount = $request->total_amount;
           $order->net_amount = $request->net_amount;
           $order->discount = null;
           $order->remarks = null;
           if ($request->has('attachment') && is_array($request->attachment)) {
                $order->attachment = json_encode($request->attachment);
            } else {
                $order->attachment = json_encode([]);
            }
            $order->created_by = $dealer_id;
           $order->save();

           foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                OrderItem::create([
                    'dealer_order_id' =>$order->id,
                    'product_id' => $item['product_id'],
                    'warehouse_id' => $product->warehouse_id ?? null,
                    'product_code' => $product->product_code ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                    'remarks' => $item['remarks'] ?? null,
                    'status' => 1,
                ]);
            }

         return returnData(2000, null, 'Successfully order');

        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }
}

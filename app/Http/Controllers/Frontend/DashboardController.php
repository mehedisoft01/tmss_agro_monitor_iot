<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Sales\Order;
use function Illuminate\Foundation\Testing\Concerns\json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(){
       try{
           $dealer_id = Auth::guard('dealers')->id();

           $data['totals_order'] = Order::where('status', 1)
               ->where('dealer_id', $dealer_id)
               ->count();

           $data['pending_order'] = Order::where('order_status', 0)
               ->where('dealer_id', $dealer_id)
               ->count();

           $data['delivery_order'] = Order::where('order_status', 4)
               ->where('dealer_id', $dealer_id)
               ->count();

           $data['cancelled_order'] = Order::where('order_status', 5)
               ->where('dealer_id', $dealer_id)
               ->count();

       return returnData(2000, $data);

       }catch (\Exception $exception) {
           return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
       }
    }
}

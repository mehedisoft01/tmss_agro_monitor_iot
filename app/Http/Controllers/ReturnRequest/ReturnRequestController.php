<?php

namespace App\Http\Controllers\ReturnRequest;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\ReturnRequest\ReturnRequest;
use App\Models\ReturnRequest\ReturnRequestItems;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReturnRequestController extends Controller
{
    use Helper;
    public function __construct()
    {
//        if (!can(request()->route()->action['as'])) {
//            return returnData(5001, null, 'You are not authorized to access this page');
//        }
        $this->model = new ReturnRequest();
    }

    public function returnRequest(){
        try {
            $dealer_id = Auth::guard('dealers')->id();
            $returnDays = Setting::where('key', 'return_refund')
                    ->where('is_visible', 1)
                    ->value('value') ?? 0;

            $orders = Invoice::where('dealer_id', $dealer_id)
                ->where('status', 1)
                ->with('items.product')
                ->get()
                ->map(function ($invoice) use ($returnDays) {

                    $invoiceDate = Carbon::parse($invoice->invoice_date);
                    $lastReturnDate = $invoiceDate->copy()->addDays((int)$returnDays);

                    $invoice->return_last_date = $lastReturnDate->toDateString();
                    $invoice->return_expired = now()->gt($lastReturnDate);

                    return $invoice;
                });

            return returnData(2000, $orders);

        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }


    public function storeReturnRequest(Request $request){
        try {
//            ddA($request);
            DB::beginTransaction();

            $auth = Auth('dealers')->user();
            $input = $request->all();

            $input['dealer_id'] = $auth->id;
            $input['created_by'] = $auth->id;
            $input['return_date'] = Carbon::today()->toDateString();

            $validate = $this->model->validate($input);
            if ($validate->fails()) {
                return returnData(3000, $validate->errors());
            }

            $this->model->fill($input);
            $this->model->save();

            foreach ($input['items'] as $item) {
                ReturnRequestItems::create([
                    'return_request_id' => $this->model->id,
                    'product_id'        => $item['product_id'],
                    'quantity'          => $item['return_qty'],
                    'serial_no'         => implode(',', $item['serial_no']),
                    'unit_price'        => $item['unit_price'],
                    'total_price'       => $item['total'],
                    'condition'         => $item['condition'] ?? null,
                ]);
            }


            DB::commit();

            return returnData(2000, null, 'Return request submitted successfully');

        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }

    public function returnHistory()
    {
        try {
            $dealer_id = Auth::guard('dealers')->id();
            $orders = ReturnRequest::where('dealer_id', $dealer_id)
                ->with('items', 'invoice')
                ->orderBy('return_date', 'desc')
                ->paginate(input('perPage'));
            return returnData(2000, $orders);

        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }

    public function returnRequestVerify()
    {
        try {
            $orders = ReturnRequest::with('items', 'invoice', 'dealer')
                ->orderBy('return_date', 'desc')
                ->paginate(input('perPage'));
            return returnData(2000, $orders);

        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }

}

<?php

namespace App\Http\Controllers\Backend\Sales;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Sales\Invoice;

class InvoicePendingController extends Controller
{
    use Helper;

    public function __construct()
    {
        //        if (!can(request()->route()->action['as'])) {
        //            return returnData(5001, null, 'You are not authorized to access this page');
        //        }
        $this->model = new Invoice();
    }

    public function index()
    {
        if (!can('invoice_information.index')) {
            return $this->notPermitted();
        }

        try {
            $keyword = request()->input('keyword');
            $dealerId = request()->input('dealer_id');
            $customerId = request()->input('customer_id');
            $warehouseId = request()->input('warehouse_id');
            $perPage = request()->input('per_page');
            $user = auth()->user();
            $isSalesman = $this->isSalesManger();


            $data = $this->model
                ->with(['order', 'items','customer','dealer','warehouse','createdByUser'])
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('invoice_no', 'like', "%{$keyword}%")
                            ->orWhere('order_no', 'like', "%{$keyword}%");
                    });
                })
                ->when($dealerId, function ($query) use ($dealerId) {
                    $query->where('dealer_id', 'like', "%$dealerId%");
                })
                ->when($customerId, function ($query) use ($customerId) {
                    $query->where('customer_id', 'like', "%$customerId%");
                })
                ->when($warehouseId, function ($query) use ($warehouseId) {
                    $query->where('warehouse_id', 'like', "%$warehouseId%");
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
                ->whereIn('invoice_status', [0, 2])

                ->orderBy('id', 'DESC')
                ->paginate($perPage);

            return returnData(2000, $data);
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }


    public function destroy($id)
    {
//        if (!can('order_information.destroy')) {
//            return $this->notPermitted();
//        }

        try {
            $data = $this->model->where('id', $id)->first();
            if (!$data) {
                return returnData(5000, null, 'Data Not found');
            }

            $data->delete();

            return returnData(2000, $data, 'Pending Invoice Successfully Deleted');
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }
}

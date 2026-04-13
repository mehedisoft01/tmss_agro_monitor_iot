<?php

namespace App\Http\Controllers\Backend\Inventory;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Inventory\ProductStockMovement;
use Illuminate\Http\Request;

class ProductStockMovementController extends Controller
{
    use Helper;
    public function __construct()
    {
//        if (!can(request()->route()->action['as'])) {
//            return returnData(5001, null, 'You are not authorized to access this page');
//        }
        $this->model = new ProductStockMovement();
    }

    public function index()
    {
        try {
            $keyword = request()->input('keyword');
            $productId = request()->input('product_id');
            $warehouseId = request()->input('warehouse_id');
            $type = request()->input('type');
            $auth = auth()->user();

            $data = $this->model
                ->with(['product', 'warehouse'])
//                ->when($auth->dealer_id != 0, function ($query) use ($auth) {
//                    $query->where('dealer_id', $auth->dealer_id);
//                }, function ($query) {
//                    $query->whereNull('dealer_id');
//                })
                ->when($keyword, function ($query) use ($keyword) {
                    $query->whereHas('product', function ($q) use ($keyword) {
                        $q->where('product_name', 'LIKE', "%{$keyword}%");
                    });
                })
                ->when($productId, function ($query) use ($productId) {
                    $query->where('product_id', 'like', "%$productId%");
                })
                ->when($warehouseId, function ($query) use ($warehouseId) {
                    $query->where('warehouse_id', 'like', "%$warehouseId%");
                })
                ->when($type, function ($query) use ($type) {
                    $query->where('type', 'like', "%$type%");
                })
                ->orderBy('id', 'DESC')
                ->paginate(input('perPage'));

            return returnData(2000, $data);
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show( $id)
    {
        //
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }
}

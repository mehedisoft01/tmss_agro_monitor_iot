<?php

namespace App\Http\Controllers\Backend\Inventory;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Inventory\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    use Helper;
    public function __construct()
    {
//        if (!can(request()->route()->action['as'])) {
//            return returnData(5001, null, 'You are not authorized to access this page');
//        }
        $this->model = new Stock();
    }


    public function index()
    {
        try {

            $keyword            = request()->input('keyword');
            $parentId           = request()->input('parent_id');
            $subCategoryId      = request()->input('sub_category_id');
            $subSubCategoryId   = request()->input('sub_sub_category_id');
            $productId          = request()->input('product_id');
            $warehouseId        = request()->input('warehouse_id');
            $perPage            = request()->input('per_page');

            $data = $this->model
                ->with([
                    'warehouse',
                    'product',
                    'user',
                    'product.parentCategory',
                    'product.subCategory',
                    'product.subSubCategory'
                ])
                ->select('stocks.*')

                // Serial Count
                ->selectSub(function ($q) {
                    $q->from('product_serials')
                        ->join('product_serial_groups', 'product_serial_groups.id', '=', 'product_serials.serial_group_id')
                        ->whereColumn('product_serials.product_id', 'stocks.product_id')
                        ->whereColumn('product_serial_groups.warehouse_id', 'stocks.warehouse_id')
                        ->where('product_serials.status', 0)
                        ->selectRaw('COUNT(DISTINCT product_serials.serial)');
                }, 'serial_count')

                // Serial List
                ->selectSub(function ($q) {
                    $q->from('product_serials')
                        ->join('product_serial_groups', 'product_serial_groups.id', '=', 'product_serials.serial_group_id')
                        ->whereColumn('product_serials.product_id', 'stocks.product_id')
                        ->whereColumn('product_serial_groups.warehouse_id', 'stocks.warehouse_id')
                        ->where('product_serials.status', 0)
                        ->selectRaw('GROUP_CONCAT(DISTINCT product_serials.serial)');
                }, 'serial_list')

                ->when($keyword, function ($query) use ($keyword) {
                    $query->whereHas('user', function ($q) use ($keyword) {
                        $q->where('name', 'LIKE', "%{$keyword}%");
                    });
                })

                ->when($parentId, function ($query) use ($parentId) {
                    $query->whereHas('product', function ($q) use ($parentId) {
                        $q->where('parent_id', $parentId);
                    });
                })

                ->when($subCategoryId, function ($query) use ($subCategoryId) {
                    $query->whereHas('product', function ($q) use ($subCategoryId) {
                        $q->where('sub_category_id', $subCategoryId);
                    });
                })

                ->when($subSubCategoryId, function ($query) use ($subSubCategoryId) {
                    $query->whereHas('product', function ($q) use ($subSubCategoryId) {
                        $q->where('sub_sub_category_id', $subSubCategoryId);
                    });
                })

                ->when($productId, function ($query) use ($productId) {
                    $query->where('product_id', $productId);
                })

                ->when($warehouseId, function ($query) use ($warehouseId) {
                    $query->where('warehouse_id', $warehouseId);
                })

                ->orderBy('stocks.id', 'DESC')
                ->paginate($perPage);

            return returnData(2000, $data);

        } catch (\Exception $exception) {

            return returnData(
                5000,
                $exception->getMessage(),
                'Whoops, Something Went Wrong..!!'
            );
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

    public function edit( $id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy( $id)
    {
        //
    }


    public function lowStockList()
    {
        try {
            $keyword = request()->input('keyword');
            $data = $this->model->with([
                'product',
                'warehouse',
                'parentCategory',
                'subCategory',
                'subSubCategory'
            ])
                ->when($keyword, function ($query) use ($keyword) {
                    $query->whereHas('product', function($productQuery) use ($keyword) {
                        $productQuery->where('product_name', 'LIKE', "%{$keyword}%")
                            ->orWhere('product_code', 'LIKE', "%{$keyword}%");
                    });
                })
                ->whereColumn('current_stock', '<=', 'minimum_stock')
                ->orderBy('current_stock', 'asc')
                ->paginate(request('perPage'))
                ->through(function($stock) {
                    return [
                        'id' => $stock->id,
                        'product_name' => $stock->product->product_name ?? 'N/A',
                        'product_code' => $stock->product_code ?? ($stock->product->product_code ?? '-'),
                        'warehouse_name' => $stock->warehouse->name ?? $stock->warehouse->warehouse_code ?? '-',
                        'current_stock' => $stock->current_stock,
                        'minimum_stock' => $stock->minimum_stock,
                        'stock_status' => $stock->current_stock == 0 ? 'Out of Stock' : 'Low Stock',
                    ];
                });

            return returnData(2000, $data);
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }
}

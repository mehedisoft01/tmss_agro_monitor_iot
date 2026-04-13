<?php

namespace App\Http\Controllers\Backend\Inventory;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Inventory\ProductStockMovement;
use App\Models\Inventory\StockAdjustment;
use App\Models\ProductManagement\Product;
use App\Models\ProductSerial;
use App\ProductStock\StockAdjustmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockAdjustmentController extends Controller
{
    use Helper;
    public function __construct()
    {
//        if (!can(request()->route()->action['as'])) {
//            return returnData(5001, null, 'You are not authorized to access this page');
//        }
        $this->model = new StockAdjustment();
    }

    public function index()
    {
        if (!can('stock_adjustment.index')) {
            return $this->notPermitted();
        }

        try {
            $keyword = request()->input('keyword');
            $productId = request()->input('product_id');
            $warehouseId = request()->input('warehouse_id');
            $perPage = request()->input('per_page');

            $data = $this->model
                ->with('product', 'stock', 'warehouse')
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where('name', 'LIKE', "%{$keyword}%");
                })
                ->when($productId, function ($query) use ($productId) {
                    $query->where('product_id', 'like', "%$productId%");
                })
                ->when($warehouseId, function ($query) use ($warehouseId) {
                    $query->where('warehouse_id', 'like', "%$warehouseId%");
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
        if (!can('stock_adjustment.store')) {
            return $this->notPermitted();
        }

        DB::beginTransaction();

        try {

            $auth = auth()->user();
            if (!$request->has('items') || !is_array($request->items) || count($request->items) == 0) {
                return returnData(3000, [
                    'items' => ['Items array is required']
                ]);
            }

            foreach ($request->items as $index => $item) {

                $input = [
                    'date'          => $request->date,
                    'warehouse_id'  => $request->warehouse_id,
                    'reason'        => $request->reason,
                    'product_id'    => $item['product_id'] ?? null,
                    'quantity'      => $item['quantity'] ?? 0,
                    'serial_id'    => !empty($item['serials'])
                        ? json_encode($item['serials'])
                        : null,
                    'adjust_status' => $item['adjust_status'] ?? null,
                    'user_id'       => $auth->id,
                ];

//                ddA($input);

                $validate = $this->model->validate($input);

                if ($validate->fails()) {
                    return returnData(3000, [
                        "items.$index" => $validate->errors()
                    ]);
                }

                $adjustment = $this->model->newInstance();
                $adjustment->fill($input);
                $adjustment->save();

                if (!empty($item['serials']) && is_array($item['serials'])) {

                        ProductSerial::whereIn('id', $item['serials'])
                        ->where('product_id', $item['product_id'])
                        ->update([
                            'status'     => $item['adjust_status'],
                            'invoice_id' => 0,
                            'updated_at' => now()
                        ]);
                }

                ProductStockMovement::create([
                    'user_id'         => $auth->id,
                    'product_id'      => $item['product_id'] ?? null,
                    'warehouse_id'    => $request->warehouse_id,
                    'shipping_cost'   => null,
                    'type'            => 4,
                    'quantity'        => $item['quantity'] ?? 0,
                    'ref_id'          => $adjustment->id,
                    'note'            => $request->reason,
                ]);
            }

            DB::commit();

            return returnData(2000, null, 'Stock adjusted successfully.');

        } catch (\Exception $exception) {
            DB::rollBack();
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }

    public function destroy($id)
    {
        if (!can('stock_adjustment.destroy')) {
            return $this->notPermitted();
        }

        DB::beginTransaction();

        try {

            $data = $this->model->where('id', $id)->first();

            if (!$data) {
                return returnData(5000, null, 'Data Not found');
            }

            if (!empty($data->serial_id)) {

                $serialIds = is_array($data->serial_id)
                    ? $data->serial_id
                    : json_decode($data->serial_id, true);

                if (!empty($serialIds)) {
                    ProductSerial::whereIn('id', $serialIds)
                        ->update([
                            'status' => 0
                        ]);
                }
            }

            $data->delete();

            DB::commit();

            return returnData(2000, null, 'Successfully Deleted & Serial Restored');

        } catch (\Exception $exception) {

            DB::rollBack();

            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }
}

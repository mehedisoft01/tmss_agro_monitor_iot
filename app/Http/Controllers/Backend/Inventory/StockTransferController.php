<?php

namespace App\Http\Controllers\Backend\Inventory;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Inventory\ProductStockMovement;
use App\Models\Inventory\Stock;
use App\Models\Inventory\StockTransfer;
use App\Models\ProductSerial;
use App\Models\ProductSerialGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    use Helper;

    public function __construct()
    {
//        if (!can(request()->route()->action['as'])) {
//            return returnData(5001, null, 'You are not authorized to access this page');
//        }
        $this->model = new StockTransfer();
    }

    public function index()
    {
        if (!can('stock_transfer.index')) {
            return $this->notPermitted();
        }

        try {
            $keyword = request()->input('keyword');
            $productId = request()->input('product_id');
            $fromWarehouseId = request()->input('from_warehouse_id');
            $toWarehouseId = request()->input('to_warehouse_id');
            $perPage = request()->input('per_page');
            $auth = auth()->user();

            $data = $this->model
                ->leftJoin('products as p', 'stock_transfers.product_id', '=', 'p.id')
                ->leftJoin('warehouses as w1', 'stock_transfers.from_warehouse_id', '=', 'w1.id')
                ->leftJoin('warehouses as w2', 'stock_transfers.to_warehouse_id', '=', 'w2.id')
                ->leftJoin('stocks as s', function ($join) {
                    $join->on('s.product_id', '=', 'stock_transfers.product_id')
                        ->on('s.warehouse_id', '=', 'stock_transfers.from_warehouse_id');
                })
                ->select(
                    'stock_transfers.*',
                    'p.product_name',
                    'p.product_code',
                    'w1.warehouse_name as from_warehouse',
                    'w2.warehouse_name as to_warehouse',
                    's.current_stock as available_stock'
                )
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where('p.product_name', 'LIKE', "%{$keyword}%");
                })
                ->when($productId, function ($query) use ($productId) {
                    $query->where('stock_transfers.product_id', 'like', "%$productId%");
                })
                ->when($fromWarehouseId, function ($query) use ($fromWarehouseId) {
                    $query->where('from_warehouse_id', 'like', "%$fromWarehouseId%");
                })
                ->when($toWarehouseId, function ($query) use ($toWarehouseId) {
                    $query->where('to_warehouse_id', 'like', "%$toWarehouseId%");
                })
                ->orderByDesc('stock_transfers.id')
                ->paginate($perPage);

            return returnData(2000, $data);

        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage());
        }
    }


    public function store(Request $request)
    {
        if (!can('stock_transfer.store')) {
            return $this->notPermitted();
        }

        try {

            $auth = auth()->user();
            DB::beginTransaction();

            $transferDate   = $request->transfer_date;
            $fromWarehouse  = $request->from_warehouse_id;
            $toWarehouse    = $request->to_warehouse_id;
            $shippingCost   = $request->shipping_cost ?? 0;
            $note           = $request->note;
            $total           = $request->total_amount;
            $attachment     = json_encode($request->attachment);

            $items = $request->input('transferItems', []);

            foreach ($items as $item) {

                if (!isset($item['serials'])) {
                    continue;
                }

                $selectedSerials = collect($item['serials'])
                    ->where('selected', 1)
                    ->pluck('serial')
                    ->toArray();

                if (count($selectedSerials) == 0) {
                    continue;
                }

                $transferQty = count($selectedSerials);
                $productId   = $item['product_id'];

                $serGroup = ProductSerialGroup::find($item['serial_group_id']);

                if (!$serGroup) {
                    continue;
                }

                $costPrice    = $serGroup->cost_price;
                $sellingPrice = $serGroup->selling_price;
                $dealerPrice  = $serGroup->dealer_price;

                $transfer = StockTransfer::create([
                    'user_id'            => $auth->id,
                    'product_id'         => $productId,
                    'transfer_qty'       => $transferQty,
                    'cost'               => $costPrice,
                    'total'              => $total,
                    'selling_price'      => $sellingPrice ?? 0,
                    'dealer_price'       => $dealerPrice ?? 0,
                    'subtotal'           => $item['subtotal'] ?? 0,
                    'serials_item'       => implode(',', $selectedSerials),
                    'from_warehouse_id'  => $fromWarehouse,
                    'to_warehouse_id'    => $toWarehouse,
                    'shipping_cost'      => $shippingCost,
                    'transfer_date'      => $transferDate,
                    'note'               => $note,
                    'attachment'         => $attachment,
                ]);


                $fromStock = Stock::where('product_id', $productId)
                    ->where('warehouse_id', $fromWarehouse)
                    ->first();

                if ($fromStock) {

                    if ($fromStock->current_stock < $transferQty) {
                        throw new \Exception("Not enough stock available in source warehouse.");
                    }

                    $fromStock->decrement('current_stock', $transferQty);
                }


                $toStock = Stock::where('product_id', $productId)
                    ->where('warehouse_id', $toWarehouse)
                    ->first();

                if ($toStock) {

                    $toStock->increment('current_stock', $transferQty);

                    $toStock->update([
                        'unit_cost'     => $costPrice,
                        'selling_price' => $sellingPrice ?? $toStock->selling_price,
                        'dealer_price'  => $dealerPrice ?? $toStock->dealer_price,
                        'shipping_cost' => $shippingCost,
                        'user_id'       => $auth->id,
                    ]);

                } else {

                    $toStock = Stock::create([
                        'user_id'       => $auth->id,
                        'product_id'    => $productId,
                        'warehouse_id'  => $toWarehouse,
                        'current_stock' => $transferQty,
                        'unit_cost'     => $costPrice,
                        'minimum_stock' => 5,
                        'serial_no'     => implode(',', $selectedSerials),
                        'selling_price' => $sellingPrice ?? 0,
                        'dealer_price'  => $dealerPrice ?? 0,
                        'shipping_cost' => $shippingCost,
                    ]);
                }


                ProductSerial::where('product_id', $productId)
                    ->whereIn('serial', $selectedSerials)
                    ->update([
                        'status'     => 1,
                        'invoice_id' => $transfer->id
                    ]);

                $serialGroup = ProductSerialGroup::where('product_id', $productId)
                    ->where('warehouse_id', $toWarehouse)
                    ->where('dealer_price', $dealerPrice)
                    ->where('selling_price', $sellingPrice)
                    ->first();

                if (!$serialGroup) {

                    $serialGroup = ProductSerialGroup::create([
                        'user_id'       => $auth->id,
                        'product_id'    => $productId,
                        'warehouse_id'  => $toWarehouse,
                        'date'          => $transferDate,
                        'attachment'    => $attachment,
                        'cost_price'    => $costPrice,
                        'dealer_price'  => $dealerPrice ?? 0,
                        'selling_price' => $sellingPrice ?? 0,
                        'stock_id'      => $transfer->id,
                    ]);
                }


                $newSerials = [];

                foreach ($selectedSerials as $serial) {

                    $newSerials[] = [
                        'product_id'      => $productId,
                        'user_id'         => $auth->id,
                        'serial_group_id' => $serialGroup->id,
                        'stock_id'        => $transfer->id,
                        'serial'          => $serial,
                        'status'          => 0,
                    ];
                }

                ProductSerial::insert($newSerials);

                ProductStockMovement::create([
                    'user_id'         => $auth->id,
                    'product_id'      => $productId,
                    'warehouse_id'    => $toWarehouse,
                    'shipping_cost'   => $shippingCost,
                    'type'            => 5,
                    'quantity'        => $transferQty,
                    'ref_id'          => $transfer->id,
                    'note'            => $note,
                ]);
            }

            DB::commit();

            return returnData(2000, null, 'Stock transferred successfully!');

        } catch (\Exception $exception) {

            DB::rollBack();

            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }


    public function edit($id)
    {
        try {
            $transfer = StockTransfer::findOrFail($id);

            $serials = DB::table('product_serials')
                ->where('product_id', $transfer->product_id)
                ->limit($transfer->transfer_qty)
                ->pluck('serial');

            $transfer->serials = $serials;

            return returnData(2000, $transfer);

        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }

    public function update(Request $request, $id)
    {
        if (!can('stock_transfer.update')) {
            return $this->notPermitted();
        }

        try {

            $auth = auth()->user();
            DB::beginTransaction();

            $oldTransfers = StockTransfer::where('id', $id)->get();

            foreach ($oldTransfers as $old) {

                $productId = $old->product_id;
                $qty       = $old->transfer_qty;

                $fromWarehouse = $old->from_warehouse_id;
                $toWarehouse   = $old->to_warehouse_id;

                $serials = explode(',', $old->serials_item);

                $fromStock = Stock::where('product_id', $productId)
                    ->where('warehouse_id', $fromWarehouse)
                    ->first();

                if ($fromStock) {
                    $fromStock->increment('current_stock', $qty);
                }

                $toStock = Stock::where('product_id', $productId)
                    ->where('warehouse_id', $toWarehouse)
                    ->first();

                if ($toStock) {
                    $toStock->decrement('current_stock', $qty);
                }

                ProductSerial::whereIn('serial', $serials)
                    ->update([
                        'status' => 0,
                        'invoice_id' => null
                    ]);
            }

            StockTransfer::where('id', $id)->delete();

            $transferDate   = $request->transfer_date;
            $fromWarehouse  = $request->from_warehouse_id;
            $toWarehouse    = $request->to_warehouse_id;
            $shippingCost   = $request->shipping_cost ?? 0;
            $note           = $request->note;
            $total          = $request->total_amount;
            $attachment     = json_encode($request->attachment);

            $items = $request->input('transferItems', []);

            foreach ($items as $item) {

                if (!isset($item['serials'])) {
                    continue;
                }

                $selectedSerials = collect($item['serials'])
                    ->where('selected', 1)
                    ->pluck('serial')
                    ->toArray();

                if (count($selectedSerials) == 0) {
                    continue;
                }

                $transferQty = count($selectedSerials);
                $productId   = $item['product_id'];

                $serGroup = ProductSerialGroup::find($item['serial_group_id']);

                if (!$serGroup) {
                    continue;
                }

                $costPrice    = $serGroup->cost_price;
                $sellingPrice = $serGroup->selling_price;
                $dealerPrice  = $serGroup->dealer_price;

                $transfer = StockTransfer::create([
                    'user_id'            => $auth->id,
                    'product_id'         => $productId,
                    'transfer_qty'       => $transferQty,
                    'cost'               => $costPrice,
                    'total'              => $total,
                    'selling_price'      => $sellingPrice ?? 0,
                    'dealer_price'       => $dealerPrice ?? 0,
                    'subtotal'           => $item['subtotal'] ?? 0,
                    'serials_item'       => implode(',', $selectedSerials),
                    'from_warehouse_id'  => $fromWarehouse,
                    'to_warehouse_id'    => $toWarehouse,
                    'shipping_cost'      => $shippingCost,
                    'transfer_date'      => $transferDate,
                    'note'               => $note,
                    'attachment'         => $attachment,
                ]);

                $fromStock = Stock::where('product_id', $productId)
                    ->where('warehouse_id', $fromWarehouse)
                    ->first();

                if ($fromStock) {

//                    if ($fromStock->current_stock < $transferQty) {
//                        throw new \Exception("Not enough stock available in source warehouse.");
//                    }

                    $fromStock->decrement('current_stock', $transferQty);
                }

                $toStock = Stock::where('product_id', $productId)
                    ->where('warehouse_id', $toWarehouse)
                    ->first();

                if ($toStock) {

                    $toStock->increment('current_stock', $transferQty);

                    $toStock->update([
                        'unit_cost'     => $costPrice,
                        'selling_price' => $sellingPrice ?? $toStock->selling_price,
                        'dealer_price'  => $dealerPrice ?? $toStock->dealer_price,
                        'shipping_cost' => $shippingCost,
                        'user_id'       => $auth->id,
                    ]);

                } else {

                    $toStock = Stock::create([
                        'user_id'       => $auth->id,
                        'product_id'    => $productId,
                        'warehouse_id'  => $toWarehouse,
                        'current_stock' => $transferQty,
                        'unit_cost'     => $costPrice,
                        'minimum_stock' => 5,
                        'serial_no'     => implode(',', $selectedSerials),
                        'selling_price' => $sellingPrice ?? 0,
                        'dealer_price'  => $dealerPrice ?? 0,
                        'shipping_cost' => $shippingCost,
                    ]);
                }

               ProductSerial::where('product_id', $productId)
                    ->whereIn('serial', $selectedSerials)
                    ->update([
                        'status'     => 1,
                        'invoice_id' => $transfer->id
                    ]);


                ProductStockMovement::create([
                    'user_id'         => $auth->id,
                    'product_id'      => $productId,
                    'warehouse_id'    => $toWarehouse,
                    'shipping_cost'   => $shippingCost,
                    'type'            => 5,
                    'quantity'        => $transferQty,
                    'ref_id'          => $transfer->id,
                    'note'            => $note,
                ]);

            }

            DB::commit();

            return returnData(2000, null, 'Transfer updated successfully!');

        } catch (\Exception $exception) {

            DB::rollBack();

            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }






    public function destroy($id)
    {
        if (!can('stock_transfer.destroy')) {
            return $this->notPermitted();
        }

        try {
            DB::transaction(function () use ($id) {

                $transfer = StockTransfer::findOrFail($id);

                $productId = $transfer->product_id;
                $fromWarehouse = $transfer->from_warehouse_id;
                $toWarehouse = $transfer->to_warehouse_id;
                $transferQty = $transfer->transfer_qty;

                $serials = explode(',', $transfer->serials_item);

                $fromStock = Stock::where('product_id', $productId)
                    ->where('warehouse_id', $fromWarehouse)
                    ->first();

                if ($fromStock) {
                    $fromStock->current_stock += $transferQty;
                    $fromStock->save();
                }

                $toStock = Stock::where('product_id', $productId)
                    ->where('warehouse_id', $toWarehouse)
                    ->first();

                if ($toStock) {
                    $toStock->current_stock -= $transferQty;
                    if ($toStock->current_stock < 0) {
                        $toStock->current_stock = 0;
                    }
                    $toStock->save();
                }


                $toSerials = ProductSerial::where('product_id', $productId)
                    ->whereIn('serial', $serials)
                    ->where('stock_id', $transfer->id)
                    ->get();

                $groupIds = $toSerials->pluck('serial_group_id')->unique();

                ProductSerial::where('stock_id', $transfer->id)->delete();

                ProductSerial::where('product_id', $productId)
                    ->whereIn('serial', $serials)
                    ->update([
                        'status' => 0,
                        'invoice_id' => null
                    ]);


                ProductSerialGroup::whereIn('id', $groupIds)
                    ->whereDoesntHave('serials')
                    ->delete();

                $transfer->delete();
            });

            return returnData(2000, null, 'Transfer deleted successfully!');
        }catch (\Exception $exception){
            DB::rollBack();

            return returnData(5000, null, 'Not Deleted');
        }
    }

//    public function destroy($id)
//    {
//        try {
//            $data = $this->model->where('id', $id)->first();
//            if (!$data) {
//                return returnData(5000, null, 'Data Not found');
//            }
//
//            $data->delete();
//
//            return returnData(2000, $data, 'Successfully Deleted');
//
//        } catch (\Exception $exception) {
//            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
//        }
//    }

    public function productSerials()
    {
        $product_id = request()->input('product_id');
        $serial_group_id = request()->input('serial_group_id');
        try {
            $data = ProductSerial::selectRaw("*, 0 as selected, 1 as show_serial")->where('product_id', $product_id)
                ->where('serial_group_id', $serial_group_id)
                ->where('status', 0)
                ->get(['serial_group_id', 'status', 'serial']);

            return returnData(2000, $data);
        } catch (\Exception $e) {
            return returnData(5000, $e->getMessage());
        }
    }
}
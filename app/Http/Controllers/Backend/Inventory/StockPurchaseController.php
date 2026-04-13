<?php

namespace App\Http\Controllers\Backend\Inventory;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Inventory\ProductStockMovement;
use App\Models\Inventory\StockPurchase;
use App\Models\Inventory\Stock;
use App\Models\ProductSerial;
use App\Models\ProductSerialGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockPurchaseController extends Controller
{
    use Helper;

    public function __construct()
    {
        $this->model = new StockPurchase();
    }

    public function index()
    {
        if (!can('stock_product.index')) {
            return $this->notPermitted();
        }

        try {
            $keyword = request()->input('keyword');
            $perPage = request()->input('per_page');

            $data = $this->model
                ->with(['product', 'warehouse'])
                ->when($keyword, function ($query) use ($keyword) {
                    $query->whereHas('product', function ($q) use ($keyword) {
                        $q->where('product_name', 'LIKE', "%{$keyword}%");
                    });
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
        if (!can('stock_product.store')) {
            return $this->notPermitted();
        }

        DB::beginTransaction();
        try {
            $auth = auth()->user();
            $data = $request->all();

            foreach ($data['items'] as $item) {
                $stockPurchase = StockPurchase::create([
                    'purchase_date' => $data['purchase_date'],
                    'user_id' => $auth->id,
                    'warehouse_id' => $data['warehouse_id'],
                    'product_id' => $item['product_id'],
                    'serial_no' => implode(',', $item['serials'] ?? []),
                    'stock_status' => $data['stock_status'] ?? null,
                    'note' => $data['note'] ?? null,
                    'purchase_qty' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'tax' => $item['tax'],
                    'shipping_cost' => $item['shipping_cost'],
                    'selling_price' => $item['selling_price'],
                    'dealer_price' => $item['dealer_price'],
                    'sub_total' => $item['subtotal'] ?? 0,
                    'attachment' => $data['attachment'] ? json_encode($data['attachment']) : null,
                ]);

                $group = ProductSerialGroup::where('product_id', $item['product_id'])
                    ->where('warehouse_id', $data['warehouse_id'])
                    ->where('dealer_price', $item['dealer_price'])
                    ->where('selling_price', $item['selling_price'])
                    ->first();

                $reqAttachment = $request->input('attachment') && count($request->input('attachment')) > 0
                    ? $request->input('attachment')
                    : [];
                $attachment = [];

                if ($group) {
                    $attachment = json_decode($group->attachment, true);

                    if (!is_array($attachment)) {
                        $attachment = [];
                    }
                    if (!empty($reqAttachment)) {
                        $attachment = array_merge($attachment, $reqAttachment);
                    }

                    $group->attachment = json_encode($attachment);
                    $group->save();
                }

                if (!$group){
                    $group = ProductSerialGroup::create([
                        'user_id' => $auth->id,
                        'product_id' => $item['product_id'],
                        'warehouse_id' => $request->warehouse_id,
                        'stock_id' => $stockPurchase->id,
                        'date' => $data['purchase_date'],
                        'attachment' => json_encode($attachment),
                        'cost_price' => $item['unit_cost'],
                        'dealer_price' => $item['dealer_price'],
                        'quantity' => $item['quantity'],
                        'selling_price' => $item['selling_price'],
                    ]);
                }

                $reqSerials = [];
                foreach ($item['serials'] as $serial) {
                    $reqSerials[] = [
                        'user_id' => $auth->id,
                        'product_id' => $item['product_id'],
                        'serial_group_id' => $group->id,
                        'serial' => $serial,
                        'stock_id' => $stockPurchase->id,
                    ];
                }
                ProductSerial::insert($reqSerials);

                $currentStock = Stock::where('warehouse_id', $data['warehouse_id'])
                    ->where('product_id', $item['product_id'])->first();

                if ($currentStock){
                    $currentStock->current_stock = $currentStock->current_stock + $item['quantity'];
                    $currentStock->save();
                }else{
                    Stock::create([
                        'user_id' => $auth->id,
                        'product_id' => $item['product_id'],
                        'warehouse_id' => $data['warehouse_id'],
                        'serial_no' => implode(',', $item['serials']),
                        'unit_cost' => $item['unit_cost'],
                        'shipping_cost' => $item['shipping_cost'] ?? 0,
                        'selling_price' => $item['selling_price'],
                        'dealer_price' => $item['dealer_price'],
                        'tax' => $item['tax'] ?? 0,
                        'minimum_stock' => 5,
                        'current_stock' => $item['quantity'],
                    ]);
                }

               ProductStockMovement::create([
                    'user_id'         => $auth->id,
                    'product_id'      => $item['product_id'],
                    'warehouse_id'    => $data['warehouse_id'],
                    'shipping_cost'   => $item['shipping_cost'] ?? 0,
                    'type'            => 1,
                    'quantity'        => $item['quantity'],
                    'ref_id'          => $stockPurchase->id,
                    'note'            => $data['note'] ?? null,
                ]);

            }

            DB::commit();
            return returnData(2000, null, 'Stock purchases saved successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return returnData(5000, $e->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }
    public function show($id)
    {
        try {
            $purchase = StockPurchase::with(['product', 'warehouse', 'serials'])->find($id);

            if (!$purchase) {
                return returnData(4004, null, 'Stock purchase not found.');
            }
            $purchase->attachment = json_decode($purchase->attachment, true);

            return returnData(2000, $purchase);

        } catch (\Exception $e) {
            return returnData(5000, $e->getMessage(), 'Something went wrong!');
        }
    }

    public function edit($id)
    {
        try {
            $data = $this->model->findOrFail($id);
            $data->attachment = json_decode($data->attachment, true);
            return returnData(2000, $data);
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }

    public function update(Request $request, $id)
    {
        if (!can('stock_product.update')) {
            return $this->notPermitted();
        }

        DB::beginTransaction();
        try {
            $auth = auth()->user();
            $data = $request->all();

            $purchase = StockPurchase::findOrFail($id);

            foreach ($data['items'] as $item) {
                $purchase->update([
                    'purchase_date' => $data['purchase_date'],
                    'warehouse_id' => $data['warehouse_id'],
                    'product_id' => $item['product_id'],
                    'serial_no' => implode(',', $item['serials']),
                    'stock_status' => $data['stock_status'],
                    'note' => $data['note'] ?? null,
                    'purchase_qty' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'tax' => $item['tax'],
                    'shipping_cost' => $item['shipping_cost'],
                    'selling_price' => $item['selling_price'],
                    'dealer_price' => $item['dealer_price'],
                    'sub_total' => $item['subtotal'],
                    'attachment' => $data['attachment'] ? json_encode($data['attachment']) : null,
                ]);

                $serialGroup = ProductSerialGroup::where('product_id', $item['product_id'])
                    ->where('warehouse_id', $data['warehouse_id'])
                    ->first();

                if ($serialGroup) {
                    $serialGroup->update([
                        'user_id' => $auth->id,
                        'product_id' => $item['product_id'],
                        'warehouse_id' => $data['warehouse_id'],
                        'stock_id' => $purchase->id,
                        'date' => $data['purchase_date'],
                        'attachment' => $data['attachment'] ? json_encode($data['attachment']) : null,
                        'cost_price' => $item['unit_cost'],
                        'dealer_price' => $item['dealer_price'],
                        'selling_price' => $item['selling_price'],
                        'quantity' => $item['quantity'],
                    ]);
                }

                if ($serialGroup) {
                    ProductSerial::where('serial_group_id', $serialGroup->id)->delete();
                    foreach ($item['serials'] as $serial) {
                        ProductSerial::create([
                            'user_id' => $auth->id,
                            'product_id' => $item['product_id'],
                            'stock_id' => $purchase->id,
                            'serial_group_id' => $serialGroup->id,
                            'serial' => $serial,
                        ]);
                    }
                }

                $stock = Stock::where('product_id', $item['product_id'])
                    ->where('warehouse_id', $data['warehouse_id'])
                    ->first();

                if ($stock) {
                    $stock->update([
                        'serial_no' => implode(',', $item['serials']),
                        'unit_cost' => $item['unit_cost'],
                        'shipping_cost' => $item['shipping_cost'] ?? 0,
                        'selling_price' => $item['selling_price'],
                        'dealer_price' => $item['dealer_price'],
                        'tax' => $item['tax'] ?? 0,
                        'current_stock' => $item['quantity'],

                    ]);
                }

                $movment = ProductStockMovement::where('ref_id', $purchase->id)
                    ->where('warehouse_id', $data['warehouse_id'])
                    ->first();
//                ddA($movment);
                $movment->update([
                    'user_id'         => $auth->id,
                    'product_id'      => $item['product_id'],
                    'warehouse_id'    => $data['warehouse_id'],
                    'shipping_cost'   => $item['shipping_cost'] ?? 0,
                    'type'            => 1,
                    'quantity'        => $item['quantity'],
                    'ref_id'          => $purchase->id,
                    'note'            => $data['note'] ?? null,
                ]);

            }

            DB::commit();
            return returnData(2000, null, 'Stock updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return returnData(5000, $e->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }

    public function destroy($id)
    {
        if (!can('stock_product.destroy')) {
            return $this->notPermitted();
        }

        DB::beginTransaction();
        try {
            $data = $this->model->where('id', $id)->first();

            if (!$data){
                return returnData(5000, null, 'Data not found');
            }

            $groupIds = ProductSerial::where('stock_id', $data->id)->pluck('serial_group_id');

            ProductSerial::where('stock_id', $data->id)->delete();

            ProductSerialGroup::whereIn('id', $groupIds)
                ->whereDoesntHave('serials')
                ->delete();

            $stock = Stock::where('product_id', $data->product_id)->where('warehouse_id', $data->warehouse_id)->first();

            if ($stock){
                $stock->current_stock = $stock->current_stock - $data->purchase_qty;
                $stock->update();
            }

            if (!$data) {
                return returnData(5000, null, 'Data Not found');
            }

            $data->delete();

            DB::commit();

            return returnData(2000, $data, 'Successfully Deleted');

        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }
}

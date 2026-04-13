<?php


namespace App\ProductStock;

use App\Models\Inventory\ProductStockMovement;
use App\Models\Inventory\Stock;
use Illuminate\Support\Facades\Log;

class ProductStockService
{
    public static function initStock($product, $source = 1, $userId = null, $note = null)
    {
        try {

            $qtyChange = $product->current_stock ?? 0;
            $costPrice = $product->cost_price ?? 0;
            $warehouseId = $product->warehouse_id;
            $minimumStock = $product->alert_quantity ?? 0;

//            ddA($minimumStock);

            $currentStock = Stock::where('warehouse_id', $product->warehouse_id)
                ->where('product_code', $product->product_code)
                ->first();

            if ($currentStock) {
                $currentStock->update([
                    'current_stock' => $qtyChange,
                    'unit_cost' => $costPrice,
                    'minimum_stock' => $minimumStock,
                ]);
            } else {
              Stock::create([
                    'product_id'          => $product->id,
                    'product_code'        => $product->product_code,
                    'warehouse_id'        => $product->warehouse_id,
                    'parent_id'           => $product->parent_id ?? null,
                    'sub_category_id'     => $product->sub_category_id ?? null,
                    'sub_sub_category_id' => $product->sub_sub_category_id ?? null,
                    'unit_cost'           => $product->cost_price ?? 0,
                    'current_stock'       => $qtyChange,
                    'minimum_stock'       => $minimumStock,
                    'last_updated'        => now(),
                    'user_id'             => $userId ?? $product->user_id ?? null,
                    'dealer_id'           => $product->dealer_id ?? null,
                ]);
            }

            self::productStockMovement(
                $product,
                $warehouseId,
                $qtyChange,
                $source,
                $note,
                $product->id,
                $userId
            );

        } catch (\Exception $e) {
            Log::error('Stock initialization failed: '.$e->getMessage());
        }
    }

//    public static function addToProductSerialGroup($data = [])
//    {
//        $date = $data['date'] ?? now();
//    }

    public static function productStockMovement(
        $product,
        $warehouseId,
        $qtyChange,
        $source = 1,
        $note = null,
        $refId = null,
        $userId = null
    )
    {
//        dd($product);
        try {

            ProductStockMovement::create([
                'product_id'   => $product->id,
                'warehouse_id' => $warehouseId,
                'type'         => $source,
                'ref_id'       => $refId ?? $product->id,
                'quantity'     => $qtyChange,
                'note'         => $note ?? 'Product Purchase IN',
                'user_id'      => $userId ?? $product->user_id ?? null,
            ]);

        } catch (\Exception $e) {
            Log::error('Stock movement record failed: ' . $e->getMessage());
        }
    }
}

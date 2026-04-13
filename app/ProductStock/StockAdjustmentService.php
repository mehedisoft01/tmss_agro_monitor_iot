<?php

namespace App\ProductStock;

use App\Models\Inventory\Stock;
use App\Models\Inventory\ProductStockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockAdjustmentService
{
    public static function adjust($product, $warehouseId, $quantity, $type, $note = null)
    {
        DB::beginTransaction();

        try {
            // Get or create stock
            $stock = Stock::where('warehouse_id', $warehouseId)
                ->where('product_code', $product->product_code)
                ->first();

            if (!$stock) {
                $stock = Stock::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouseId,
                    'product_code' => $product->product_code,
                    'unit_cost' => $product->cost_price ?? 0,
                    'current_stock' => 0,
                    'reorder_level' => 0,
                    'user_id' => $product->user_id ?? null,
                    'dealer_id' => $product->dealer_id ?? null,
                ]);
            }

            // Adjust stock
            if ($type == 1) {
                $stock->current_stock += $quantity;
            } elseif ($type == 2) {
                $stock->current_stock = max(0, $stock->current_stock - $quantity);
            }

            $stock->save();

            // Save ProductStockMovement
            ProductStockMovement::create([
                'user_id' => $product->user_id ?? null,
                'dealer_id' => $product->dealer_id ?? null,
                'product_id' => $product->id,
                'warehouse_id' => $warehouseId,
                'ref_id' => $product->id,
                'type' => $type,
                'quantity' => $quantity,
                'note' => 'Stock adjusted',
                'status' => 1
            ]);

            DB::commit();

        } catch (\Exception $e) {
            Log::error('Stock adjustment failed: '.$e->getMessage());
        }
    }
}
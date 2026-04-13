<?php

namespace App\ProductStock;

use App\Models\Inventory\ProductStockMovement;
use Illuminate\Support\Facades\Log;

class StockProduct
{
    public static function productStockMovement($product, $warehouseId, $qtyChange, $source = 1, $note = null, $refId = null,  $userId = null)
    {
        try {
            ProductStockMovement::create([
                'product_id'   => $product->id,
                'warehouse_id' => $warehouseId,
                'type'         => $source,
                'ref_id'       => $refId ?? $product->id,
                'quantity'     => $qtyChange,
                'note'         => $note ?? 'Stock Update',
                'user_id'      => $userId,
            ]);
        } catch (\Exception $e) {
            Log::error('Stock movement record failed: ' . $e->getMessage());
        }
    }
}

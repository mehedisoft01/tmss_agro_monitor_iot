<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait CheckWarehouseTrait
{
    public function scopeCheckWarehouse($query, $column = null)
    {
        $user = Auth::user();

        if (is_null($column)) {
            $column = 'warehouse_id';
        }

        if (!$user) {
            return $query;
        }

        // 🔓 Super Admin OR Finance Manager → no warehouse restriction
        if ($user->is_superadmin == 1 || $user->manager == 0) {
            return $query;
        }

        if ($user && !is_null($user->warehouse_id)) {
            return $query->where($column, $user->warehouse_id);
        }

        return $query;
    }
}

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\AdminApiAuthController;
use App\Http\Controllers\Backend\AuthController;
use App\Http\Controllers\Backend\Inventory\WarehouseController;
use App\Http\Controllers\Backend\DealerManagement\DealerManagementController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\Backend\ProductManagement\ProductController;
use App\Http\Controllers\Backend\Sales\OrderController;
use App\Http\Controllers\Backend\Sales\OrderPendingApproveController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\Backend\Sales\CustomerController;
use \App\Http\Controllers\Backend\Sales\InvoiceController;
use \App\Http\Controllers\api\DashboardApiController;
use \App\Http\Controllers\api\IndexApiController;
use \App\Http\Controllers\Backend\Inventory\StockPurchaseController;
use \App\Http\Controllers\Backend\ProductManagement\PricingSetupController;

Route::prefix('admin')->group(function () {

    // Public
    Route::post('login', [AdminApiAuthController::class, 'login']);
    Route::post('app_info', [AdminApiAuthController::class, 'appInFo']);

    // Protected
    Route::middleware('jwt.auth')->group(function () {
        Route::post('logout', [AdminApiAuthController::class, 'logout']);
        Route::post('refresh', [AdminApiAuthController::class, 'refresh']);
        Route::get('profile', [AuthController::class, 'index']);
        Route::get('me', [AdminApiAuthController::class, 'me']);
        Route::post('file_upload', [FileController::class, 'fileUpload']);
        Route::post('general', [SupportController::class, 'getGeneralData']);

        Route::get('get_dashboard_api', [DashboardApiController::class, 'dashboardDataAPI']);
        Route::resource('customer', CustomerController::class);
        Route::delete('customer/delete/{id}', [CustomerController::class, 'destroy']);
        Route::get('customer_list', [IndexApiController::class, 'index_customer']);
//        Route::get('customer_list', [CustomerController::class, 'index']);
        Route::get('invoice_information_list', [InvoiceController::class, 'index']);
        Route::get('get_address/{type}/{id}', [CustomerController::class, 'getAddress']);
        // Route::get('warehouse_list', [WarehouseController::class, 'index']);
        Route::get('warehouse_list', [IndexApiController::class, 'index_warehouse']);
        Route::delete('warehouse_delete/{id}', [WarehouseController::class, 'destroy']);
        Route::resource('warehouses', WarehouseController::class);

        Route::prefix('dealers')->group(function () {
//            Route::get('list', [DealerManagementController::class,'index']);
            Route::get('list', [IndexApiController::class, 'index_dealer']);
            Route::resource('submit_edit', DealerManagementController::class);
            Route::delete('delete/{id}', [DealerManagementController::class, 'destroy']);
        });

        Route::prefix('products')->group(function () {
//            Route::get('list', [ProductController::class, 'index']);
            Route::get('list', [IndexApiController::class, 'index_product']);
            Route::resource('submit_edit', ProductController::class);
            Route::delete('delete/{id}', [ProductController::class, 'destroy']);
        });

        Route::prefix('orders')->group(function () {
            Route::get('list', [IndexApiController::class, 'index_order']);
            Route::resource('create_sale', OrderController::class);
            Route::resource('submit_edit', OrderController::class);
            Route::delete('delete/{id}', [OrderController::class, 'destroy']);
            Route::resource('pending', OrderPendingApproveController::class);
            Route::post('approved', [OrderPendingApproveController::class, 'store']);
            Route::post('create_sale/{id}/attachment', [OrderController::class, 'updateAttachment']);
            Route::post('update_payment_status', [OrderPendingApproveController::class,'updatePaymentStatus']);
        });
        Route::prefix('stock')->group(function () {
            Route::get('product_list', [IndexApiController::class, 'index_stock_product']);
            Route::resource('submit_edit', StockPurchaseController::class);
            Route::delete('delete/{id}', [StockPurchaseController::class, 'destroy']);
        });
        Route::prefix('pricing')->group(function () {
            Route::resource('markup', PricingSetupController::class);
            Route::post('get_products',[PricingSetupController::class,'getProducts']);
        });

    });
});

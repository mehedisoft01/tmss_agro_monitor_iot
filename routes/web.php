
<?php

use App\Http\Controllers\Backend\DeviceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceStatusController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/locale.json', [\App\Http\Controllers\SupportController::class, 'getLocalization']);
Route::get('/update.json', [\App\Http\Controllers\SupportController::class, 'addLocalization']);
Route::get('/routes.json', [\App\Http\Controllers\SupportController::class, 'getRoutes']);
Route::get('/load.json', [\App\Http\Controllers\SupportController::class, 'loadJson']);

Route::middleware('guest')->group(function () {
    Route::get('/', [\App\Http\Controllers\Backend\AuthController::class, 'login'])->name('login');
    Route::get('login', [\App\Http\Controllers\Backend\AuthController::class, 'login'])->name('login');
    Route::post('login', [\App\Http\Controllers\Backend\AuthController::class, 'doLogin'])->name('login.submit');
});
Route::middleware([\App\Http\Middleware\AuthMiddleware::class, \App\Http\Middleware\LogActivity::class])->group(function () {
    Route::get('/admin/{any?}', [\App\Http\Controllers\Backend\DashboardController::class, 'singleApp'])
        ->where('any', '.*')->name('home');
    Route::get('/auth/{any?}', [\App\Http\Controllers\Backend\DashboardController::class, 'employeeApp'])
        ->where('any', '.*')->name('employee_home');

    Route::get('logout', [\App\Http\Controllers\Backend\AuthController::class, 'logout'])->name('logout');
//    Route::get('/billing_info',[\App\Http\Controllers\BillingController::class, 'getBillingInfo']);


    Route::prefix('api')->group(function () {
        Route::post('file_upload', [\App\Http\Controllers\FileController::class, 'fileUpload']);
        Route::post('general', [\App\Http\Controllers\SupportController::class, 'getGeneralData']);
        Route::post('configurations', [\App\Http\Controllers\SupportController::class, 'appConfigurations']);
        Route::resource('app_notification', \App\Http\Controllers\AppNotificationController::class);
        Route::get('dashboard', [\App\Http\Controllers\SupportController::class, 'appDashboard']);
        Route::get('activities', [\App\Http\Controllers\SupportController::class, 'userActivities']);

        Route::resource('modules', \App\Http\Controllers\Backend\RBAC\ModuleController::class);
        Route::resource('/roles', \App\Http\Controllers\Backend\RBAC\RoleController::class);
        Route::resource('/module_permissions', \App\Http\Controllers\Backend\RBAC\RoleModuleController::class);
        Route::resource('/role_permissions', \App\Http\Controllers\Backend\RBAC\RolePermissionController::class);

        Route::resource('/devices', DeviceController::class);
        Route::resource('/device_thresholds', \App\Http\Controllers\DeviceThresholdsController::class);
        Route::post('/check_thresholds', [\App\Http\Controllers\DeviceThresholdsController::class, 'checkAndNotify']);
        Route::get('/notification_alerts', [NotificationController::class, 'index']);
        Route::get('/notifications/unread_count', [NotificationController::class, 'unreadCount']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::resource('/device_logs', DeviceStatusController::class);
        Route::get('/devices/{device_id}/logs', [DeviceController::class, 'fetchAndStoreStatus']);
        Route::get('/devices/logs/index', [DeviceController::class, 'indexLogs']);
        Route::resource('/soil_device', \App\Http\Controllers\SoilDeviceController::class);

        Route::get('/warehouse_reports', [\App\Http\Controllers\ReportController::class, 'warehouseReport']);
        Route::get('/warehouse_reports/excel', [\App\Http\Controllers\ReportController::class, 'warehouseReportExportExcel'])
            ->name('warehouse_report.excel');

        Route::get('/soil_reports/excel', [\App\Http\Controllers\ReportController::class, 'soilReportExportExcel'])
            ->name('soil_reports.excel');

        Route::get('/soil_reports', [\App\Http\Controllers\ReportController::class, 'soilReport']);

        Route::get('/dashboard', [DashboardController::class, 'dashboardData']);
        Route::get('/upload_excell', [DashboardController::class, 'uploadExcell']);
        Route::post('/upload_excell', [DashboardController::class, 'submitUploadExcell']);

        Route::get('/dashboardV2', [DashboardController::class, 'dashboardDataV2']);
        Route::get('/storageData', [DashboardController::class, 'storageData']);

        Route::resource('settings', \App\Http\Controllers\SettingController::class);
        Route::resource('profile', \App\Http\Controllers\Backend\AuthController::class);
        Route::resource('users', \App\Http\Controllers\Backend\UserController::class);
        Route::post('profile', [\App\Http\Controllers\Backend\AuthController::class, 'update']);
        Route::resource('modules', \App\Http\Controllers\Backend\RBAC\ModuleController::class);
    });

});


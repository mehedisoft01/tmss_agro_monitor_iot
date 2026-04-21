<?php

namespace App\Http\Controllers;

use App\Exports\SoilReportExport;
use App\Exports\WarehouseReportExport;
use App\Models\DeviceStatus;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    /**
     * Reusable report function
     */
    public function warehouseReport(Request $request)
    {
        $device = $request->input('device_id');
        $date_from = $request->input('date_from');
        $date_to = $request->input('date_to');

        $data = DeviceStatus::with(['device' => function ($query) {
            $query->where('device_category', 1);
        }])
            ->whereHas('device', function ($q) use ($device) {
                $q->where('device_category', 1);

                if ($device) {
                    $q->where('device_id', $device);
                }
            })
            ->when($date_from && $date_to, function ($query) use ($date_from, $date_to) {
                $query->whereBetween('created_at', [
                    $date_from . ' 00:00:00',
                    $date_to . ' 23:59:59'
                ]);
            })
            ->orderBy('device_id')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                $item->formatted_date = \Carbon\Carbon::parse($item->created_at)->format('Y-m-d H:i');
                return $item;
            })
            ->groupBy(function ($item) {
                return $item->device->display_name ?? 'Unknown Device';
            });

        return returnData(2000, $data);
    }

    public function soilReport(Request $request)
    {
        $device = $request->input('device_id');
        $date_from = $request->input('date_from');
        $date_to = $request->input('date_to');

        $data = DB::table('site_readings as sr')
            ->leftJoin('soil_devices as d', 'sr.site_id', '=', 'd.id')
//            ->where('d.device_category', 2)
            ->when($device, function ($query) use ($device) {
                $query->where('d.device_id', $device);
            })
            ->when($date_from && $date_to, function ($query) use ($date_from, $date_to) {
                $query->whereBetween('sr.created_at', [
                    $date_from . ' 00:00:00',
                    $date_to . ' 23:59:59'
                ]);
            })
            ->orderBy('d.device_id')
            ->orderBy('sr.created_at', 'desc' )           // তারিখ অনুসারে সাজানো
            ->select(
                'sr.*',
                'd.device_name',
//                'd.device_name as device_name',
                'd.device_id'
            )
            ->get()
            ->map(function ($item) {
                $item->formatted_date = \Carbon\Carbon::parse($item->created_at)
                    ->format('Y-m-d H:i');
                return $item;
            })
            ->groupBy('device_name');   // display_name দিয়ে groupBy

        return returnData(2000, $data);
    }

    public function warehouseReportExportExcel(Request $request){
        return Excel::download(
            new WarehouseReportExport($request->all()),
            'warehouse_report.xlsx'
        );
    }

    public function soilReportExportExcel(Request $request){
        return Excel::download(
            new SoilReportExport($request->all()),
            'soil_report.xlsx'
        );
    }
}
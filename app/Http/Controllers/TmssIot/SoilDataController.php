<?php

namespace App\Http\Controllers\TmssIot;

use App\Exports\SoilDataExport;
use App\Exports\SoilReportExport;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class SoilDataController extends Controller
{
    public function soilData(Request $request)
    {
        $farmer = $request->input('farmer_id');
        $date_from = $request->input('date_from');
        $date_to = $request->input('date_to');

        $data = DB::table('soil_readigs as sr')

            ->leftJoin(
                'soil_devices as d',
                'sr.site_id',
                '=',
                'd.id'
            )

            ->leftJoin(
                'farmers as f',
                'sr.farmer_id',
                '=',
                'f.id'
            )

            ->when($farmer, function ($query) use ($farmer) {

                $query->where('sr.farmer_id', $farmer);

            })

            ->when($date_from && $date_to, function ($query) use (
                $date_from,
                $date_to
            ) {

                $query->whereBetween('sr.created_at', [
                    $date_from . ' 00:00:00',
                    $date_to . ' 23:59:59'
                ]);

            })

            ->orderBy('d.device_id', 'asc')

            ->orderBy(
                'sr.reading_time',
                'desc'
            )

            ->select(
                'sr.*',
                'd.device_name',
                'd.device_id',
                'f.name as farmer_name'
            )

            ->get()

            ->map(function ($item) {

                $item->formatted_date = Carbon::parse(
                    $item->reading_time
                )->format('Y-m-d H:i');

                return $item;
            })

            ->groupBy('device_name');

        return returnData(2000, $data);
    }

    public function soilDataReportExportExcel(Request $request)
    {
        return Excel::download(
            new SoilDataExport($request->all()),
            'soil_report.xlsx'
        );
    }
}

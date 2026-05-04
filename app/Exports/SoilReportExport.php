<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SoilReportExport implements FromCollection, WithHeadings
{
    protected $data;

    public function __construct($requestData)
    {
        $this->data = $requestData;
    }

    public function headings(): array
    {
        return [
            'SL',
            'Device Name',
            'Date',
            'Temperature (°C)',
            'Humidity (%)',
            'Conductivity',
            'N',
            'P',
            'K',
            'Fertility',
            'Remarks'
        ];
    }

    public function collection()
    {
        $device     = $this->data['device_id'] ?? null;
        $date_from  = $this->data['date_from'] ?? null;
        $date_to    = $this->data['date_to'] ?? null;

        // ✅ Correct Query (IMPORTANT FIXED)
        $query = DB::table('site_readings_report as sr')
            ->leftJoin('soil_devices as d', 'sr.site_id', '=', 'd.id')

            ->when($device, function ($q) use ($device) {
                $q->where('d.device_id', $device);
            })
            ->when($date_from && $date_to, function ($q) use ($date_from, $date_to) {
                $q->whereBetween('sr.created_at', [
                    $date_from . ' 00:00:00',
                    $date_to . ' 23:59:59'
                ]);
            })
            ->orderBy('d.id')
            ->orderBy('sr.created_at')

            ->select(
                'sr.*',
                'd.device_name'
            )
            ->get();

        $data = [];
        $sl = 1;

        foreach ($query as $item) {
            $data[] = [
                $sl++,
                $item->device_name ?? '',
                \Carbon\Carbon::parse($item->created_at)->format('Y-m-d H:i'),
                $item->temperature ?? '',
                $item->humidity ?? '',
                $item->conductivity ?? '',
                $item->n ?? '',
                $item->p ?? '',
                $item->k ?? '',
                $item->fertility ?? '',
                $item->remarks ?? '',
            ];
        }

        return collect($data);
    }
}

//    public function collection()
//    {
//        $device = $this->data['device_id'] ?? null;
//        $date_from = $this->data['date_from'] ?? null;
//        $date_to = $this->data['date_to'] ?? null;
//
//        $query = DB::table('site_readings as sr')
//            ->leftJoin('devices as d', 'sr.site_id', '=', 'd.device_id')
//            ->where('d.device_category', 2)
//            ->when($device, function ($q) use ($device) {
//                $q->where('d.device_id', $device);
//            })
//            ->when($date_from && $date_to, function ($q) use ($date_from, $date_to) {
//                $q->whereBetween('sr.created_at', [
//                    $date_from . ' 00:00:00',
//                    $date_to . ' 23:59:59'
//                ]);
//            })
//            ->orderBy('d.device_id')
//            ->orderBy('sr.created_at')
//            ->select(
//                'sr.*',
//                'd.display_name'
//            )
//            ->get();
//
//        $data = [];
//        $sl = 1;
//
//        foreach ($query as $item) {
//            $data[] = [
//                $sl++,
//                $item->display_name ?? '',
//                \Carbon\Carbon::parse($item->created_at)->format('Y-m-d H:i'),
//                $item->temperature ?? '',
//                $item->humidity ?? '',
//                $item->conductivity ?? '',
//                $item->n ?? '',
//                $item->p ?? '',
//                $item->k ?? '',
//                $item->fertility ?? '',
//                $item->remarks ?? '',
//            ];
//        }
//
//        return new Collection($data);
//    }
//}
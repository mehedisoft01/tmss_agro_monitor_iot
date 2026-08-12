<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SoilDataExport implements FromCollection, WithHeadings
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
            'Farmer Name',
            'Device Name',
            'Date',
            'Temperature (°C)',
            'Humidity (%)',
            'Conductivity',
            'N',
            'P',
            'K',
            'Fertility',
            'pH',
            'Remarks'
        ];
    }

    public function collection()
    {
        $farmer = $this->data['farmer_id'] ?? null;
        $date_from = $this->data['date_from'] ?? null;
        $date_to = $this->data['date_to'] ?? null;

        $query = DB::table('soil_readigs as sr')

            ->leftJoin('soil_devices as d', 'sr.site_id', '=', 'd.id')
            ->leftJoin('farmers as f', 'sr.farmer_id', '=', 'f.id')
            ->when($farmer, function ($q) use ($farmer) {
                $q->where('sr.farmer_id', $farmer);
            })

            ->when(
                $date_from && $date_to,
                function ($q) use ($date_from, $date_to) {
                    $q->whereBetween(
                        'sr.reading_time',
                        [
                            $date_from . ' 00:00:00',
                            $date_to . ' 23:59:59'
                        ]
                    );

                }
            )

            ->orderBy('d.device_id', 'asc')
            ->orderBy('sr.reading_time', 'desc')
            ->select('sr.*', 'd.device_name', 'd.device_id', 'f.name as farmer_name')
            ->get();
        $data = [];
        $sl = 1;
        foreach ($query as $item) {
            $data[] = [
                $sl++,
                $item->farmer_name ?? '',
                $item->device_name ?? '',
                $item->reading_time ? \Carbon\Carbon::parse($item->reading_time)->format('Y-m-d H:i') : '',
                $item->temperature ?? '',
                $item->humidity ?? '',
                $item->conductivity ?? '',
                $item->n ?? '',
                $item->p ?? '',
                $item->k ?? '',
                $item->fertility ?? '',
                $item->ph ?? '',
                $item->remarks ?? '',
            ];
        }


        return collect($data);
    }
}
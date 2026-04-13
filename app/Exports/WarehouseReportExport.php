<?php

namespace App\Exports;

use App\Models\DeviceStatus;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class WarehouseReportExport implements FromCollection, WithHeadings
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
            'Remarks'
        ];
    }

    public function collection()
    {
        $device = $this->data['device_id'] ?? null;
        $date_from = $this->data['date_from'] ?? null;
        $date_to = $this->data['date_to'] ?? null;

        $result = DeviceStatus::with(['device' => function ($query) {
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
            ->get();

        $data = [];
        $sl = 1;

        foreach ($result as $item) {
            $data[] = [
                $sl++,
                $item->device->display_name ?? '',
                \Carbon\Carbon::parse($item->recorded_at)->format('Y-m-d H:i'),
                $item->temperature,
                $item->humidity,
                $item->remarks,
            ];
        }

        return new Collection($data);
    }
}
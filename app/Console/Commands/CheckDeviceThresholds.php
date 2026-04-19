<?php

namespace App\Console\Commands;

use App\Models\DeviceStatus;
use App\Models\DeviceThreshold;
use App\Models\Notification;
use App\Models\SoilDevice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckDeviceThresholds extends Command
{
    protected $signature = 'threshold:check';
    protected $description = 'Check device thresholds and create notifications';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $thresholds = DeviceThreshold::all();

        foreach ($thresholds as $threshold) {

            $deviceStatus = DeviceStatus::latest('recorded_at')->first();

            if (!$deviceStatus) {
                $this->info("No device status found!");
                continue;
            }

            $currentValue = 0;

            // warehouse sensor mapping
            if ($threshold->sensor_id == 1) {
                $currentValue = $deviceStatus->temperature;
            } elseif ($threshold->sensor_id == 2) {
                $currentValue = $deviceStatus->humidity;
            } else {
                continue;
            }

            if ($currentValue < $threshold->min_value || $currentValue > $threshold->max_value) {

                //  LAST NOTIFICATION CHECK
                $lastNotification = Notification::where([
                    'device_id' => $deviceStatus->device_id,
                    'sensor_id' => $threshold->sensor_id,
                ])->latest()->first();

                if (!$lastNotification || $lastNotification->current_value != $currentValue) {

                    Notification::create([
                        'device_id' => $deviceStatus->device_id,
                        'device_category_id' => $threshold->device_category_id,
                        'sensor_id' => $threshold->sensor_id,
                        'current_value' => $currentValue,
                        'min_value' => $threshold->min_value,
                        'max_value' => $threshold->max_value,
                        'message' => "Alert! Value out of range",
                        'is_read' => 0,
                    ]);

                    $this->info("Notification created for device {$deviceStatus->device_id}");
                }
            }
        }

        // ===============================
        //  SOIL DEVICE THRESHOLD CHECK
        // ===============================

        $soilDevices = SoilDevice::all();

        foreach ($soilDevices as $device) {

            $latestReading = DB::table('site_readings')
                ->where('site_id', $device->id)
                ->orderBy('id', 'desc')
                ->first();

            if (!$latestReading) {
                continue;
            }

            $thresholds = DeviceThreshold::where('device_category_id', 2)->get();

            foreach ($thresholds as $threshold) {

                $currentValue = null;

                switch ($threshold->sensor_id) {

                    case 5:
                        $currentValue = $latestReading->n;
                        break;
                    case 6:
                        $currentValue = $latestReading->p;
                        break;
                    case 7:
                        $currentValue = $latestReading->k;
                        break;
                    case 8:
                        $currentValue = $latestReading->conductivity;
                        break;
                    case 9:
                        $currentValue = $latestReading->ph;
                        break;
                    case 10:
                        $currentValue = $latestReading->temperature;
                        break;
                    case 11:
                        $currentValue = $latestReading->humidity;
                        break;
                    case 12:
                        $currentValue = $latestReading->fertility;
                        break;
                    default:
                        continue 2;
                }

                if ($currentValue === null) continue;

                if ($currentValue < $threshold->min_value || $currentValue > $threshold->max_value) {

                    // LAST NOTIFICATION CHECK
                    $lastNotification = Notification::where([
                        'device_id' => $device->device_id,
                        'sensor_id' => $threshold->sensor_id,
                    ])->latest()->first();

                    if (!$lastNotification || $lastNotification->current_value != $currentValue) {

                        Notification::create([
                            'device_id' => $device->device_id,
                            'device_category_id' => 2,
                            'sensor_id' => $threshold->sensor_id,
                            'current_value' => $currentValue,
                            'min_value' => $threshold->min_value,
                            'max_value' => $threshold->max_value,
                            'message' => "Soil Alert! Value out of range",
                            'is_read' => 0,
                        ]);

                        $this->info("Soil Notification created for device {$device->device_id}");
                    }
                }
            }
        }

        $this->info('🔥 Threshold check completed.');
    }
}

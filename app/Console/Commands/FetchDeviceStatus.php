<?php

namespace App\Console\Commands;

use App\Http\Controllers\Backend\DeviceController;
use App\Models\Device;
use Illuminate\Console\Command;

class FetchDeviceStatus extends Command
{
    /**
     * Command name
     */
    protected $signature = 'device:fetch-status';

    /**
     * Description
     */
    protected $description = 'Fetch device status and store in database';

    /**
     * Execute the console command
     */
    public function handle()
    {
        $controller = new DeviceController();

        $deviceIds = Device::pluck('device_id')->toArray();
        $deviceIds[] = 'bfeb0a04e9c7a32d15pfby';

        $deviceIds = array_unique($deviceIds);

        foreach ($deviceIds as $deviceId) {
            try {

                $controller->fetchAndStoreStatus(request(), $deviceId);

                $this->info("Device {$deviceId} status fetched");

            } catch (\Exception $e) {

                $this->error(
                    "Device {$deviceId} failed: {$e->getMessage()}"
                );
            }
        }

        $this->info("All device status fetched successfully");

        return Command::SUCCESS;
    }
}

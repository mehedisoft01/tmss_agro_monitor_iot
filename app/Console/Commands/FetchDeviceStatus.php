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
        $devices = Device::all();

        $controller = new DeviceController();

        foreach ($devices as $device) {

            $controller->fetchAndStoreStatus(request(), $device->device_id);

            $this->info("Device {$device->device_id} status fetched");
        }

        $this->info("All device status fetched successfully");

        return Command::SUCCESS;
    }
}

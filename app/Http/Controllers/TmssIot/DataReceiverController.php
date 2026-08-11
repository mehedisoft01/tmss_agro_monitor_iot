<?php

namespace App\Http\Controllers\TmssIot;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\TmssIot\FarmerDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DataReceiverController extends Controller
{


    use Helper;

    public function index(Request $request)
    {
        $device = $request->input('device_id');
        $date_from = $request->input('date_from');
        $date_to = $request->input('date_to');
        $farmer_id = $request->input('farmer_id');
        $data = DB::table('soil_readigs as sr')
            ->leftJoin('farmer_devices as d', 'sr.site_id', '=', 'd.id')
            ->leftJoin('farmers as f', 'sr.farmer_id', '=', 'f.id')
            ->when($farmer_id, function ($query) use ($farmer_id) {
                $query->where('sr.farmer_id', $farmer_id);
            })
            ->when($device, function ($query) use ($device) {
                $query->where('d.device_id', $device);
            })
            ->when($date_from && $date_to, function ($query) use ($date_from, $date_to) {
                $query->whereBetween('sr.created_at', [
                    $date_from . ' 00:00:00',
                    $date_to . ' 23:59:59'
                ]);
            })
            ->orderBy('sr.created_at', 'desc')
            ->select(
                'sr.*',
                'd.device_id',
                'd.status as device_status',
                'f.name as farmer_name'
            )
            ->paginate($request->input('perPage', 15))
            ->through(function ($item) {

                $item->formatted_date = \Carbon\Carbon::parse(
                    $item->created_at
                )->format('Y-m-d H:i');

                return $item;
            });

        return returnData(2000, $data);
    }


    public function devices()
    {
        try {

            $devices = FarmerDevice::orderBy('id', 'asc')->get();

            return response()->json([
                'success' => true,
                'data' => $devices,
                'message' => 'Farmer devices fetched successfully'
            ], 200);

        } catch (\Throwable $e) {

            \Log::error(
                'Farmer devices error: ' . $e->getMessage()
            );

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function disableFarmerDevice(Request $request)
    {
        try {

            $deviceId = $request->input('device_id');

            if (!$deviceId) {

                return response()->json([
                    'success' => false,
                    'message' => 'Device ID is required'
                ], 400);
            }

            $device = FarmerDevice::where(
                'device_id',
                $deviceId
            )->first();

            if (!$device) {

                return response()->json([
                    'success' => false,
                    'message' => 'Device not found'
                ], 404);
            }

            $device->status = 0;

            $device->save();

            return response()->json([

                'success' => true,

                'message' => 'Device disabled successfully',

                'data' => [

                    'id' => $device->id,

                    'farmer_id' => $device->farmer_id,

                    'device_id' => $device->device_id,

                    'status' => $device->status,

                ]

            ], 200);

        } catch (\Throwable $e) {

            \Log::error(
                'Disable farmer device error: ' . $e->getMessage()
            );

            return response()->json([

                'success' => false,

                'message' => $e->getMessage()

            ], 500);
        }
    }
}
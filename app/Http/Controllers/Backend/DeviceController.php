<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceStatus;
use App\Models\DeviceThreshold;
use App\Models\SoilDevice;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeviceController extends Controller
{
    private $clientId = 'rctv3rj9nv9kyx5dx87d';
    private $clientSecret = '54c47c586cc74e8082857ab54bd91a0d';
    private $apiBase = 'https://openapi.tuyaeu.com';

    private function getAccessToken()
    {
        $client = new Client();
        $t = round(microtime(true) * 1000);
        $stringToSign = "GET\n" . hash('sha256', '') . "\n" . "\n" . "/v1.0/token?grant_type=1";

        $signInput = $this->clientId . $t . $stringToSign;
        $sign = strtoupper(hash_hmac('sha256', $signInput, $this->clientSecret));

        $response = $client->get("{$this->apiBase}/v1.0/token?grant_type=1", [
            'headers' => [
                'client_id'     => $this->clientId,
                't'             => $t,
                'sign_method'   => 'HMAC-SHA256',
                'sign'          => $sign,
            ]
        ]);

        $data = json_decode($response->getBody(), true);

        if (!isset($data['success']) || !$data['success']) {
            throw new \Exception($data['msg'] ?? 'Failed to get access token');
        }

        return $data['result']['access_token'];
    }

    private function getDeviceInfo($deviceId, $accessToken)
    {
        $client = new Client();
        $t = round(microtime(true) * 1000);
        $nonce = bin2hex(random_bytes(8));

        $method = 'GET';
        $bodyHash = hash('sha256', '');
        $headersString = '';
        $urlPath = "/v1.0/devices/{$deviceId}";

        $stringToSign = $method . "\n" . $bodyHash . "\n" . $headersString . "\n" . $urlPath;

        $signInput = $this->clientId . $accessToken . $t . $nonce . $stringToSign;
        $sign = strtoupper(hash_hmac('sha256', $signInput, $this->clientSecret));

        $response = $client->get("{$this->apiBase}{$urlPath}", [
            'headers' => [
                'client_id'     => $this->clientId,
                'access_token'  => $accessToken,
                't'             => $t,
                'sign_method'   => 'HMAC-SHA256',
                'sign'          => $sign,
                'nonce'         => $nonce,
            ]
        ]);

        $data = json_decode($response->getBody(), true);

        if (!isset($data['success']) || !$data['success']) {
            throw new \Exception($data['msg'] ?? 'Failed to fetch device info - ' . json_encode($data));
        }

        return $data['result'];
    }

    public function index()
    {
        $data = Device::get();
        return returnData(2000, $data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string',
        ]);

        try {
            $accessToken = $this->getAccessToken();
            $deviceData  = $this->getDeviceInfo($request->device_id, $accessToken);

            $device = Device::updateOrCreate(
                ['device_id' => $deviceData['id']],
                [
                    'device_id'    => $request->device_id         ?? null,
                    'display_name' => $request->display_name      ?? null,
                    'device_category' => $request->device_category      ?? null,
                    'name'         => $deviceData['name']         ?? null,
                    'model'        => $deviceData['model']        ?? null,
                    'product_id'   => $deviceData['product_id']   ?? null,
                    'product_name' => $deviceData['product_name'] ?? null,
                    'online'       => $deviceData['online']       ?? false,
                    'lat'          => $deviceData['lat']          ?? null,
                    'lon'          => $deviceData['lon']          ?? null,
                    'local_key'    => $deviceData['local_key']    ?? null,
                    'time_zone'    => $deviceData['time_zone']    ?? null,
                    'client_id'    => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'api_base'     => $this->apiBase,
                ]
            );

            if ($request->device_category == 1) {

                DeviceThreshold::updateOrCreate(
                    ['device_id' => $request->device_id],
                    [
                        'temp_min' => $request->temp_min,
                        'temp_max' => $request->temp_max,
                        'humidity_min' => $request->humidity_min,
                        'humidity_max' => $request->humidity_max,
                    ]
                );
            }

            return returnData(2000, null, "Device added successfully");
        } catch (\Exception $e) {
            return returnData(5000, null, $e->getMessage());
        }
    }

    private function getDeviceStatus($deviceId, $accessToken)
    {
        $client = new Client();
        $t = round(microtime(true) * 1000);
        $nonce = bin2hex(random_bytes(8));

        $path = "/v1.0/devices/{$deviceId}";
        $fullPath = $path;

        $method = 'GET';
        $bodyHash = hash('sha256', '');
        $headersString = '';
        $stringToSign = $method . "\n" . $bodyHash . "\n" . $headersString . "\n" . $fullPath;

        $signInput = $this->clientId . $accessToken . $t . $nonce . $stringToSign;
        $sign = strtoupper(hash_hmac('sha256', $signInput, $this->clientSecret));

        $response = $client->get("{$this->apiBase}{$fullPath}", [
            'headers' => [
                'client_id'     => $this->clientId,
                'access_token'  => $accessToken,
                't'             => $t,
                'sign_method'   => 'HMAC-SHA256',
                'sign'          => $sign,
                'nonce'         => $nonce,
            ]
        ]);

        $data = json_decode($response->getBody(), true);

        if (!isset($data['success']) || !$data['success']) {
            throw new \Exception("Failed to fetch device status: " . ($data['msg'] ?? json_encode($data)));
        }

        return $data['result'] ?? [];
    }

    public function fetchAndStoreStatus(Request $request, $device_id)
    {
        try {
            $accessToken = $this->getAccessToken();
            $deviceData = $this->getDeviceStatus($device_id, $accessToken);

            $statusArray = $deviceData['status'] ?? [];
            $statusMap = [];
            foreach ($statusArray as $item) {
                $statusMap[$item['code']] = $item['value'];
            }
            $temperature = null;
            if (isset($statusMap['temp_current'])) {
                $temperature = (float) $statusMap['temp_current'] / 10;
            } elseif (isset($statusMap['va_temperature'])) {
                $temperature = (float) $statusMap['va_temperature'] / 10;
            }
            $humidity = null;

            if (isset($statusMap['humidity_value'])) {
                $humidity = (float) $statusMap['humidity_value'];
            } elseif (isset($statusMap['va_humidity'])) {
                $humidity = (float) $statusMap['va_humidity'];
            }
            if ($humidity !== null && $humidity > 100) {
                $humidity = $humidity / 10;
            }

            $batteryPercentage = $statusMap['battery_percentage'] ?? null;
            $tempAlarm = $statusMap['temp_alarm'] ?? null;
            $humAlarm  = $statusMap['hum_alarm'] ?? null;
            $recordedAt = isset($deviceData['update_time']) ? Carbon::createFromTimestamp($deviceData['update_time']) : now();
            $timeData = [
                'active_time' => isset($deviceData['active_time']) ? Carbon::createFromTimestamp($deviceData['active_time'])->format('Y-m-d H:i:s') : null,
                'create_time' => isset($deviceData['create_time']) ? Carbon::createFromTimestamp($deviceData['create_time'])->format('Y-m-d H:i:s') : null,
                'update_time' => isset($deviceData['update_time']) ? Carbon::createFromTimestamp($deviceData['update_time'])->format('Y-m-d H:i:s') : null,
            ];

            // $lastStatus = DeviceStatus::where('device_id', $device_id)->orderBy('id','desc')->first();
            // if ($lastStatus &&
            //     $lastStatus->temperature == $temperature &&
            //     $lastStatus->humidity == $humidity) {
            //     return returnData(3000,null,"Temperature & Humidity unchanged. Skip insert.");
            // }

            DeviceStatus::create([
                'device_id'          => $device_id,
                'temperature'        => $temperature,
                'humidity'           => $humidity,
                'battery_percentage' => $batteryPercentage,
                'temp_alarm'         => $tempAlarm,
                'hum_alarm'          => $humAlarm,
                'online'             => $deviceData['online'] ?? false,
                'recorded_at'        => json_encode($timeData),
            ]);

            Device::where('device_id', $device_id)->update([
                'online' => $deviceData['online'] ?? false
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Device status fetched and stored successfully',
                'data' => [
                    'temperature' => $temperature,
                    'humidity' => $humidity,
                    'battery_percentage' => $batteryPercentage,
                    'temp_alarm' => $tempAlarm,
                    'hum_alarm' => $humAlarm,
                    'recorded_at' => $recordedAt
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("Status fetch/store failed for device {$device_id}: " . $e->getMessage());
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 400);
        }
    }

    public function soilSensorData(Request $request)
    {
        $lastDeviceId = DB::table('soil_sensro_data')->max('device_id');

        DB::table('soil_sensro_data')->insert([
            'device_id' => $lastDeviceId ? $lastDeviceId + 1 : 1,
            'json_data' => json_encode($request->all())
        ]);
    }

    public function iotData(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return response()->json(['status' => 'error', 'message' => 'Invalid JSON'], 400);
        }

        $deviceId =  $data['id'] ?? null;
        if (!$deviceId) {
            return response()->json(['status' => 'error', 'message' => 'Invalid JSON'], 400);
        }


        $device = SoilDevice::where('device_id', $deviceId)->first();
        if (!$device) {
            $device = new SoilDevice();
            $device->device_id = $deviceId;
            $device->save();
        }

        // Insert into iot_data table
        DB::table('site_readings')->insert([
            'site_id'        => $device->id,               // device_id
            'reading_time'   => $data['time'] ?? now(),           // time
            'temperature'    => $data['temperature'] ?? 0,
            'humidity'       => $data['humidity'] ?? 0,
            'conductivity'   => $data['EC'] ?? 0,                 // EC
            'ph'             => $data['PH'] ?? 0,
            'n'              => $data['N'] ?? 0,
            'p'              => $data['P'] ?? 0,
            'k'              => $data['K'] ?? 0,
            'fertility'      => $data['battery'] ?? 0,            // map battery to fertility
            'created_at'     => now()->addHours(6),
        ]);

        return response()->json(['status' => 'success']);
    }
}

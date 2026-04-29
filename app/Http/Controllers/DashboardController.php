<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\DeviceStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\CarbonPeriod;



class DashboardController extends Controller
{
    use Helper;

    public function __construct() {}

    public function dashboardData(Request $request)
    {
        $authUser = auth()->user();

        $type = $request->input('type_id');
        $device_id = $request->input('device_id');
        $time = (int) $request->input('time_priod', 24);

        // =========================
        // INTERVAL SET (IMPORTANT)
        // =========================
        if ($time == 24) {
            $intervalHour = 1;
            $totalHours = 24;
        } elseif ($time == 3) {
            $intervalHour = 3;
            $totalHours = 72;
        } elseif ($time == 7) {
            $intervalHour = 7;
            $totalHours = 168;
        } elseif ($time == 30) {
            $intervalHour = 30;
            $totalHours = 720;
        } else {
            $intervalHour = 1;
            $totalHours = 24;
        }

        $color = ($authUser->theme && $authUser->theme == 'bg-default bg-theme2')
            ? '#000000'
            : '#FFFFFF';

        // =========================
        // DATE RANGE
        // =========================
        $end = now()->startOfHour();
        $start = $end->copy()->subHours($totalHours - 1);

        // =========================
        // LABELS + BUCKET
        // =========================
        $labels = [];
        $bucket = [];

        $period = CarbonPeriod::create($start, "{$intervalHour} hours", $end);

        foreach ($period as $dt) {
            $key = $dt->format('Y-m-d H:00:00');

            $labels[] = $dt->format('d-M (H:i)');

            $bucket[$key] = [
                'temperature_sum' => 0,
                'humidity_sum' => 0,
                'count' => 0,
            ];
        }

        // =========================
        // TYPE 1
        // =========================
        if ($type == 1) {

            $raw = DB::table('device_statuses as ds')
                ->join('devices as d', 'd.device_id', '=', 'ds.device_id')
                ->when($device_id, function ($q) use ($device_id) {
                    $q->where('ds.device_id', $device_id);
                })
                ->whereBetween('ds.created_at', [$start, $end])
                ->selectRaw("
                ds.device_id,
                d.display_name,
                DATE_FORMAT(ds.created_at, '%Y-%m-%d %H:00:00') as hour_time,
                AVG(ds.temperature) as temperature,
                AVG(ds.humidity) as humidity
            ")
                ->groupBy('ds.device_id', 'd.display_name', 'hour_time')
                ->orderBy('hour_time')
                ->get();

            $devices = [];

            foreach ($raw as $row) {

                $deviceName = $row->display_name ?? ('Device-' . $row->device_id);

                if (!isset($devices[$deviceName])) {
                    $devices[$deviceName] = [
                        'device_name' => $deviceName,
                        'dates' => $labels,
                        'temperature' => array_fill(0, count($labels), 0),
                        'humidity' => array_fill(0, count($labels), 0),
                    ];
                }

                // interval bucket match
                $dt = Carbon::parse($row->hour_time);

                $index = array_search(
                    $dt->format('d-M (H:i)'),
                    $labels
                );

                if ($index !== false) {
                    $devices[$deviceName]['temperature'][$index] = round($row->temperature, 2);
                    $devices[$deviceName]['humidity'][$index] = round($row->humidity, 2);
                }
            }

            return response()->json([
                'status' => 2000,
                'result' => $devices,
                'dates' => $labels,
                'theme_color' => $color
            ]);
        }

        // =========================
        // TYPE 2 (SOIL)
        // =========================
        if ($type == 2) {

            $raw = DB::table('site_readings')
                ->when($device_id, function ($q) use ($device_id) {
                    $q->where('site_id', $device_id);
                })
                ->whereBetween('created_at', [$start, $end])
                ->selectRaw("
                site_id,
                DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as hour_time,
                AVG(temperature) as temperature,
                AVG(humidity) as humidity,
                AVG(conductivity) as conductivity,
                AVG(ph) as ph,
                AVG(fertility) as fertility,
                AVG(n) as n,
                AVG(p) as p,
                AVG(k) as k
            ")
                ->groupBy('site_id', 'hour_time')
                ->orderBy('hour_time')
                ->get();

            $sites = DB::table('soil_devices')
                ->when($device_id, function ($q) use ($device_id) {
                    $q->where('id', $device_id);
                })
                ->get();

            $chartData = [
                'temperature' => [],
                'humidity' => [],
                'conductivity' => [],
                'ph' => [],
                'fertility' => [],
                'n' => [],
                'p' => [],
                'k' => []
            ];

            foreach ($sites as $site) {

                foreach ($labels as $i => $label) {

                    $match = null;

                    foreach ($raw as $row) {

                        $rowHour = Carbon::parse($row->hour_time)->format('d-M (H:i)');
                        $labelHour = $label;

                        if ($row->site_id == $site->id && $rowHour == $labelHour) {
                            $match = $row;
                            break;
                        }
                    }

                    $chartData['temperature'][$site->device_name][] = $match->temperature ?? 0;
                    $chartData['humidity'][$site->device_name][] = $match->humidity ?? 0;
                    $chartData['conductivity'][$site->device_name][] = $match->conductivity ?? 0;
                    $chartData['ph'][$site->device_name][] = $match->ph ?? 0;
                    $chartData['fertility'][$site->device_name][] = $match->fertility ?? 0;
                    $chartData['n'][$site->device_name][] = $match->n ?? 0;
                    $chartData['p'][$site->device_name][] = $match->p ?? 0;
                    $chartData['k'][$site->device_name][] = $match->k ?? 0;
                }
            }

            return response()->json([
                'status' => 2000,
                'result' => [
                    'dates' => $labels,
                    'chartData' => $chartData
                ],
                'theme_color' => $color
            ]);
        }

        return response()->json([
            'status' => 4000,
            'message' => 'Invalid type'
        ]);
    }
    public function uploadExcell() {}

    public function submitUploadExcell(Request $request)
    {
        try {
            $excelData = $request->input('excelData');
            if (!$excelData || count($excelData) < 2) {
                return returnData(5000, null, 'Excel data is empty');
            }

            $rows = array_slice($excelData, 1);

            foreach ($rows as $row) {
                if (count($row) < 2) continue;

                $rowData = [
                    'description'  => $row[0] ?? null,
                    'time'         => $row[1] ?? null,
                    'temperature'  => $row[2] ?? 0,
                    'humidity'     => $row[3] ?? 0,
                    'conductivity' => $row[4] ?? 0,
                    'ph'           => $row[5] ?? 0,
                    'n'            => $row[6] ?? 0,
                    'p'            => $row[7] ?? 0,
                    'k'            => $row[8] ?? 0,
                    'fertility'    => $row[9] ?? 0,
                ];

                if (!$rowData['description'] || !$rowData['time']) continue;

                DB::table('sites')->updateOrInsert(
                    ['name' => $rowData['description']],
                    ['updated_at' => now()]
                );

                $deviceId = DB::table('sites')->where('name', $rowData['description'])->value('id');
                if (!$deviceId) continue;

                DB::table('site_readings')->updateOrInsert(
                    [
                        'site_id'      => $deviceId,
                        'reading_time' => $rowData['time'],
                    ],
                    [
                        'temperature'  => $rowData['temperature'],
                        'humidity'     => $rowData['humidity'],
                        'conductivity' => $rowData['conductivity'],
                        'ph'           => $rowData['ph'],
                        'n'            => $rowData['n'],
                        'p'            => $rowData['p'],
                        'k'            => $rowData['k'],
                        'fertility'    => $rowData['fertility'],
                        'created_at'   => now(),
                    ]
                );
            }

            return returnData(2000, null, 'Excel data uploaded successfully');
        } catch (\Exception $e) {
            Log::error('Excel upload failed: ' . $e->getMessage());
            return returnData(5000, null, $e->getMessage());
        }
    }


    public function dashboardDataV2(Request $request)
    {
        $siteId = $request->device_id;
        $mode = $request->input('mode', 'live');

        $selectedDate = $request->date_from;
        $baseDate = $selectedDate
            ? \Carbon\Carbon::parse($selectedDate)
            : now();

        $result = [];
        $latest = null;

        // =========================
        // LIVE MODE
        // =========================
        if ($mode === 'live') {

            $days = [
                '6 days ago' => $baseDate->copy()->subDays(6)->toDateString(),
                '4 days ago' => $baseDate->copy()->subDays(4)->toDateString(),
                '2 days ago' => $baseDate->copy()->subDays(2)->toDateString(),
                'Today' => $baseDate->toDateString(),
            ];

            foreach ($days as $label => $date) {

                $row = DB::table('site_readings')
                    ->where('site_id', $siteId)
                    ->whereDate('created_at', $date)
                    ->orderBy('created_at', 'desc')
                    ->first();

                $result[] = [
                    'label' => $label,
                    'temperature' => $row->temperature ?? 0,
                    'humidity' => $row->humidity ?? 0,
                    'ph' => $row->ph ?? 0,
                    'ec' => $row->conductivity ?? 0,

                ];
                if ($label === "Today") {
                    $latest = $row;
                }
            }
        }

        // =========================
        // 24 HOURS MODE
        // =========================
        if ($mode === '24h') {

            $latest = DB::table('site_readings')
                ->where('site_id', $siteId)
                ->where('created_at', '>=', now()->subHours(24))
                ->selectRaw("
                ROUND(AVG(ph), 2) as ph,
                ROUND(AVG(temperature), 2) as temperature,
                ROUND(AVG(humidity), 2) as humidity,
                ROUND(AVG(n), 2) as n,
                ROUND(AVG(p), 2) as p,
                ROUND(AVG(k), 2) as k,
                ROUND(AVG(conductivity), 2) as ec,
                ROUND(AVG(fertility), 2) as fertility
            ")
                ->first();

            $result = DB::table('site_readings')
                ->where('site_id', $siteId)
                ->where('created_at', '>=', now()->subHours(24))
                ->selectRaw("
                DATE_FORMAT(created_at, '%H:00') as label,
                ROUND(AVG(temperature), 2) as temperature,
                ROUND(AVG(humidity), 2) as humidity,
                ROUND(AVG(ph), 2) as ph
            ")
                ->groupBy(DB::raw("DATE_FORMAT(created_at, '%H:00')"))
                ->orderBy('label')
                ->get();
        }

        // =========================
        // SAFE FALLBACK
        // =========================
        if (!$latest) {
            $latest = (object)[
                'ph' => 0,
                'temperature' => 0,
                'humidity' => 0,
                'n' => 0,
                'p' => 0,
                'k' => 0,
                'ec' => 0,
                'fertility' => 0,
            ];
        }

        // =========================
        // SENSOR MAP
        // =========================
        $sensorMap = [
            5 => 'N',
            6 => 'P',
            7 => 'K',
            8 => 'EC',
            9 => 'PH',
            1 => 'TEMPERATURE',
            11 => 'HUMIDITY',
            12 => 'FERTILITY',
        ];

        $latestValues = [
            'PH' => $latest->ph ?? 0,
            'TEMPERATURE' => $latest->temperature ?? 0,
            'HUMIDITY' => $latest->humidity ?? 0,
            'N' => $latest->n ?? 0,
            'P' => $latest->p ?? 0,
            'K' => $latest->k ?? 0,
            'EC' => $latest->ec ?? 0,
            'FERTILITY' => $latest->fertility ?? 0,
        ];

        // =========================
        // THRESHOLDS
        // =========================
        $thresholds = DB::table('device_thresholds')
            ->where('device_category_id', 2)
            ->get()
            ->keyBy('sensor_id');

        $sensors = [];
        $alerts = [];
        $farmActions = [];
        $score = 100;

        foreach ($sensorMap as $id => $name) {

            $value = $latestValues[$name] ?? 0;
            $threshold = $thresholds[$id] ?? null;

            $status = 'OK';

            if ($threshold) {

                if ($value < $threshold->min_value) {
                    $status = 'LOW';
                    $score -= 10;

                    if ($threshold->min_alert) $alerts[] = $threshold->min_alert;
                    if ($threshold->min_action) $farmActions[] = $threshold->min_action;
                }

                if ($value > $threshold->max_value) {
                    $status = 'HIGH';
                    $score -= 10;

                    if ($threshold->max_alert) $alerts[] = $threshold->max_alert;
                    if ($threshold->max_action) $farmActions[] = $threshold->max_action;
                }
            }

            $sensors[] = [
                'id' => $id,
                'name' => $name,
                'value' => round($value, 2), // 🔥 clean output
                'status' => $status
            ];
        }

        // =========================
        // RESPONSE
        // =========================
        return response()->json([
            'status' => 2000,
            'chartData' => $result,
            'sensors' => $sensors,
            'farmHealth' => [
                'score' => $score,
                'alerts' => $alerts,
                'actions' => $farmActions,
            ],
            'fetched_at' => now()->format('Y-m-d H:i:s')
        ]);
    }


    public function storageData(Request $request)
    {
        $deviceId = $request->device_id;


        $latest = DB::table('device_statuses')
            ->where('device_id', $deviceId)
            ->orderBy('created_at', 'desc')
            ->first();

        $thresholds = DB::table('device_thresholds')
            ->where('device_category_id', 1)
            ->get()
            ->keyBy('sensor_id');

        $moisture = $thresholds[2] ?? null;
        $temperature = $thresholds[1] ?? null;

        if ($moisture) {
            if ($latest->humidity < $moisture->min_value) {
                $actions[] = "Soil moisture is LOW → Start irrigation immediately";
            } elseif ($latest->humidity > $moisture->max_value) {
                $actions[] = "Soil moisture is HIGH → Improve drainage / ventilation";
            }
        }

        // Temperature
        if ($temperature) {
            if ($latest->temperature < $temperature->min_value) {
                $actions[] = "Temperature LOW → Increase greenhouse heating";
            } elseif ($latest->temperature > $temperature->max_value) {
                $actions[] = "Temperature HIGH → Turn on cooling system";
            }
        }
        if ($latest->battery_percentage < 20) {
            $actions[] = "Battery LOW → Please charge the device";
        } elseif ($latest->battery_percentage > 90) {
            $actions[] = "Battery HIGH → Normal condition";
        }

        return response()->json([
            'status' => 2000,
            'result' => [
                'latest' => $latest,
                'actions' => $actions,
            ]
        ]);
    }
}

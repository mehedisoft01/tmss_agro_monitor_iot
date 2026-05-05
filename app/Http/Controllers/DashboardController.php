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

//    public function dashboardData(Request $request)
//    {
//        $authUser = auth()->user();
//
//        $type = $request->input('type_id');
//        $device_id = $request->input('device_id');
//        $time = (int) $request->input('time_priod', 23);
//
//        // =========================
//        // INTERVAL SET
//        // =========================
//        if ($time == 24) {
//            $intervalHour = 1;
//            $totalHours = 24;
//        } elseif ($time == 3) {
//            $intervalHour = 3;
//            $totalHours = 72;
//        } elseif ($time == 7) {
//            $intervalHour = 7;
//            $totalHours = 168;
//        } elseif ($time == 30) {
//            $intervalHour = 30;
//            $totalHours = 720;
//        } else {
//            $intervalHour = 1;
//            $totalHours = 24;
//        }
//
//        $color = ($authUser->theme && $authUser->theme == 'bg-default bg-theme2')
//            ? '#000000'
//            : '#FFFFFF';
//
//        // =========================
//        // DATE RANGE (REALTIME)
//        // =========================
//        $end = now();
//        $start = $end->copy()->subHours($totalHours - 1);
//
//        // =========================
//        // TYPE 1
//        // =========================
//        if ($type == 1) {
//
//            $raw = DB::table('device_statuses as ds')
//                ->join('devices as d', 'd.device_id', '=', 'ds.device_id')
//                ->when($device_id, function ($q) use ($device_id) {
//                    $q->where('ds.device_id', $device_id);
//                })
//                ->whereBetween('ds.created_at', [$start, $end])
//                ->selectRaw("
//                ds.device_id,
//                d.display_name,
//
//                FROM_UNIXTIME(
//                    FLOOR(UNIX_TIMESTAMP(ds.created_at) / (3600 * {$intervalHour}))
//                    * (3600 * {$intervalHour})
//                ) as bucket_time,
//
//                AVG(ds.temperature) as temperature,
//                AVG(ds.humidity) as humidity
//            ")
//                ->groupBy('ds.device_id', 'd.display_name', 'bucket_time')
//                ->orderBy('bucket_time')
//                ->get();
//
//            $devices = [];
//            $labels = [];
//            $labelIndex = [];
//
//            // 👉 LABEL BUILD
//            foreach ($raw as $row) {
//                $key = $row->bucket_time;
//
//                if (!isset($labelIndex[$key])) {
//                    $labelIndex[$key] = count($labels);
//                    $labels[] = \Carbon\Carbon::parse($key)->format('d-M H:i');
//                }
//            }
//
//            // 👉 INIT DEVICE ARRAY
//            foreach ($raw as $row) {
//                $name = $row->display_name ?? ('Device-' . $row->device_id);
//
//                if (!isset($devices[$name])) {
//                    $devices[$name] = [
//                        'device_name' => $name,
//                        'temperature' => array_fill(0, count($labels), 0),
//                        'humidity' => array_fill(0, count($labels), 0),
//                    ];
//                }
//            }
//
//            // 👉 FILL DATA
//            foreach ($raw as $row) {
//                $name = $row->display_name ?? ('Device-' . $row->device_id);
//                $key = $row->bucket_time;
//
//                if (!isset($labelIndex[$key])) continue;
//
//                $i = $labelIndex[$key];
//
//                $devices[$name]['temperature'][$i] = round($row->temperature, 2);
//                $devices[$name]['humidity'][$i] = round($row->humidity, 2);
//            }
//
//            return response()->json([
//                'status' => 2000,
//                'result' => $devices,
//                'dates' => $labels,
//                'theme_color' => $color
//            ]);
//        }
//
//        // =========================
//        // TYPE 2
//        // =========================
//        if ($type == 2) {
//
//            $raw = DB::table('site_readings')
//                ->when($device_id, function ($q) use ($device_id) {
//                    $q->where('site_id', $device_id);
//                })
//                ->whereBetween('created_at', [$start, $end])
//                ->selectRaw("
//                site_id,
//
//                FROM_UNIXTIME(
//                    FLOOR(UNIX_TIMESTAMP(created_at) / (3600 * {$intervalHour}))
//                    * (3600 * {$intervalHour})
//                ) as bucket_time,
//
//                AVG(temperature) as temperature,
//                AVG(humidity) as humidity,
//                AVG(conductivity) as conductivity,
//                AVG(ph) as ph,
//                AVG(fertility) as fertility,
//                AVG(n) as n,
//                AVG(p) as p,
//                AVG(k) as k
//            ")
//                ->groupBy('site_id', 'bucket_time')
//                ->orderBy('bucket_time')
//                ->get();
//
//            $sites = DB::table('soil_devices')
//                ->when($device_id, function ($q) use ($device_id) {
//                    $q->where('id', $device_id);
//                })
//                ->get();
//
//            $chartData = [];
//            $labels = [];
//            $labelIndex = [];
//
//            // 👉 LABEL BUILD
//            foreach ($raw as $row) {
//                $key = $row->bucket_time;
//
//                if (!isset($labelIndex[$key])) {
//                    $labelIndex[$key] = count($labels);
//                    $labels[] = \Carbon\Carbon::parse($key)->format('d-M H:i');
//                }
//            }
//
//            // 👉 INIT
//            foreach ($sites as $site) {
//
//                $name = $site->device_name;
//
//                $chartData['temperature'][$name] = array_fill(0, count($labels), 0);
//                $chartData['humidity'][$name] = array_fill(0, count($labels), 0);
//                $chartData['conductivity'][$name] = array_fill(0, count($labels), 0);
//                $chartData['ph'][$name] = array_fill(0, count($labels), 0);
//                $chartData['fertility'][$name] = array_fill(0, count($labels), 0);
//                $chartData['n'][$name] = array_fill(0, count($labels), 0);
//                $chartData['p'][$name] = array_fill(0, count($labels), 0);
//                $chartData['k'][$name] = array_fill(0, count($labels), 0);
//            }
//
//            // 👉 FILL DATA
//            foreach ($raw as $row) {
//
//                $site = $sites->firstWhere('id', $row->site_id);
//                if (!$site) continue;
//
//                $name = $site->device_name;
//                $key = $row->bucket_time;
//
//                if (!isset($labelIndex[$key])) continue;
//
//                $i = $labelIndex[$key];
//
//                $chartData['temperature'][$name][$i] = round($row->temperature, 2);
//                $chartData['humidity'][$name][$i] = round($row->humidity, 2);
//                $chartData['conductivity'][$name][$i] = round($row->conductivity, 2);
//                $chartData['ph'][$name][$i] = round($row->ph, 2);
//                $chartData['fertility'][$name][$i] = round($row->fertility, 2);
//                $chartData['n'][$name][$i] = round($row->n, 2);
//                $chartData['p'][$name][$i] = round($row->p, 2);
//                $chartData['k'][$name][$i] = round($row->k, 2);
//            }
//
//            return response()->json([
//                'status' => 2000,
//                'result' => [
//                    'dates' => $labels,
//                    'chartData' => $chartData
//                ],
//                'theme_color' => $color
//            ]);
//        }
//
//        return response()->json([
//            'status' => 4000,
//            'message' => 'Invalid type'
//        ]);
//    }

    public function dashboardData(Request $request)
    {
        $authUser = auth()->user();

        $type = $request->input('type_id');
        $device_id = $request->input('device_id');
        $time = (int) $request->input('time_priod', 24);

        // =========================
        // INTERVAL SET
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
        // RANGE
        // =========================
        $end = now();
        $start = $end->copy()->subHours($totalHours - 1);

        // =========================
        // FIXED LABELS (ALL HOURS INCLUDED)
        // =========================
        $labels = [];
        $labelIndex = [];

        $period = CarbonPeriod::create($start, "{$intervalHour} hours", $end);

        foreach ($period as $dt) {
            $key = $dt->format('Y-m-d H:00:00');

            $labelIndex[$key] = count($labels);
            $labels[] = $dt->format('d-M H:00');
        }

        // ==========================================================
        // TYPE 1
        // ==========================================================
        if ($type == 1) {

            $raw = DB::table('device_statuses_report as ds')
                ->join('devices as d', 'd.device_id', '=', 'ds.device_id')
                ->when($device_id, function ($q) use ($device_id) {
                    $q->where('ds.device_id', $device_id);
                })
                ->whereBetween('ds.created_at', [$start, $end])
                ->selectRaw("
                ds.device_id,
                d.display_name,

                DATE_FORMAT(ds.created_at, '%Y-%m-%d %H:00:00') as bucket_time,

                AVG(ds.temperature) as temperature,
                AVG(ds.humidity) as humidity
            ")
                ->groupBy('ds.device_id', 'd.display_name', 'bucket_time')
                ->orderBy('bucket_time')
                ->get();

            $devices = [];

            foreach ($raw as $row) {

                $name = $row->display_name ?? ('Device-' . $row->device_id);

                if (!isset($devices[$name])) {
                    $devices[$name] = [
                        'device_name' => $name,
                        'temperature' => array_fill(0, count($labels), 0),
                        'humidity' => array_fill(0, count($labels), 0),
                    ];
                }

                $key = Carbon::parse($row->bucket_time)->format('Y-m-d H:00:00');

                if (!isset($labelIndex[$key])) continue;

                $i = $labelIndex[$key];

                $devices[$name]['temperature'][$i] = round($row->temperature ?? 0, 2);
                $devices[$name]['humidity'][$i] = round($row->humidity ?? 0, 2);
            }

            return response()->json([
                'status' => 2000,
                'result' => $devices,
                'dates' => $labels,
                'theme_color' => $color
            ]);
        }

        // ==========================================================
        // TYPE 2
        // ==========================================================
        if ($type == 2) {

            $raw = DB::table('site_readings_report')
                ->when($device_id, function ($q) use ($device_id) {
                    $q->where('site_id', $device_id);
                })
                ->whereBetween('created_at', [$start, $end])
                ->selectRaw("
                site_id,

                DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as bucket_time,

                AVG(temperature) as temperature,
                AVG(humidity) as humidity,
                AVG(conductivity) as conductivity,
                AVG(ph) as ph,
                AVG(fertility) as fertility,
                AVG(n) as n,
                AVG(p) as p,
                AVG(k) as k
            ")
                ->groupBy('site_id', 'bucket_time')
                ->orderBy('bucket_time')
                ->get();

            $sites = DB::table('soil_devices')
                ->when($request->farmer_type, function ($q) use ($request) {
                    $q->where('farmer_type', $request->farmer_type);
                })
                ->when($device_id, function ($q) use ($device_id) {
                    $q->where('id', $device_id);
                })
                ->get();

            $chartData = [];

            foreach ($sites as $site) {

                $name = $site->device_name;

                $chartData['temperature'][$name] = array_fill(0, count($labels), 0);
                $chartData['humidity'][$name] = array_fill(0, count($labels), 0);
                $chartData['conductivity'][$name] = array_fill(0, count($labels), 0);
                $chartData['ph'][$name] = array_fill(0, count($labels), 0);
                $chartData['fertility'][$name] = array_fill(0, count($labels), 0);
                $chartData['n'][$name] = array_fill(0, count($labels), 0);
                $chartData['p'][$name] = array_fill(0, count($labels), 0);
                $chartData['k'][$name] = array_fill(0, count($labels), 0);
            }

            foreach ($raw as $row) {

                $site = $sites->firstWhere('id', $row->site_id);
                if (!$site) continue;

                $name = $site->device_name;

                $key = Carbon::parse($row->bucket_time)->format('Y-m-d H:00:00');

                if (!isset($labelIndex[$key])) continue;

                $i = $labelIndex[$key];

                $chartData['temperature'][$name][$i] = round($row->temperature ?? 0, 2);
                $chartData['humidity'][$name][$i] = round($row->humidity ?? 0, 2);
                $chartData['conductivity'][$name][$i] = round($row->conductivity ?? 0, 2);
                $chartData['ph'][$name][$i] = round($row->ph ?? 0, 2);
                $chartData['fertility'][$name][$i] = round($row->fertility ?? 0, 2);
                $chartData['n'][$name][$i] = round($row->n ?? 0, 2);
                $chartData['p'][$name][$i] = round($row->p ?? 0, 2);
                $chartData['k'][$name][$i] = round($row->k ?? 0, 2);
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
        $from = $request->date_from;
        $authUser = auth()->user();

        $siteId = $request->device_id;
        $mode = $request->input('mode', 'live');

        $color = ($authUser->theme && $authUser->theme == 'bg-default bg-theme2')
            ? '#000000'
            : '#FFFFFF';

        $selectedDate = $request->date_from;
        $baseDate = $selectedDate
            ? \Carbon\Carbon::parse($selectedDate)
            : now();

        $result = [];
        $latest = null;

        // =========================
        // 🔥 GET DEVICE FARMER TYPE
        // =========================
        $device = DB::table('soil_devices')
            ->where('id', $siteId)
            ->first();

        $farmerType = $device->farmer_type ?? null;

        // =========================
        // LIVE MODE
        // =========================
        if ($mode === 'live') {

            $latest = DB::table('site_readings_report')
                ->where('site_id', $siteId)
                ->orderBy('created_at', 'desc')
                ->select(
                    'ph',
                    'temperature',
                    'humidity',
                    'n',
                    'p',
                    'k',
                    'fertility',
                    DB::raw('conductivity as ec')
                )
                ->first();

            $days = [
                '6 days ago' => $baseDate->copy()->subDays(6)->toDateString(),
                '4 days ago' => $baseDate->copy()->subDays(4)->toDateString(),
                '2 days ago' => $baseDate->copy()->subDays(2)->toDateString(),
                'Today' => $baseDate->toDateString(),
            ];

            foreach ($days as $label => $date) {

                $row = DB::table('site_readings_report')
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
            }
        }

        // =========================
        // 24 HOURS MODE
        // =========================
        if ($mode === '24h') {

            $from = $request->date_from
                ? \Carbon\Carbon::parse($request->date_from)->startOfDay()
                : now()->subHours(24);

            $to = (clone $from)->addHours(24);

            $latest = DB::table('site_readings_report')
                ->where('site_id', $siteId)
                ->whereBetween('created_at', [$from, $to])
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

            $result = DB::table('site_readings_report')
                ->where('site_id', $siteId)
                ->whereBetween('created_at', [$from, $to])
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
            10 => 'TEMPERATURE',
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
        // 🔥 THRESHOLDS (FIXED)
        // =========================
        $thresholdsRaw = DB::table('device_thresholds')
            ->where('device_category_id', 2)
            ->where(function ($q) use ($farmerType) {
                $q->where('farmer_type', $farmerType)
                    ->orWhereNull('farmer_type'); // fallback
            })
            ->orderByRaw('farmer_type IS NULL') // prioritize specific
            ->get();

        $thresholds = $thresholdsRaw->unique('sensor_id')->keyBy('sensor_id');

        // =========================
        // SENSOR ANALYSIS
        // =========================
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
                'value' => round($value, 2),
                'status' => $status
            ];
        }

        // =========================
        // REMOVE DUPLICATES
        // =========================
        $alerts = array_values(array_unique($alerts));
        $farmActions = array_values(array_unique($farmActions));

        // =========================
        // RESPONSE
        // =========================
        return response()->json([
            'status' => 2000,
            'chartData' => $result,
            'sensors' => $sensors,
            'farmHealth' => [
                'score' => max($score, 0),
                'alerts' => $alerts,
                'actions' => $farmActions,
            ],
            'fetched_at' => now()->format('Y-m-d H:i:s'),
            'theme_color' => $color
        ]);
    }

    public function storageData(Request $request)
    {
        $deviceId = $request->device_id;


        $latest = DB::table('device_statuses_report')
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

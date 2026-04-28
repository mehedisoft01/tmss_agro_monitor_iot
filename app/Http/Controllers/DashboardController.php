<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\DeviceStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    use Helper;

    public function __construct() {}

    //    public function dashboardData(Request $request)
    //    {
    //        $deviceId    = $request->input('option');
    //        $dateFrom  = $request->input('date_from');
    //        $dateTo    = $request->input('date_to');
    //
    //        $dateFrom = $dateFrom ? Carbon::parse($dateFrom) : null;
    //        $dateTo   = $dateTo ? Carbon::parse($dateTo) : null;
    //
    //        if (!$dateFrom && !$dateTo) {
    //            $dateTo = Carbon::now();
    //            $dateFrom = $dateTo->copy()->subDays(6);
    //        } elseif ($dateFrom && !$dateTo) {
    //            $dateTo = Carbon::now();
    //        } elseif (!$dateFrom && $dateTo) {
    //            $dateFrom = $dateTo->copy()->subDays(6);
    //        }
    //
    //        $diffDays = $dateFrom->diffInDays($dateTo) + 1;
    //        if ($diffDays > 7) {
    //            return returnData(4000, [],"You can only search a maximum of 7 days at a time.");
    //        }
    //
    //        $dates = DB::table('site_readings')
    //            ->when($deviceId, function($q) use ($deviceId){
    //                $q->where('site_id', $deviceId);
    //            })
    //            ->whereBetween('reading_time', [$dateFrom, $dateTo])
    //            ->selectRaw('DATE(reading_time) as date')
    //            ->groupBy('date')
    //            ->orderBy('date')
    //            ->pluck('date')
    //            ->toArray();
    //        $sitesQuery = DB::table('sites');
    //        if ($deviceId) $sitesQuery->where('id', $deviceId);
    //        $sites = $sitesQuery->get();
    //
    //        // Initialize chart data
    //        $chartData = [
    //            'temperature'   => [],
    //            'humidity'      => [],
    //            'conductivity'  => [],
    //            'ph'            => [],
    //            'fertility'     => [],
    //            'n'             => [],
    //            'p'             => [],
    //            'k'             => []
    //        ];
    //
    //        // Collect readings per site
    //        foreach ($sites as $site) {
    //            $temp = $hum = $conductivity = $ph = $fertility = $n = $p = $k = [];
    //
    //            foreach ($dates as $date) {
    //                $row = DB::table('site_readings')
    //                    ->where('site_id', $site->id)
    //                    ->whereDate('reading_time', $date)
    //                    ->first();
    //
    //                $temp[]          = $row ? round($row->temperature, 2) : 0;
    //                $hum[]           = $row ? (int)$row->humidity : 0;
    //                $conductivity[]  = $row ? (int)$row->conductivity : 0;
    //                $ph[]            = $row ? round($row->ph, 1) : 0;
    //                $fertility[]     = $row ? (int)$row->fertility : 0;
    //                $n[]             = $row ? (int)$row->n : 0;
    //                $p[]             = $row ? (int)$row->p : 0;
    //                $k[]             = $row ? (int)$row->k : 0;
    //            }
    //
    //            $chartData['temperature'][$site->name]   = $temp;
    //            $chartData['humidity'][$site->name]      = $hum;
    //            $chartData['conductivity'][$site->name]  = $conductivity;
    //            $chartData['ph'][$site->name]            = $ph;
    //            $chartData['fertility'][$site->name]     = $fertility;
    //            $chartData['n'][$site->name]             = $n;
    //            $chartData['p'][$site->name]             = $p;
    //            $chartData['k'][$site->name]             = $k;
    //        }
    //
    //        return returnData(2000, [
    //            'dates'     => $dates,
    //            'chartData' => $chartData
    //        ]);
    //    }


    //    public function dashboardData(Request $request)
    //    {
    //        $limit = $request->input('limit', 10);
    //
    //        // Step 1: Base query
    //        $subQuery = DeviceStatus::query();
    //
    //        // ✅ Device filter
    //        if ($request->option) {
    //            $subQuery->where('device_id', $request->option);
    //        }
    //
    //        // ✅ Date filter
    //        if ($request->date_from) {
    //            $subQuery->whereDate('created_at', '>=', $request->date_from);
    //        }
    //
    //        if ($request->date_to) {
    //            $subQuery->whereDate('created_at', '<=', $request->date_to);
    //        }
    //
    //        // Step 2: Apply ROW_NUMBER per device
    //        $subQuery->select('id')->selectRaw("ROW_NUMBER() OVER (PARTITION BY device_id ORDER BY created_at DESC) as rn");
    //
    //        // Step 3: Get filtered IDs
    //        $filteredIds = DB::table(DB::raw("({$subQuery->toSql()}) as t"))
    //            ->mergeBindings($subQuery->getQuery())
    //            ->where('rn', '<=', $limit)
    //            ->pluck('id');
    //
    //        // Step 4: Fetch final data
    //        $data = DeviceStatus::with('device')
    //            ->whereIn('id', $filteredIds)
    //            ->orderBy('device_id', 'asc')
    //            ->get();
    //
    //        // Step 5: Group by device
    //        $devicesData = [];
    //
    //        foreach ($data as $row) {
    //            $deviceName = $row->device->display_name ?? 'Device';
    //            $time = \Carbon\Carbon::parse($row->created_at)->format('H:i');
    //
    //            if (!isset($devicesData[$deviceName])) {
    //                $devicesData[$deviceName] = [
    //                    'dates' => [],
    //                    'temperature' => [],
    //                    'humidity' => []
    //                ];
    //            }
    //
    //            $devicesData[$deviceName]['dates'][] = $time;
    //            $devicesData[$deviceName]['temperature'][] = $row->temperature;
    //            $devicesData[$deviceName]['humidity'][] = $row->humidity;
    //        }
    //
    //        return response()->json([
    //            'status' => 2000,
    //            'result' => $devicesData
    //        ]);
    //    }
    public function dashboardData(Request $request)
    {
        $authUser = auth()->user();
        $type = $request->input('type_id');
        $device_id = $request->input('device_id');
        $limit = $request->input('limit', 10);
        $color = ($authUser->theme && $authUser->theme == 'bg-default bg-theme2') ? '#000000' : '#FFFFFF';
        // =========================
        // ✅ TYPE = 1 (DeviceStatus)
        // =========================

        if ($type == 1) {

            $subQuery = DeviceStatus::query();
//            $subQuery->where('created_at', '>=', now()->subHours(24));

            if ($request->option) {
                $subQuery->where('device_id', $request->option);
            }

            if ($request->date_from) {
                $subQuery->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->date_to) {
                $subQuery->whereDate('created_at', '<=', $request->date_to);
            }

            $subQuery->select('id')
                ->selectRaw("ROW_NUMBER() OVER (PARTITION BY device_id ORDER BY created_at DESC) as rn");

            $filteredIds = DB::table(DB::raw("({$subQuery->toSql()}) as t"))
                ->mergeBindings($subQuery->getQuery())
                ->where('rn', '<=', $limit)
                ->pluck('id');

            $data = DeviceStatus::with('device')
                ->whereIn('id', $filteredIds)
                ->orderBy('device_id', 'asc')
                ->when($device_id, function ($query) use ($device_id) {
                    $query->where('device_id', $device_id);
                })
                ->get();

            $devicesData = [];

            foreach ($data as $row) {
                $deviceName = $row->device->display_name ?? 'Device';
                $time = \Carbon\Carbon::parse($row->created_at)->format('Y-m-d (H:i)');

                if (!isset($devicesData[$deviceName])) {
                    $devicesData[$deviceName] = [
                        'dates' => [],
                        'temperature' => [],
                        'humidity' => []
                    ];
                }

                $devicesData[$deviceName]['dates'][] = $time;
                $devicesData[$deviceName]['temperature'][] = $row->temperature;
                $devicesData[$deviceName]['humidity'][] = $row->humidity;
            }

            return response()->json([
                'status' => 2000,
                'result' => $devicesData,
                'theme_color' => $color

            ]);
        }

        // =========================
        // ✅ TYPE = 2 (Site Readings - old logic)
        // =========================

        if ($type == 2) {

            $deviceId = $request->input('option');

            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');

            $dateFrom = $dateFrom ? \Carbon\Carbon::parse($dateFrom) : null;
            $dateTo = $dateTo ? \Carbon\Carbon::parse($dateTo) : null;

            // =========================
            // ✅ CASE 1: If date range given
            // =========================

            if ($dateFrom && $dateTo) {

                $dates = DB::table('site_readings')
                    ->when($deviceId, function ($q) use ($deviceId) {
                        $q->where('site_id', $deviceId);
                    })
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->selectRaw('DATE(created_at) as date')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->pluck('date')
                    ->toArray();
            } else {
                // =========================
                // ✅ CASE 2: Default → last 7 records (not days)
                // =========================

                $dates = DB::table('site_readings')
                    ->when($deviceId, function ($q) use ($deviceId) {
                        $q->where('site_id', $deviceId);
                    })
                    ->selectRaw("
        MIN(created_at) as created_at,
        DATE_FORMAT(created_at, '%Y-%m-%d %H:') as hour_part,
        FLOOR(MINUTE(created_at) / 30) as half_hour
    ")
                    ->groupBy('hour_part', 'half_hour')
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get()
                    ->pluck('created_at')
                    ->map(function ($time) {
                        return \Carbon\Carbon::parse($time)->format('Y-m-d (H:i)');
                    })
                    ->toArray();
            }

            // =========================
            // ✅ Devices (sites)
            // =========================
            //            $sitesQuery = DB::table('soil_devices')
            //                ->whereExists(function ($query) use ($deviceId) {
            //                    $query->select(DB::raw(1))
            //                        ->from('site_readings')
            //                        ->when($deviceId, function ($q) use ($deviceId) {
            //                            $q->where('site_readings.site_id', $deviceId);
            //                        });
            //                });
            //
            //
            //            if ($deviceId) {
            //                $sitesQuery->where('id', $deviceId);
            //            }
            //
            //            $sites = $sitesQuery->get();

            $deviceId = $request->device_id ?? $request->option;

            $sitesQuery = DB::table('soil_devices')
                ->when($request->farmer_type, function ($q) use ($request) {
                    $q->where('farmer_type', $request->farmer_type);
                })
                ->when($deviceId, function ($query) use ($deviceId) {
                    $query->where('id', $deviceId);
                })
                ->whereExists(function ($query) use ($deviceId) {
                    $query->select(DB::raw(1))
                        ->from('site_readings')
                        ->when($deviceId, function ($q) use ($deviceId) {
                            $q->where('site_readings.site_id', $deviceId);
                        });
                });

            $sites = $sitesQuery->get();

            // =========================
            // ✅ Chart Data
            // =========================
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

            // =========================
            // ✅ Build chart
            // =========================
            foreach ($sites as $site) {

                $temp = $hum = $conductivity = $ph = $fertility = $n = $p = $k = [];

                foreach ($dates as $date) {

                    $row = DB::table('site_readings')
                        ->where('site_id', $site->id) // ✅ FIXED
                        ->whereDate('created_at', $date)
                        ->first();

                    $temp[] = $row ? round($row->temperature, 2) : 0;
                    $hum[] = $row ? (int)$row->humidity : 0;
                    $conductivity[] = $row ? (int)$row->conductivity : 0;
                    $ph[] = $row ? round($row->ph, 1) : 0;
                    $fertility[] = $row ? (int)$row->fertility : 0;
                    $n[] = $row ? (int)$row->n : 0;
                    $p[] = $row ? (int)$row->p : 0;
                    $k[] = $row ? (int)$row->k : 0;
                }

                $chartData['temperature'][$site->device_name] = $temp;
                $chartData['humidity'][$site->device_name] = $hum;
                $chartData['conductivity'][$site->device_name] = $conductivity;
                $chartData['ph'][$site->device_name] = $ph;
                $chartData['fertility'][$site->device_name] = $fertility;
                $chartData['n'][$site->device_name] = $n;
                $chartData['p'][$site->device_name] = $p;
                $chartData['k'][$site->device_name] = $k;
            }

            return response()->json([
                'status' => 2000,
                'result' => [
                    'dates' => $dates,
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
        $date = $request->date_from;

        $selectedDate = $request->date_from;
        $baseDate = $selectedDate
            ? \Carbon\Carbon::parse($selectedDate)
            : now();

        $days = [
            '6 days ago' => $baseDate->copy()->subDays(6)->toDateString(),
            '4 days ago' => $baseDate->copy()->subDays(4)->toDateString(),
            '2 days ago' => $baseDate->copy()->subDays(2)->toDateString(),
            'Today' => $baseDate->toDateString(),
        ];

        $result = [];
        $latest = null;

        // =========================
        // STEP 2: BUILD CHART DATA
        // =========================
        foreach ($days as $label => $date) {

            $row = DB::table('site_readings')
                ->where('site_id', $siteId)
                ->whereDate('created_at', $date)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$row) {
                $result[] = [
                    'label' => $label,
                    'temperature' => 0,
                    'humidity' => 0,
                    'ph' => 0
                ];
                continue;
            }

            $result[] = [
                'label' => $label,
                'temperature' => $row->temperature,
                'humidity' => $row->humidity,
                'ph' => $row->ph
            ];

            if ($label == "Today") {
                $latest = $row;
            }
        }

        // =========================
        // STEP 4: LEFT SIDE SENSOR ENGINE
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

        $thresholds = DB::table('device_thresholds')
            ->where('device_category_id', 2)
            ->get()
            ->keyBy('sensor_id');

        $sensors = [];

        foreach ($sensorMap as $id => $name) {

            $value = $latestValues[$name] ?? 0;
            $threshold = $thresholds[$id] ?? null;

            $status = 'OK';

            if ($threshold) {
                if ($value < $threshold->min_value) {
                    $status = 'LOW';
                } elseif ($value > $threshold->max_value) {
                    $status = 'HIGH';
                }
            }

            $sensors[] = [
                'id' => $id,
                'name' => $name,
                'value' => $value,
                'status' => $status
            ];
        }

        // =========================
        // STEP 5: FARM HEALTH SCORE + ALERTS
        // =========================
        $hasData = false;
        $alerts = [];
        $farmActions = [];
        $score = 100;

        foreach ($sensors as $s) {

            if ($s['value'] > 0) {
                $hasData = true;
            }

            $threshold = $thresholds[(string)$s['id']] ?? null;

            if (!$threshold) continue;

            $value = $s['value'];

            // ======================
            // LOW

            if ($value < $threshold->min_value) {

                $score -= 10;

                if (!empty($threshold->min_alert)) {
                    $alerts[] = $threshold->min_alert;
                }

                if (!empty($threshold->min_action)) {
                    $farmActions[] = $threshold->min_action;
                }
            }

// HIGH
            if ($value > $threshold->max_value) {

                $score -= 10;

                if (!empty($threshold->max_alert)) {
                    $alerts[] = $threshold->max_alert;
                }

                if (!empty($threshold->max_action)) {
                    $farmActions[] = $threshold->max_action;
                }
            }
        }
        return response()->json([
            'status' => 2000,
            'result' => [
                'chartData' => $result,
                'sensors' => $sensors,
                'farmHealth' => [
                    'score' => $score,
                    'alerts' => $alerts,
                    'actions' => $farmActions,
                ],
                'fetched_at' => now()->format('Y-m-d H:i:s')
            ]
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

<?php

use Carbon\Carbon;
use App\Models\User;
use App\Models\RBAC\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\Accounting\DealerLedger;
use Illuminate\Database\Eloquent\Builder;

if (!function_exists('input')) {
    function input($name)
    {
        if (request()->input($name)) {
            return request()->input($name);
        }
        return null;
    }
}
if (!function_exists('assets')) {
    function assets($path)
    {
        if (env('PUBLIC_PATH')) {
            return env('PUBLIC_PATH') . '/' . ltrim($path, '/');
        }
        return asset($path);
    }
}
if (!function_exists('can')) {
    function can($permission)
    {
        $permissions = Permission::whereHas('role_permissions', function ($query) {
            $query->where('role_id', auth()->user()->role_id);
        })->get()->pluck('name')->toArray();

        if (is_array($permission)) {
            foreach ($permission as $each_per) {
                if (in_array($each_per, $permissions)) {
                    return true;
                } else {
                    return false;
                }
            }
        } else {
            if (in_array($permission, $permissions)) {
                return true;
            } else {
                return false;
            }
        }
    }
}

if (!function_exists('getData')) {
    function getData($id, $column, $table = 'users', $whereColumn = 'id')
    {
        $user = DB::table($table)->where($whereColumn, $id)->first();
        if ($user) {
            return $user->{$column};
        }
        return '';
    }
}
if (!function_exists('storageImage')) {
    function storageImage($path)
    {
        if (!$path) {
            return publicImage('images/male_stuff.png');
        }
        if (env('UPLOAD_PATH')) {
            return env('UPLOAD_PATH') . '/' . $path;
        }
        return env('UPLOAD_PATH') . '/' . $path;
    }
}
if (!function_exists('publicImage')) {
    function publicImage($path)
    {
        return env('PUBLIC_PATH') . '/' . $path;
    }
}
if (!function_exists('returnData')) {
    function returnData($status_code = 2000, $result = null, $message = null, $type = false)
    {
        return response()->json(array_merge(
            ['status' => $status_code],
            array_filter([
                'result'  => $result,
                'message' => $message,
                'type'    => $type
            ], function ($v) {
                return ($v !== null && $v !== false);
            })
        ));
    }
}


if (!function_exists('returnUnauthorized')) {
    function returnUnauthorized($status_code = 4001, $result = null, $message = 'Unauthorized! Contact system Admin.')
    {
        $data['status'] = $status_code;
        $data['message'] = $message;
        if ($result) {
            $data['result'] = $result;
        }

        return response()->json($data);
    }
}

if (!function_exists('permissions')) {
    function permissions()
    {
        $user_permissons = @unserialize(session()->get(''));
        if (is_array($user_permissons)) {
            return $user_permissons;
        }
        return [];
    }
}

if (!function_exists('randomString')) {
    function randomString($length = 25, $type = 'n')
    {
        $characters = $type == 'n' ? '123456789' : '123456789abcdefghijklmnopqrstuvwxyz';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}

if (!function_exists('folder')) {
    function folder($path, $permission = 0777)
    {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
            return $path;
        } else {
            return $path;
        }
    }
}

if (!function_exists('appFile')) {
    function appFile($path)
    {
        if (file_exists(public_path() . $path)) {
            return $path;
        } else {
            return '/img/no-image.png';
        }
    }
}

if (!function_exists('uploadFile')) {
    function uploadFile($requestFile, $fileName = null, $folder = null)
    {
        try {
            if ($requestFile) {
                $filePath = $folder ? $folder : 'img/';
                $image = $requestFile;
                $format = explode('/', mime_content_type($requestFile))[1];
                $data['image'] = $fileName ? $fileName . ".$format" : time() . ".$format";
                $img = Image::make($image);
                $upload_path = folder(public_path($filePath));
                $image_url = $upload_path . $data['image'];
                $img->save($image_url);

                if ($img) {
                    return $filePath . $data['image'];
                }
                return null;
            }
        } catch (Exception $exception) {
            return null;
        }
    }
}

if (!function_exists('ddA')) {
    function ddA($arrayOrObject)
    {
        dd(collect($arrayOrObject)->toArray());
    }
}

if (!function_exists('exact_permission')) {
    function exact_permission($permission_name)
    {
        $explode = explode('_', $permission_name);
        return end($explode);
    }
}

if (!function_exists('configs')) {
    function configs($keys)
    {
        $configs = DB::table('configurations')->where(function ($query) use ($keys) {
            if (is_array($keys)) {
                $query->whereIn('key', $keys);
            } else {
                $query->where('key', $keys);
            }
        })->get();

        $conData = [];

        foreach ($configs as $config) {
            $conData[$config->key] = $config->value;

            if ($config->type == 'file') {
                $conData[$config->key] = storageImage($config->value);
            }
            if ($config->type == 'encoded') {
                $conData[$config->key] = json_decode($config->value);
            }
            if ($config->type == 'youtube') {
                $conData[$config->key] = deviceWiseUrl($config->value);
            }
        }

        if (count($keys) == 1) {
            return collect($conData)->first();
        }
        return $conData;
    }
}
if (!function_exists('levels')) {
    function levels($keys)
    {

        $levels = DB::table('level_name_manages')->where(function ($query) use ($keys) {
            if (is_array($keys)) {
                $query->whereIn('key', $keys);
            } else {
                $query->where('key', $keys);
            }
        })->get();

        $conData = [];

        foreach ($levels as $level) {
            $conData[$level->key] = $level->value;
        }

        if (count($keys) == 1) {
            return collect($conData)->first();
        }
        return $conData;
    }
}

if (!function_exists('strLimit')) {
    function strLimit($string, $limit)
    {
        return mb_strimwidth(strip_tags($string), 0, $limit, '...');
    }
}


if (!function_exists('themeLayout')) {
    function themeLayout()
    {
        if (auth()->check()) {
            return auth()->user()->layout;
        }
        return 'vertical';
    }
}

if (!function_exists('isSuperUser')) {
    function isSuperUser()
    {
        if (can('user_add') && can('user_update')) {
            return true;
        }

        return false;
    }
}

if (!function_exists('user')) {
    function user()
    {
        return auth()->user();
    }
}
if (!function_exists('userRole')) {
    function userRole($guard)
    {
        if (auth()->guard($guard)->check()) {
            return auth()->guard($guard)->user();
        }
    }
}
if (!function_exists('hasInput')) {
    function hasInput($name)
    {
        if (request()->input($name)) {
            return true;
        }

        return false;
    }
}
if (!function_exists('textToSlug')) {
    function textToSlug($string)
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
    }
}

if (!function_exists('dbValue')) {
    function dbValue($pbiID, $columNAme = 'PBI_ID', $tableName = 'personnel_basic_info')
    {
        return DB::table($tableName)->where($columNAme, $pbiID)->first();
    }
}
if (!function_exists('getTable')) {
    function getTable($tableName, $tablePrefix = 'dbo')
    {
        return "$tablePrefix.$tableName";
    }
}

if (!function_exists('fullLibraryPath')) {
    function fullLibraryPath($path)
    {
        return config('library.LIBRARY_PATH') . '/' . $path;
    }
}
if (!function_exists('getDeviceData')) {
    function getDeviceData($pythonScriptPath, $scriptFile, $deviceIpAddress, $devicePort, $execuitableFullPath = 0)
    {
        $attendenceScriptPath = fullLibraryPath($pythonScriptPath);
        $pythonScript = "$attendenceScriptPath/$scriptFile";

        $pythonPath = $execuitableFullPath ? "$attendenceScriptPath/venv/bin/python3" : "python3";
        $pythonCheckScript = "$attendenceScriptPath/checkConnection.py";
        $command = "$pythonPath $pythonScript $deviceIpAddress $devicePort 2>&1";
        $checkConnection = shell_exec("$attendenceScriptPath/venv/bin/python3 $pythonCheckScript $deviceIpAddress $devicePort 2>&1");


        $connectionResult = json_decode($checkConnection);

        if (isset($connectionResult->status) && $connectionResult->status) {
            $output = shell_exec($command);

            $attendance_info = json_decode($output, true);
            return (object)[
                'status' => true,
                'data' => $attendance_info,
                'message' => 'Successfully retrieved attendance'
            ];
        }

        return (object)[
            'status' => false,
            'data' => [],
            'message' => $connectionResult->message
        ];
    }
}

if (!function_exists('getLocale')) {
    function getLocale($key = '', $extraText = '')
    {
        if ($key) {
            return __($key) . " $extraText";
        }

        return '';
    }
}

if (!function_exists('checkComponentFile')) {
    function checkComponentFile(array $menuItem)
    {
        $component = isset($menuItem['component']) ? $menuItem['component'] : '';
        $file = base_path("resources/js/{$component}");

        if (!empty($component) && !file_exists($file)) {
            return (object)[
                'status' => false,
                'component' => $file,
                'message' => "Missing component",
            ];
        }

        return (object)[
            'status' => true,
            'component' => $file,
            'message' => "Valid Component",
        ];
    }
}
///var/www/l12v3/resources/js/views/pages/Dashboard.vue


if (!function_exists('generateUniqueCode')) {
    function generateUniqueCode($prefix, $column, $table)
    {
        do {
            $number = random_int(100000, 999999);
            $code = $prefix . '-' . $number;
            $exists = DB::table($table)->where($column, $code)->exists();
        } while ($exists);

        return $code;
    }
}


if (!function_exists('dealerLeagerBalance')) {
    function dealerLeagerBalance(array $data)
    {
        return
            DB::transaction(function () use ($data) {
                if (isset($data['id'])) {
                    $ledger = DealerLedger::where('id', $data['id'])->first();
                    $payment = DealerLedger::where('id', $data['id'])->first();
                    if ($ledger->id === $data['id']) {
                        $ledger->update([
                            'dealer_id'        => $data['dealer_id'] ?? $ledger->dealer_id,
                            'payment_id'       => $data['payment_id'] ?? $ledger->payment_id,
                            'order_id'         => $data['order_id'] ?? $ledger->order_id,
                            'customer_id'      => $data['customer_id'] ?? $ledger->customer_id,
                            'type'             => $data['type'] ?? $ledger->type,
                            'transaction_type' => $data['transaction_type'] ?? $ledger->transaction_type,
                            'invoice_id'       => $data['invoice_id'] ?? $ledger->invoice_id,
                            'date'             => $data['date'] ?? $ledger->date,
                            'debit'            => $data['debit'] ?? $ledger->debit,
                            'credit'           => $data['credit'] ?? $ledger->credit,
                            'remarks'          => $data['remarks'] ?? $ledger->remarks,
                        ]);

                        return $ledger;
                    }

                    if ($payment->id === $data['id']) {
                        $payment->update([
                            'dealer_id'        => $data['dealer_id'] ?? $payment->dealer_id,
                            'payment_id'       => $data['payment_id'] ?? $payment->payment_id,
                            'order_id'         => $data['order_id'] ?? $ledger->order_id,
                            'customer_id'      => $data['customer_id'] ?? $payment->customer_id,
                            'type'             => $data['type'] ?? $payment->type,
                            'transaction_type' => $data['transaction_type'] ?? $payment->transaction_type,
                            'invoice_id'       => $data['invoice_id'] ?? $payment->invoice_id,
                            'date'             => $data['date'] ?? $payment->date,
                            'debit'            => $data['debit'] ?? $payment->debit,
                            'credit'           => $data['credit'] ?? $payment->credit,
                            'remarks'          => $data['remarks'] ?? $payment->remarks,
                        ]);

                        return $payment;
                    }
                }
                return DealerLedger::create([
                    'payment_id'       => $data['payment_id'] ?? null,
                    'dealer_id'        => $data['dealer_id'] ?? null,
                    'order_id'         => $data['order_id'] ?? null,
                    'customer_id'      => $data['customer_id'] ?? null,
                    'type'             => $data['type'] ?? null,
                    'transaction_type' => $data['transaction_type'] ?? null,
                    'invoice_id'       => $data['invoice_id'] ?? null,
                    'date'             => $data['date'] ?? now(),
                    'debit'            => $data['debit'] ?? 0,
                    'credit'           => $data['credit'] ?? 0,
                    'remarks'          => $data['remarks'] ?? null,
                ]);
            });
    }
}

if (!function_exists('myLowerUserIds')) {
    function myLowerUserIds()
    {
        $authId = auth()->id();

        return \App\Models\User::all()
            ->filter(function ($user) use ($authId) {
                return in_array($authId, myLowerIdsForUser($user));
            })
            ->pluck('id')
            ->toArray();
    }
}

if (!function_exists('myLowerIdsForUser')) {
    function myLowerIdsForUser($user)
    {
        $ids = [];

        $superAdminId = \App\Models\User::where('is_superadmin', 1)->value('id');
        if ($superAdminId) $ids[] = $superAdminId;

        $financeId = \App\Models\User::where('manager', 0)->value('id');
        if ($financeId) $ids[] = $financeId;

        if ($user->division_id) {
            $divisionId = \App\Models\User::where('division_id', $user->division_id)
                ->where('manager', 2)
                ->value('id');
            if ($divisionId) $ids[] = $divisionId;
        }

        if (is_null($user->manager) && $user->district_id) {
            $districtId = \App\Models\User::where('district_id', $user->district_id)
                ->where('manager', 1)
                ->value('id');
            if ($districtId) $ids[] = $districtId;
        }

        return array_unique($ids);
    }
}


if (!function_exists('myUpperUserIds')) {
    function myUpperUserIds()
    {
        $user = auth()->user();
        $ids = [];

        $superAdminId = \App\Models\User::where('is_superadmin', 1)->value('id');
        if ($superAdminId) {
            $ids[] = $superAdminId;
        }

        $financeManagerId = \App\Models\User::where('manager', 0)->value('id');
        if ($financeManagerId) {
            $ids[] = $financeManagerId;
        }

        if ($user->division_id) {
            $divisionManagerId = \App\Models\User::where('division_id', $user->division_id)
                ->where('manager', 2)
                ->value('id');

            if ($divisionManagerId) {
                $ids[] = $divisionManagerId;
            }
        }

        if (is_null($user->manager) && $user->district_id) {
            $districtManagerId = \App\Models\User::where('district_id', $user->district_id)
                ->where('manager', 1)
                ->value('id');

            if ($districtManagerId) {
                $ids[] = $districtManagerId;
            }
        }

        return array_values(array_unique(array_diff($ids, [$user->id])));
    }
}


if (!function_exists('getAllowedUserIds')) {
    function getAllowedUserIds()
    {
        $user = auth()->user();

        if (!$user) {
            return [];
        }



        $allowedIds = [$user->id];

        //Super Admin and under his all users
        if ($user->is_superadmin == 1) {
            $salesmanUserIds = DB::table('users')->pluck('id')->toArray();
            $allowedIds = array_merge($allowedIds, $salesmanUserIds);
            return array_values(array_unique($allowedIds));
        }

        //Division Manager and all districts under his division
        if ($user->manager == 2 && $user->division_id) {
            $salesmanUserIds = DB::table('salesmen as s')
                ->join('users as u', 's.user_id', '=', 'u.id')
                ->join('warehouses as w', 'u.warehouse_id', '=', 'w.id')
                ->where('w.division_id', $user->division_id)
                ->where('w.status', 1)
                ->where('u.manager', 0)
                ->pluck('u.id')
                ->toArray();
            $allowedIds = array_merge($allowedIds, $salesmanUserIds);
        }
        // District Manager (manager = 1)
        elseif ($user->manager == 1 && $user->district_id) {
            $salesmanUserIds = DB::table('salesmen as s')
                ->join('users as u', 's.user_id', '=', 'u.id')
                ->join('warehouses as w', 'u.warehouse_id', '=', 'w.id')
                ->where('w.district_id', $user->district_id)
                ->where('w.status', 1)
//                ->where('u.manager', 0)
                ->pluck('u.id')
                ->toArray();
            $allowedIds = array_merge($allowedIds, $salesmanUserIds);
        }
        return array_values(array_unique($allowedIds));
    }
}

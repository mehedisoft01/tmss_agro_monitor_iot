<?php

namespace App\Helpers;

use App\Models\AppNotification;
use App\Models\Manager;
use App\Models\User;
use DateTime;
use DateInterval;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use PHPUnit\Exception;

trait Helper
{
    public $permission = [];
    public $model;
    public $modelClass = '';
    public $childModel = '';
    public $perPage = 20;
    public $permissionMessage = 'Sorry, You do not have permission to perform this action..!!';
    public $exceptionMessage = 'Whoops, looks like something went wrong.';
    public $permissionMessageType = 'error';
    public $statusCode = array('-100', '-110', '-111', '-112', '-113', '-114', '-115', '-116', '-120');

    public function __construct()
    {
        $perPage = input('perPage');
        if ($perPage && $perPage > 0) {
            $this->perPage = $perPage;
        }
    }

    public function status()
    {
        try {
            $column = request()->input('column') ? request()->input('column') : 'status';
            $data = $this->model->where($this->model->getKeyName(), input('id'))->first();

            if (!$data) {
                return returnData(2000, null, 'Data Not found');
            }

            if (request()->input('change_status')) {
                $data->{$column} = request()->input('change_status');
                $data->save();

                return returnData(2000, 'success', "Status Changed");
            }

            if ($data->{$column} == 1) {
                $data->{$column} = 0;
                $data->save();

                return returnData(2000, 'warning', "Successfully $column Changed");
            } else {
                $data->{$column} = 1;
                $data->save();

                return returnData(2000, 'success', "Successfully $column Changed");
            }
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Not Updated');
        }
    }

    public function notPermitted()
    {
        $data = [];
        $data['status'] = 5001;
        $data['message'] = $this->permissionMessage;
        $data['type'] = $this->permissionMessageType;
        return response()->json($data);
    }

    public function insertFile($fileName, $module_type = 2)
    {
        $orgName = request()->file($fileName)->getClientOriginalName();
        $extension = request()->file($fileName)->getClientOriginalExtension();
        $size = request()->file($fileName)->getSize();
        $timestamp = now()->format('Ymd_His');
        $newFileName = $timestamp . '_' . str_replace(' ', '_', $orgName);
        $path = request()->file('file')->storeAs('uploads', str_replace(' ', '_', $newFileName), 'public');

        $sizeInMb = round(($size / 1000) / 1000, 2);

        return [
            'path' => $path,
            'name' => $newFileName,
            'extension' => $extension,
            'size' => $sizeInMb,
        ];
    }
    public function permittedMenus()
    {
        $user = auth()->guard('admin')->user();
        $role_id = $user->role_id;
        $data['user'] = DB::table('admins')
            ->selectRaw("admins.*,personnel_basic_info.image,personnel_basic_info.PBI_SEX, salary_sheet_approval, responsibility, leave_request, transfer_request, resignation_request, appointment_letter, joining_letter, promotion_letter, demotion_letter, increment_letter, confirmation_letter,reporting_managers.approval_manager as approval_manager_id")
            ->leftJoin('personnel_basic_info', 'personnel_basic_info.PBI_ID', '=', 'admins.pbi_id')
            ->leftJoin('managers', 'admins.id', '=', 'managers.user_id')
            ->leftJoin('reporting_managers', 'admins.pbi_id', '=', 'reporting_managers.approval_manager')
            ->where('admins.id', $user->id)->first();

        $permissions = Permission::whereHas('role_permissions', function ($query) use ($role_id) {
            $query->where('role_id', $role_id);
        })->get();

        $permittedModules = collect($permissions)->pluck('module_id');
        $data['permissions'] = collect($permissions)->pluck('name')->toArray();
        $configs = configs(['app_name', 'logo', 'id_card_org']);
        $levels = levels(['level_1', 'level_2', 'level_3', 'level_4', 'level_5', 'level_6', 'level_7']);

        $orgLayer = DB::table('level_name_manages')->select('priority', 'value')->get()->keyBy('priority');

        $data['menus'] = Module::where('status', 1)->where('parent_id', 0)
            ->whereIn('id', $permittedModules)
            ->with(['submenus' => function ($query) use ($permittedModules) {
                $query->whereIn('id', $permittedModules);
                $query->orderBy('sort_index', 'ASC');
                $query->orderBy('parent_id', 'ASC');
                $query->orderBy('is_subparent', 'DESC');

                $query->with(['submenus' => function ($query) use ($permittedModules) {
                    $query->whereIn('id', $permittedModules);
                    $query->orderBy('sort_index', 'ASC');
                    $query->orderBy('parent_id', 'ASC');
                    $query->orderBy('is_subparent', 'DESC');
                }]);
            }])
            ->orderBy('sort_index', 'ASC')
            ->orderBy('parent_id', 'ASC')
            ->orderBy('is_subparent', 'DESC')
            ->orderBy('id', 'ASC')
            ->get()->toArray();

        $data['layer'] = $levels;
        $data['orgLayer'] = $orgLayer;
        $data['mfp'] = DB::table('configures')->where('key', 'microfinance_sector')->select('value')->first();

        $data = array_merge($data, $configs);

        return $data;
    }
    public function getTableValue($tableName, $columnName, $whereFilter = [])
    {
        $checkExist = DB::table($tableName)->where($whereFilter)->first();
        if ($checkExist) {
            return $checkExist->{$columnName};
        }
        DB::table($tableName)->insert([$whereFilter]);

        return $this->getTableValue($tableName, $columnName, $whereFilter);
    }

    public function addNotification($user_id = '', $send_to = '', $title = '', $details = '', $link = '', $type = 0, $type_id = 0)
    {
        $notification = new AppNotification();
        $notification->fill([
            'user_id' => $user_id,
            'send_to' => $send_to,
            'title' => $title,
            'notification' => $details,
            'link' => $link,
            'type' => $type,
            'type_id' => $type_id,
        ]);
        $notification->save();

        return $notification;
    }

    public function isSalesManger()
    {
        $salesmanRoles = ['district_manager_role', 'salesman_role'];
        $salesman_roles = DB::table('settings')->whereIn('key', ['district_manager_role', 'salesman_role'])->get()->pluck('value')->toArray();

        $user = User::where('users.id', auth()->user()->id)
            ->whereIn('role_id', $salesman_roles)
            ->first();
        if ($user){
            return true;
        }

        return false;
    }
}

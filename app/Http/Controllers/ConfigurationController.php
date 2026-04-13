<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\Module;
use App\Helpers\Helper;
use App\Models\GropList;
use App\Models\Operator;
use App\Models\Permission;
use App\Models\CampaignList;
use Illuminate\Http\Request;
use App\Models\Configuration;
use App\Models\TicketCategory;
use App\Models\SenderIdRequest;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class ConfigurationController extends Controller
{
    use Helper;

    public function __construct()
    {
        $this->model = new Configuration();
    }

    public function getConfigurations()
    {
        $role_id = auth()->user()->role_id;
        $user_id = auth()->user()->id;


        $data['school'] = [];
        $data['user'] = User::where('id', $user_id)->first();
        $data['config'] = configs(['logo', 'name']);

        $permissions = Permission::whereHas('role_permissions', function ($query) use ($role_id) {
            $query->where('role_id', $role_id);
        })->get();

        $permittedModules = collect($permissions)->pluck('module_id');
        $data['permissions'] = collect($permissions)->pluck('name');

        $data['menus'] = Module::where('parent_id', 0)
            ->whereIn('id', $permittedModules)
            ->with(['submenus' => function ($query) use ($permittedModules) {
                $query->with('submenus');
                $query->whereIn('id', $permittedModules);
            }])->get();

        return returnData(2000, $data);
    }

    public function getGeneralData($requestArray = false)
    {
        $array = $requestArray ? $requestArray : request()->all();
        $user = auth()->user();
        $data = [];

        if (in_array('days', $array)) {
            $data['days'] = [
                'saturday' => 'Saturday',
                'sunday' => 'Sunday',
                'monday' => 'Monday',
                'tuesday' => 'Tuesday',
                'wednesday' => 'Wednesday',
                'thursday' => 'Thursday',
            ];
        }

        if (in_array('module', $array)) {
            $data['module'] = Module::where('parent_id', 0)->get();
        }
        if (in_array('role', $array)) {
            $data['role'] = Role::get();
        }
        if (in_array('sites', $array)) {
            $data['sites'] = DB::table('sites')->get();
        }
        if (in_array('warehouse_device', $array)) {
            $data['warehouse_device'] = DB::table('devices')->where('device_category', 1)->get();
        }

        //        if (in_array('device', $array) || isset($array['device'])) {
        //            $key = isset($array['device']['objName']) ? $array['device']['objName'] : 'device';
        //            $data[$key] = DB::table('devices')->where(function ($query) use ($array) {
        //                if (isset($array['device']['type_id'])) {
        //                    $query->where('device_category', $array['device']['type_id']);
        //                }
        //            })->get();
        //        }


        //        if (in_array('device', $array) || isset($array['device'])) {
        //            $key = isset($array['device']['objName']) ? $array['device']['objName'] : 'device';
        //
        //            $data[$key] = collect();
        //
        //            if (isset($array['device']['type_id'])) {
        //                $typeId = $array['device']['type_id'];
        //
        //                if ($typeId == 1) {
        //                    // Fetch from devices table
        //                    $data[$key] = DB::table('devices')->where('device_category',1)->select('device_id','display_name as name')->get();
        //                } elseif ($typeId == 2) {
        //                    // Fetch from soil_devices table
        //                    $data[$key] = DB::table('soil_devices')->select('id as device_id','device_id as name','device_name')->get();
        //                }
        //            }
        //        }

        if (in_array('device', $array) || isset($array['device'])) {
            $key = isset($array['device']['objName']) ? $array['device']['objName'] : 'device';

            $data[$key] = collect();

            if (isset($array['device']['type_id'])) {
                $typeId = $array['device']['type_id'];

                if ($typeId == 1) {
                    $data[$key] = DB::table('devices')
                        ->where('device_category', 1)
                        ->select('device_id', 'display_name as name')
                        ->get();
                } elseif ($typeId == 2) {
                    $query = DB::table('soil_devices')
                        ->select('id as device_id', 'device_id as name', 'device_name');

                    // Farmer type filter
                    if (isset($array['device']['farmer_type']) && $array['device']['farmer_type']) {
                        $query->where('farmer_type', $array['device']['farmer_type']);
                    }

                    $data[$key] = $query->get();
                }
            }
        }

        if (in_array('soil_device', $array)) {
            $data['soil_device'] = DB::table('soil_devices')->get();
        }
        if (in_array('warehouse_device', $array)) {
            $data['warehouse_device'] = DB::table('devices')->where('device_category', 1)->get();
        }
        if (in_array('date', $array)) {
            $data['date'] = date('Y-M-d');
        }

        if (in_array('users', $array)) {
            if (auth()->user()->is_superadmin) {
                $data['users'] = User::where('status', 1)->get();
            } else {
                $data['users'] = User::where('id', auth()->user()->id)->where('status', 1)->get();
            }
        }

        if ($requestArray) {
            return $data;
        }

        return returnData(2000, $data);
    }

    public function getGeneralDependencyData()
    {
        $array = request()->all();
    }

    public function index()
    {
        try {

            $keyword = request()->input('keyword');
            $datas = $this->model
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where('name', 'Like', "%$keyword%");
                })
                ->paginate($this->perPage);
            return returnData(2000, $datas);
        } catch (\Exception $exception) {
            return response()->json(returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!'));
        }
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        try {
            $input = $request->all();
            $validate = $this->model->validate($input);
            if ($validate->fails()) {
                return returnData(2000, $validate->errors());
            }

            $this->model->fill($input);
            $this->model->save();

            return returnData(2000, $this->model, 'Successfully Inserted');
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }

    public function show($id) {}

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        try {
            $input = $request->all();

            $validate = $this->model->validate($input);
            if ($validate->fails()) {
                return returnData(2000, $validate->errors());
            }

            $data = $this->model->where('id', $input['id'])->first();

            if ($data) {
                $data->fill($input);
                $data->save();

                return returnData(2000, $this->model, 'Successfully Updated');
            }

            return returnData(5000, $this->model, 'Data not found');
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }

    public function destroy($id)
    {
        try {
            $data = $this->model->where('id', $id)->first();
            if ($data) {
                $data->delete();

                return returnData(2000, $data, 'Successfully Deleted');
            }

            return returnData(5000, null, 'Data Not found');
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }
}

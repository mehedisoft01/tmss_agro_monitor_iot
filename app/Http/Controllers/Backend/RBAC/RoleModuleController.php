<?php

namespace App\Http\Controllers\Backend\RBAC;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\RBAC\Module;
use App\Models\RBAC\RoleModules;
use App\Models\RBAC\RolePermission;
use Illuminate\Http\Request;
use function Symfony\Component\Finder\in;

class RoleModuleController extends Controller
{
    use Helper;

    public function __construct()
    {
        $this->model = new RoleModules();
        $this->childModel = new Module();
    }

    public function index()
    {
        try {
            $data = $this->childModel
                ->whereHas('role_modules', function ($query) {
                    if (input('keyword')) {
                        $keyword = input('keyword');
                        $query->where('display_name', 'Like', "%$keyword%");
                    }
                })
                ->with(['role_modules'])
                ->paginate(input('perPage'));

            return returnData(2000, $data);

        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
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

            $validate = $this->model->validate();

            if ($validate->fails()) {
                return returnData(3000, $validate->errors(), 'You must select role and atleast one Module');
            }

            foreach ($request->modules as $module) {
                $check_exists = $this->model->where('role_id', input('role_id'))
                    ->where('module_id', $module)->first();

                if (!$check_exists) {
                    $this->model->insert([
                        'role_id' => $request->role_id,
                        'module_id' => $module,
                    ]);
                }
            }

            foreach ($request->permissions as $permission) {
                $check_exists = RolePermission::where([
                    'role_id' => $request->role_id,
                    'permission_id' => $permission,
                ])->first();

                if (!$check_exists) {
                    RolePermission::insert([
                        'role_id' => $request->role_id,
                        'permission_id' => $permission,
                    ]);
                }
            }

            return returnData(2000, null, 'Successfully Inserted');

        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }

    }

    public function show($id)
    {
        //
    }

    public function edit(Request $request, $id)
    {

    }

    public function update(Request $request, $id)
    {


    }

    public function destroy(Request $request, $id)
    {
        try {
            if (isset($request->delete_type) && $request->delete_type == 'role') {
                $exist = $this->model->where('module_id', $request->module_id)
                    ->where('role_id', $request->role_id)
                    ->first();

                if ($exist) {
                    $permissions_id = Permission::where('module_id', $request->module_id)->get()->pluck('id')->toArray();
                    RolePermission::where('role_id', $request->role_id)
                        ->whereIn('permission_id', $permissions_id)
                        ->delete();

                    $exist->delete();

                }
            } else {
                $role_modules = $this->model->where('module_id', $id)->get();

                foreach ($role_modules as $module) {
                    $permissions_id = Permission::where('module_id', $module->module_id)->get()->pluck('id')->toArray();

                    RolePermission::where('role_id', $module->role_id)
                        ->whereIn('permission_id', $permissions_id)
                        ->delete();
                }

                $this->model->where('module_id', $id)->delete();
            }

            return returnData(2000, null, 'Successfully Deleted');

        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }


    }
}

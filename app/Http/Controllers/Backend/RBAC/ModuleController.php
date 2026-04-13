<?php

namespace App\Http\Controllers\Backend\RBAC;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\RBAC\Module;
use App\Models\RBAC\Permission;
use App\Models\RBAC\RoleModules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function Symfony\Component\Finder\in;

class ModuleController extends Controller
{
    use Helper;

    public function __construct()
    {
        if (!can(request()->route()->action['as'])) {
            return returnData(5001, null, 'You are not authorized to access this page');
        }
        $this->model = new Module();
        $this->modelClass = Module::class;
    }

    public function index()
    {
        $keyword = input('keyword');

        $data = $this->model->where('parent_id', 0)
            ->when($keyword, function ($query) use ($keyword) {
                $query->where('name', 'Like', "%$keyword%");
            })
            ->with('permissions')
            ->with(['submenus' => function ($query) use ($keyword) {
                $query->when($keyword, function ($query) use ($keyword) {
                    $query->where('name', 'Like', "%$keyword%");
                });
                $query->with('permissions');

                $query->with(['submenus' => function ($query) use ($keyword) {
                    $query->when($keyword, function ($query) use ($keyword) {
                        $query->where('name', 'Like', "%$keyword%");
                    });
                    $query->with('permissions');
                }]);
            }])
            ->orderBy('id', 'DESC')
            ->paginate(input('per_page'));

        return returnData(2000, $data);
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        try {

            DB::beginTransaction();

            $input = $request->except('permissions');
            $input['route_type'] = 2;
            $permissions = $request->input('permissions');
            $validate = $this->model->validate($input);

            if ($validate->fails()) {
                return returnData(3000, $validate->errors());
            }
            $this->model->fill($input);
            $this->model->save();

            $name = $this->model->name;
            $uniquePermissions = collect($permissions)->unique()->toArray();
            foreach ($uniquePermissions as $perName) {
                $permissionData = new Permission();
                $permissionData->module_id = $this->model->id;
                $permissionData->name = "$name.$perName";
                $permissionData->display_name = ucfirst("$name $perName");
                $permissionData->save();
            }

            DB::commit();

            return returnData(2000, null, 'Successfully Inserted');
        } catch (\Exception $exception) {
            DB::rollBack();
            return returnData(5000, $exception->getMessage(), 'Something Wrong');
        }
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $module = Module::where('id', $id)->first();
        if ($module) {
            $permissions = Permission::where('module_id', $module->id)->get();
            $allPermission = [];
            foreach ($permissions as $permission) {
                $perExp = explode('.', $permission->name);
                $allPermission[] = end($perExp);
            }

            $module->{'permissions'} = $allPermission;

            return returnData(2000, $module);
        }

        return returnData(5000, null, 'Module Not Found');
    }

    public function update(Request $request, $id)
    {
        $input = $request->all();
        $permissions = $request->input('permissions');

        $validate = $this->model->validate($input);
        if ($validate->fails()) {
            return returnData(3000, $validate->errors());
        }

        $module = $this->model->where('id', $request->id)->first();

        if ($module) {
            $module->fill($input);
            $module->save();

            $permissionIds = [];
            $uniquePermissions = collect($permissions)->unique()->toArray();
            foreach ($uniquePermissions as $perName) {
                $permissionData = Permission::where('name', $perName)->first();
                if (!$permissionData) {
                    $permissionData = new Permission();
                }

                $permissionData->module_id = $module->id;
                $permissionData->name = $module->name . "." . $perName;
                $permissionData->display_name = ucfirst("$module->name $perName");
                $permissionData->save();

                $permissionIds[] = $permissionData->id;
            }

            Permission::whereNotIn('id', $permissionIds)->where('module_id', $this->model->id)->delete();

            $this->addNotification(getLocale('modules', 'Updated'), 'Successfully Updated');
        }

        return returnData(2000, null, "$module->name Successfully Updated");
    }

    public function destroy($id)
    {
        $data = $this->model->find($id);

        if (!$data) {
            return returnData(5000, null, 'Data Not Found');
        }

        $subModules = RoleModules::where('module_id', $data->id)->count();

        if ($subModules > 0) {
            return returnData(5000, null, "You can't Delete Module");
        }

        // Delete related records first
        Permission::where('module_id', $data->id)->delete();
        RoleModules::where('module_id', $data->id)->delete();

        // Then delete the main record
        $data->delete();

        return returnData(2000, $data, "Successfully Deleted");
    }

    public function multiple(Request $request)
    {
        $selectedKeys = $request->input('selectedKeys', []);
        $ids = is_array($selectedKeys) ? $selectedKeys : [];

        if (empty($ids)) {
            return returnData(4000, null, 'No IDs provided');
        }

        $deleted = [];
        $errors = [];

        foreach ($ids as $id) {
            $data = Module::where('id', $id)->first();

            if (!$data) {
                $errors[] = "ID {$id} not found";
                continue;
            }

            $subModules = RoleModules::where('module_id', $id)->count();
            if ($subModules > 0) {
                $errors[] = $id;
                continue;
            }

            // Delete related records first
            Permission::where('module_id', $id)->delete();
            RoleModules::where('module_id', $id)->delete();

            // Delete main record
            $data->delete();
            $deleted[] = $id;
        }

        return returnData(2000, null,count($deleted)." Item Deleted and ".json_encode($errors)." Item Not Deleted");
    }

}


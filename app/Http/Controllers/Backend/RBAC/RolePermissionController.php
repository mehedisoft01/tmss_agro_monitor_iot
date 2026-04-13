<?php

namespace App\Http\Controllers\Backend\RBAC;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\RBAC\Module;
use App\Models\RBAC\RoleModules;
use App\Models\RBAC\RolePermission;
use Illuminate\Http\Request;
use function Symfony\Component\Finder\in;

class RolePermissionController extends Controller
{
    use Helper;

    public function __construct()
    {
        $this->model = new RoleModules();
        $this->childModel = new RolePermission();
    }

    public function index()
    {
        try {
            $role_id = request()->input('role_id');
            $keyword = input('keyword');
            $perPage = 500; //request()->get('perPage', 100);

            $data['modules'] = $this->model->where('role_id', $role_id)
                ->get()->pluck('module_id')
                ->toArray();

            $data['permissions'] = $this->childModel->where('role_id', $role_id)
                ->get()->pluck('permission_id')
                ->toArray();

            $data['all_modules'] = Module::where('parent_id', 0)->where('is_visible', 1)
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where('name', 'Like', "%$keyword%");
                    $query->orWhere('display_name', 'Like', "%$keyword%");
                })
                ->with('permissions')
                ->with(['submenus' => function ($query) use ($keyword) {
                    $query->where('is_visible', 1);
                    $query->when($keyword, function ($query) use ($keyword) {
                        $query->where('name', 'Like', "%$keyword%");
                        $query->orWhere('display_name', 'Like', "%$keyword%");
                    });
                    $query->with('permissions');

                    $query->with(['submenus'=>function($query) use ($keyword){
                        $query->where('is_visible', 1);
                        $query->when($keyword, function ($query) use ($keyword) {
                            $query->where('name', 'Like', "%$keyword%");
                            $query->orWhere('display_name', 'Like', "%$keyword%");
                        });
                        $query->with('permissions');
                    }]);
                }])
                ->paginate($perPage);

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
            if ((input('data_type') == 'all' && input('type') == 'insert')){
                $modules = Module::with('permissions')->get();
                $modulePermissions = $this->model->where('role_id', input('role_id'))->get()->keyBy('module_id');
                $allPermission = $this->childModel->where('role_id', input('role_id'))->get()->keyBy('permission_id');

                foreach ($modules as $module){
                    if (!isset($modulePermissions[$module->id])){
                        $this->model->insert([
                            'role_id' => input('role_id'),
                            'module_id' => $module->id,
                        ]);
                    }
                    foreach ($module->permissions as $permission){
                        if (!isset($allPermission[$permission->id])){
                            $this->childModel->insert([
                                'role_id' => input('role_id'),
                                'permission_id' => $permission->id,
                            ]);
                        }
                    }
                }

                return returnData(2000, null, 'Access Successfully Added');
            }

            if ((input('data_type') == 'all' && input('type') == 'remove')){

                $this->model->where('role_id', input('role_id'))->delete();
                $this->childModel->where('role_id', input('role_id'))->delete();

                return returnData(2000, null, 'Access Remove Successfully');
            }

            if ((input('type') == 'insert' && input('data_type') != 'all')) {
                if (input('module_id')) {
                    $check_exists = $this->model->where('role_id', input('role_id'))
                        ->where('module_id', input('module_id'))
                        ->first();

                    if (!$check_exists) {
                        $this->model->insert([
                            'role_id' => input('role_id'),
                            'module_id' => input('module_id'),
                        ]);
                    }
                }
                if (is_array(input('permissions')) && count(input('permissions')) > 0) {
                    foreach (input('permissions') as $permission) {
                        $check_exists = $this->childModel->where('role_id', input('role_id'))
                            ->where('permission_id', $permission)
                            ->first();

                        if (!$check_exists) {
                            $this->childModel->insert([
                                'role_id' => input('role_id'),
                                'permission_id' => $permission,
                            ]);
                        }
                    }
                }

                return returnData(2000, null, 'Access Successfully Added');
            }

            if ((input('type') == 'remove' && input('data_type') != 'all')) {
                if (input('module_id')) {
                    $check_exists = $this->model->where('role_id', input('role_id'))
                        ->where('module_id', input('module_id'))
                        ->first();

                    if ($check_exists) {
                        $check_exists->delete();
                    }
                }

                if (is_array(input('permissions')) && count(input('permissions')) > 0) {
                    foreach (input('permissions') as $permission) {
                        $check_exists = $this->childModel->where('role_id', input('role_id'))
                            ->where('permission_id', $permission)
                            ->first();
                        if ($check_exists) {
                            $check_exists->delete();
                        }
                    }
                }

                return returnData(2000, null, 'Access Successfully Removed');
            }

        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }

    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {


    }

    public function destroy($id)
    {


    }
}

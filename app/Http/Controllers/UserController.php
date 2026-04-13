<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use Helper;

    public function __construct()
    {
        $this->model = new User();
    }

    public function index()
    {
        $role_id = input('role_id');
        $users = $this->model->when(input('keyword'),
            function ($query) {
                $keyword = input('keyword');
                $query->where('name', "LIKE", "%$keyword%");
            })
            ->with('roles:id,display_name')
            ->when($role_id, function ($query) use ($role_id) {
                $query->whereHas('roles', function ($query) use ($role_id) {
                    $query->where('role_id', 'LIKE', "%$role_id%");
                });
            })
            ->paginate($this->perPage);

        return returnData(2000, $users);
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $data['is_superadmin'] = isset($data['is_superadmin']) && $data['is_superadmin'] ? 1 : 0;
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $this->model->fill($data);
        $this->model->save();
        return returnData(2000, null, 'Successfully Inserted');
    }

    public function show($id)
    {
        $perPage = request()->input('perPage');
        $data = $this->model->where('id', $id)
            ->orderBy('id', 'DESC')
            ->paginate($perPage);

        return returnData(2000, $data);
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        $existingData = $this->model->find($id);
        if (!$existingData) {
            return returnData(5000, null, 'Data Not Found');
        }
        $data = $request->all();
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $existingData->fill($data);
        $existingData->save();

        return returnData(2000, null, 'Successfully Updated');
    }

    public function destroy($id)
    {
        if (!can('user_delete')) {
            return $this->notPermitted();
        }
        try {
            $data = $this->model->where('id', $id)->first();
            if (!$data) {
                return returnData(5000, null, 'Data Not found');
            }

            $data->delete();

            return returnData(2000, $data, 'Successfully Deleted');

        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }

}

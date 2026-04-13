<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\RBAC\Module;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use Helper;
    public function __construct()
    {
        if (!can(request()->route()->action['as'])) {
            return returnData(5001, null, 'You are not authorized to access this page');
        }
        $this->model = new User();
    }

    public function index()
    {
        if (!can('users.index')) {
            return $this->notPermitted();
        }
        $perPage = request()->input('per_page', 10);

        $data = DB::table('users as u')
            ->leftJoin('salesmen as s', 'u.salesman_id', '=', 's.id')
            ->leftJoin('roles as r', 'u.role_id', '=', 'r.id')
            ->selectRaw("u.*, '' as password, s.name as salesman_name,s.salesman_code as salesman_code,r.name as role_name")
            ->paginate($perPage);

//        ddA($data);

        return returnData(2000, $data);
    }


    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        if (!can('users.store')) {
            return $this->notPermitted();
        }
        try{
            $data = $request->all();
            $data['is_superadmin'] = isset($data['is_superadmin']) && $data['is_superadmin'] ? 1 : 0;

            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }
            $this->model->fill($data);
            $this->model->save();

            return returnData(2000, null, 'Successfully Inserted');
        }catch (\Exception $exception){
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }

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
        if (!can('users.update')) {
            return $this->notPermitted();
        }
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
        if (!can('users.destroy')) {
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

<?php

namespace App\Http\Controllers\Backend\Inventory;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Inventory\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    use Helper;
    public function __construct()
    {
//        if (!can(request()->route()->action['as'])) {
//            return returnData(5001, null, 'You are not authorized to access this page');
//        }
        $this->model = new Warehouse();
    }

    public function index()
    {
        try {
            $keyword = request()->input('keyword');
            $divisionId = request()->input('division_id');
            $districtId = request()->input('district_id');
            $upazilaId = request()->input('upazila_id');
            $perPage = request()->input('per_page');
            $user = auth()->user();
            $isSalesman = $this->isSalesManger();

            $data = $this->model
                ->with(['division:id,division_name', 'district:id,district_name', 'upazila:id,upazila_name'])
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('warehouse_name', 'like', "%{$keyword}%")
                            ->orWhere('warehouse_code', 'like', "%{$keyword}%")
                            ->orWhere('phone', 'like', "%{$keyword}%")
                            ->orWhere('email', 'like', "%{$keyword}%");
                    });
                })
                ->when($divisionId, function ($query) use ($divisionId) {
                    $query->where('division_id', 'Like', "%$divisionId%");
                })
                ->when($districtId, function ($query) use ($districtId) {
                    $query->where('district_id', 'Like', "%$districtId%");
                })
                ->when($upazilaId, function ($query) use ($upazilaId) {
                    $query->where('upazila_id', 'Like', "%$upazilaId%");
                })
                ->where(function ($query) use ($user) {
                    if ($user->division_id){
                        $query->where('division_id', $user->division_id);
                    }
                    if ($user->district_id){
                        $query->where('district_id', $user->district_id);
                    }
                    if ($user->warehouse_id){
                        $query->where('id', $user->warehouse_id);
                    }
                })
                ->when($isSalesman, function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->orderBy('id','DESC')
                ->paginate($perPage);

            return returnData(2000, $data);
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }


    public function store(Request $request)
    {
        try {
            $auth = auth()->user();
            $input = $request->all();
            $input['user_id'] = $auth->id;

            $validate = $this->model->validate($input);
            if ($validate->fails()) {
                return returnData(3000, $validate->errors());
            }

            $this->model->fill($input);
            $this->model->save();

            return returnData(2000, null, 'Successfully Inserted');
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }

    public function show( $id)
    {
        //
    }

    public function edit($id)
    {
        $data = Warehouse::find($id);
        if ($data) {
            return returnData(2000, $data, 'Data Updated');
        }

        return returnData(5000, null, 'Data not found');
    }

    public function update(Request $request, $id)
    {
        try {
            $input = $request->all();
            // logged in user_id override
            $input['user_id'] = auth()->id();

            $validate = $this->model->validate($input);
            if ($validate->fails()) {
                return returnData(3000, $validate->errors());
            }

            $data = $this->model->find($id);
            if ($data) {
                $data->update($input);
                return returnData(2000, null, 'Successfully Updated');
            }
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }



    public function destroy( $id)
    {
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

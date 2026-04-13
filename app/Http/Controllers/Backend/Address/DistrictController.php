<?php

namespace App\Http\Controllers\Backend\Address;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Address\District;
use Illuminate\Http\Request;

class DistrictController extends Controller
{
    use Helper;

    public function __construct()
    {
        if (!can(request()->route()->action['as'])) {
            return returnData(5001, null, 'You are not authorized to access this page');
        }
        $this->model = new District();
    }

    public function index()
    {
        if (!can('district.index')) {
            return $this->notPermitted();
        }
        try {
            $keyword = request()->input('keyword');
            $division = request()->input('division_id');
            $perPage = request()->input('per_page');

            $data = $this->model
                ->leftJoin('divison', 'district.division_id', '=', 'divison.id')
                ->select(
                    'district.*',
                    'divison.division_name'
                )
                ->when($division, function ($query) use ($division) {
                    $query->where('district.division_id', 'Like', "%$division%");
                })
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where('district.district_name', 'Like', "%$keyword%");
                })
                ->orderBy('district.id', 'DESC')
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
        if (!can('district.store')) {
            return $this->notPermitted();
        }
        try {
            $input = $request->all();
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
        if (!can('district.update')) {
            return $this->notPermitted();
        }
        try {
            $input = $request->all();
            $validation = $this->model->validate($input);
            if ($validation->fails()) {
                return response()->json(['status' => 3000, 'errors' => $validation->errors()], 200);
            }
            $data = $this->model->find($id);
            if ($data) {
                $data->update($input);
                return returnData(2000, null, 'Successfully Updated');
            }
            return returnData(5000, null, 'Data Not found');
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }

    public function destroy($id)
    {
        if (!can('district.destroy')) {
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

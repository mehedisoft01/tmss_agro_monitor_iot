<?php

namespace App\Http\Controllers\Backend\Inventory;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Inventory\supplier;
use Illuminate\Http\Request;

class supplierController extends Controller
{
    use Helper;

    public function __construct()
    {
//        if (!can(request()->route()->action['as'])) {
//            return returnData(5001, null, 'You are not authorized to access this page');
//        }
        $this->model = new supplier();
    }

    public function index()
    {
        if (!can('supplier.index')) {
            return $this->notPermitted();
        }

        try {
            $keyword = request()->input('keyword');
            $perPage = request()->input('per_page');

            $data = $this->model
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%")
                            ->orWhere('email', 'like', "%{$keyword}%")
                            ->orWhere('phone', 'like', "%{$keyword}%");
                    });
                })
                ->orderBy('id', 'DESC')
                ->paginate($perPage);

            return returnData(2000, $data);
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }

    public function store(Request $request)
    {
        if (!can('supplier.store')) {
            return $this->notPermitted();
        }
        try {
            $input = $request->all();

            // logged in user id insert
            $input['user_id'] = auth()->id();

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
        try {
            $data = $this->model->findOrFail($id);
            return returnData(2000, $data);
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Record not found or something went wrong.');
        }
    }



    public function edit(string $id)
    {
        //
    }


    public function update(Request $request, $id)
    {
        if (!can('supplier.update')) {
            return $this->notPermitted();
        }
        try {
            $input = $request->all();

            // logged in user_id override
            $input['user_id'] = auth()->id();

            $validation = $this->model->validate($input);
            if ($validation->fails()) {
                return response()->json(['status' => 3000, 'errors' => $validation->errors()], 300);
            }
            $data = $this->model->find($id);
            if ($data) {
                $data->update($input);
                return returnData(2000, null, 'Successfully Updated');
            }
            return returnData(2000, null, 'Unsuccessful Updated');
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }

    public function destroy($id)
    {
        if (!can('supplier.destroy')) {
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

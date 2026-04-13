<?php

namespace App\Http\Controllers\Backend\ProductManagement;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductUnit;
use Illuminate\Http\Request;

class ProductUnitController extends Controller
{
    use Helper;

    public function __construct()
    {
        if (!can(request()->route()->action['as'])) {
            return returnData(5001, null, 'You are not authorized to access this page');
        }
        $this->model = new ProductUnit();
    }


    public function index()
    {
        if (!can('product_unit.index')) {
            return $this->notPermitted();
        }

        try {
            $keyword = request()->input('keyword');

            $data = $this->model
                ->checkWarehouse()
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where('name', 'like', "%$keyword%");
                })
                ->orderBy('id','DESC')
                ->paginate(request('perPage'));

            return returnData(2000, $data);
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }



    public function store(Request $request)
    {
        if (!can('product_unit.store')) {
            return $this->notPermitted();
        }

        try {
            $auth = auth()->user();
            $input = $request->all();
            $input['warehouse_id'] = ($auth->warehouse_id != 0) ? $auth->warehouse_id : null;
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


    public function show($id)
    {
        try {
            $data = $this->model->findOrFail($id);
            return returnData(2000, $data);
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Record not found or something went wrong.');
        }
    }



    public function update(Request $request, $id)
    {
        if (!can('product_unit.update')) {
            return $this->notPermitted();
        }
        try {
            $input = $request->all();

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
        if (!can('product_unit.destroy')) {
            return $this->notPermitted();
        }
        try {
            $data = $this->model->where('id', $id)->first();
            if (!$data) {
                return returnData(5000, null, 'Data Not found');
            }

            $productUnit = Product::where('product_unit_id', $id)->exists();

            if($productUnit){
                return returnData(4000,null,'This Unit is in use and cannot be deleted.');
            }

            $data->delete();

            return returnData(2000, $data, 'Successfully Deleted');

        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }
}


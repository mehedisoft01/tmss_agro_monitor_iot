<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\SoilDevice;
use Illuminate\Http\Request;


class SoilDeviceController extends Controller
{
    use Helper;
    public function __construct()
    {
        $this->model = new SoilDevice();
    }
    public function index()
    {
        $data = $this->model->orderBy('id','desc')->paginate(15);
        return returnData(2000, $data);
    }


    public function create()
    {
        //
    }


    public function store(Request $request)
    {

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
        try {
            $input = $request->all();

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

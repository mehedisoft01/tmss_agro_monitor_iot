<?php

namespace App\Http\Controllers;

use App\Models\DeviceThreshold;
use App\Models\Notification;
use Illuminate\Http\Request;

class DeviceThresholdsController extends Controller
{
    public function index()
    {
        $perPage = request()->input('perPage');
        $data = DeviceThreshold::orderBy('id','desc')->paginate($perPage);

        return returnData(2000, $data);
    }

    public function store(Request $request)
    {
        try {
            $input = $request->all();

            $model = new DeviceThreshold();

            $validate = $model->validate($input);
            if ($validate->fails()) {
                return returnData(3000, $validate->errors());
            }

            $model->fill($input);
            $model->save();

            return returnData(2000, null, 'Device Thresholds added successfully');

        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $input = $request->all();

            $model = new DeviceThreshold();

            $validation = $model->validate($input);
            if ($validation->fails()) {
                return returnData(3000, $validation->errors());
            }

            $data = DeviceThreshold::find($id);

            if ($data) {
                $data->update($input);
                return returnData(2000, null, 'Device Thresholds Successfully Updated');
            }

            return returnData(5000, null, 'Data Not Found');

        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }

    public function destroy($id)
    {
        try {
            $data = DeviceThreshold::find($id);

            if (!$data) {
                return returnData(5000, null, 'Data Not Found');
            }

            $data->delete();

            return returnData(2000, $data, 'Device Thresholds Successfully Deleted');

        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }

    public function checkThreshold(Request $request)
    {
        $device_id = $request->device_id;
        $sensor_id = $request->sensor_id;
        $value = $request->value;

        $threshold = DeviceThreshold::where('sensor_id', $sensor_id)->first();

        if (!$threshold) {
            return returnData(5000, null, 'Threshold not found');
        }

        if ($value < $threshold->min_value || $value > $threshold->max_value) {
            Notification::create([
                'device_id' => $device_id,
                'device_category_id' => $threshold->device_category_id,
                'sensor_id' => $sensor_id,
                'current_value' => $value,
                'min_value' => $threshold->min_value,
                'max_value' => $threshold->max_value,
                'message' => "Alert! Value out of range",
                'is_read' => 0,
            ]);

            return returnData(2000, null, 'Notification created');
        }

        return returnData(2000, null, 'Value within threshold');
    }
}

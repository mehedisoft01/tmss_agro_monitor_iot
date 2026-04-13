<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    use Helper;

    public function __construct()
    {
        $this->model = new Setting();

        if (!can(request()->route()->action['as'])) {
            return returnData(5001, null, 'You are not authorized to access this page');
        }
    }

    public function index(){
        $data = $this->model->where('is_visible', 1)->get();
        $settingGroup = $data->groupBy('setting_type');

        return returnData(2000, $settingGroup);
    }

    public function store(Request $request)
    {
        try {
            $input = $request->all();
            $validate = Validator::make($input, [
                'key' => 'required',
                'is_visible' => 'required',
                'setting_type' => 'required',
                'type' => 'required',
                'value' => 'required',
            ]);
            if ($validate->fails()) {
                return returnData(3000, $validate->errors());
            }

            $this->model->fill($input);
            $this->model->save();

            return returnData(2000, null, 'Successfully Updated');
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }

    public function update(Request $request)
    {
        try {
            $input = $request->all();

            $validate = Validator::make($request->all(), [

            ]);
            if ($validate->fails()) {
                return returnData(2000, $validate->errors());
            }

            $value = $this->model->get()->keyBy('key');
            foreach ($input as $key => $eachGroup) {
                foreach ($eachGroup as $index => $data) {
                    if (isset($value[$data['key']])) {
                        $existData = $value[$data['key']];
                        $existData->value = $data['value'];
                        $existData->save();
                    }
                }
            }
            return returnData(2000, null, 'Successfully Updated');
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }
}

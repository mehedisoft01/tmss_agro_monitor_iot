<?php

namespace App\Http\Controllers\Backend\HumanResource;

use App\Helpers\Helper;
use App\Models\HumanResource\SalaryConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\HumanResource\Salary;

class SalaryController extends Controller
{
    use Helper;

    public function __construct()
    {
        $this->model = new SalaryConfiguration();
    }
    public function index()
    {
        try {
            $keyword = request()->input('keyword');
            $salesman_id = request()->input('salesman_id');
            $perPage = request()->input('per_page');

            $data = $this->model->with('salesman')
                ->when($keyword, function ($query) use ($keyword) {
                    $query->whereHas('sales_man', function ($q) use ($keyword) {
                        $q->where('name', 'LIKE', "%{$keyword}%");
                    });
                })
                ->when($salesman_id, function ($q) use ($salesman_id) {
                    $q->where('salesman_id',$salesman_id);
                })
               ->paginate($perPage);

            return returnData(2000, $data);
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $auth = auth()->user();
            $input = $request->all();
            $input['user_id'] = $auth->id;

            dd($input);

            $emp = DB::table('salesmen')->where('id', $input['salesman_id'])->first();
            $this->model->where('salesman_code', $emp->salesman_code)->update(['status' => 0]);

            $input['warehouse_id'] = $emp->warehouse_id;
            $input['salesman_code'] = $emp->salesman_code;

            $validate = $this->model->validate($input);
            if ($validate->fails()) {
                return returnData(5000, $validate->errors()->first(), 'Validation Error..!!');
            }
            $data = $this->model->create($input);

            return returnData(2000, $data, 'Salary Structure Added Successfully..!!');
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $data = $this->model->find($id);
            if ($data) {
                $data->delete();

                return returnData(2000, $data, 'Successfully Deleted & Previous Reactivated');
            }
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }
}

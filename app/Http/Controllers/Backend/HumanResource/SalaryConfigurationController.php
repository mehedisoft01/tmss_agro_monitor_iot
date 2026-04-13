<?php

namespace App\Http\Controllers\Backend\HumanResource;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\HumanResource\Salary;
use App\Models\HumanResource\SalaryConfiguration;
use App\Models\HumanResource\Salesman;
use Illuminate\Http\Request;
class SalaryConfigurationController extends Controller
{
    use Helper;

    public function __construct()
    {
        $this->model = new SalaryConfiguration();
    }
    public function index()
    {
        if (!can('district.index')) {
            return $this->notPermitted();
        }
        try {
            $keyword = request()->input('keyword');


            $data = $this->model
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where('name', 'Like', "%$keyword%");
                })
                ->paginate(input('perPage'));

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
        $user = auth()->user();
        $key = isset($input['salesman']['key']) ? $input['salesman']['key'] : 'salesman';
        $warehouseId = request()->input('warehouseId');

        $salesman = Salesman::selectRaw("salesmen.*,
        CASE WHEN salary_configurations.basic_salary IS NULL
            THEN 0 ELSE salary_configurations.basic_salary END as basic_salary,
        CASE WHEN salary_configurations.daily_salary IS NULL
            THEN 0 ELSE salary_configurations.daily_salary END as daily_salary,
        CASE WHEN salary_configurations.hourly_salary IS NULL
            THEN 0 ELSE salary_configurations.hourly_salary END as hourly_salary,
        CASE WHEN salary_configurations.allowance IS NULL
            THEN 0 ELSE salary_configurations.allowance END as allowance,
        CASE WHEN salary_configurations.is_salesman IS NULL
            THEN 0 ELSE salary_configurations.is_salesman END as is_salesman,
        CASE WHEN salary_configurations.is_commission_applicable IS NULL
            THEN 0 ELSE salary_configurations.is_commission_applicable END as is_commission_applicable,
        staff_designations.designation_name")
            ->leftJoin('warehouses', 'warehouses.id', '=', 'salesmen.warehouse_id')
            ->leftJoin('staff_designations', 'staff_designations.id', '=', 'salesmen.designation_id')
            ->leftJoin('salary_configurations', 'salary_configurations.salesman_id', '=', 'salesmen.id')
            ->where('salesmen.status', 1)
            ->when($warehouseId, function ($query) use ($warehouseId) {
                $query->where('salesmen.warehouse_id', $warehouseId);
            })
            ->where(function ($query) use ($user) {
                if ($user->division_id){
                    $query->where('warehouses.division_id', $user->division_id);
                }
                if ($user->district_id){
                    $query->where('warehouses.district_id', $user->district_id);
                }
                if ($user->warehouse_id){
                    $query->where('warehouses.id', $user->warehouse_id);
                }
            })
            ->orderBy('salesmen.id', 'ASC')
            ->get();

        return returnData(2000, $salesman );
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

            $validate = $this->model->validate($input);
            if ($validate->fails()) {
                return returnData(5000, $validate->errors()->first(), 'Validation Error..!!');
            }

            foreach ($request->salesman as $salesman) {

                SalaryConfiguration::updateOrCreate(
                    ['salesman_id' => $salesman['id']],
                    [
                        'basic_salary' => $salesman['basic_salary'] ?? 0,
                        'daily_salary' => $salesman['daily_salary'] ?? 0,
                        'hourly_salary' => $salesman['hourly_salary'] ?? 0,
                        'allowance' => $salesman['allowance'] ?? 0,
                        'is_salesman' => $salesman['is_salesman'] ?? 0,
                        'is_commission_applicable' => $salesman['is_commission_applicable'] ?? 0,
                        'user_id' => $auth->id
                    ]
                );
            }

            return returnData(2000, null, 'Salary Structure Added Successfully..!!');

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
                $checkSalary = Salary::where('salesman_id', $data->id)->first();

                if ($checkSalary){
                    return returnData(5000, nullOrEmptyString(), 'Already Salary Generated, Cannot Delete..!!');
                }

                $data->delete();
                $this->model->where('salesman_id', $data->salesman_id)
                    ->update(['status' => 0]);
                return returnData(2000, $data, 'Successfully Deleted & Previous Reactivated');
            }
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }
}

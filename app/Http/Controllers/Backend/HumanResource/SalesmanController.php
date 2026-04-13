<?php

namespace App\Http\Controllers\Backend\HumanResource;

use App\Helpers\Helper;
use App\Models\HumanResource\AssignTerritory;
use App\Models\HumanResource\Salary;
use App\Models\HumanResource\SalesmanFile;
use App\Models\HumanResource\TargetProduct;
use App\Models\Inventory\Warehouse;
use App\Models\Sales\Invoice;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\HumanResource\Salesman;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SalesmanController extends Controller
{
    use Helper;

    public function __construct()
    {
        $this->model = new Salesman();
    }

    public function index()
    {
        if (!can('staff_information.index')) {
            return $this->notPermitted();
        }
        try {
            $keyword = request()->input('keyword');
            $warehouseId = request()->input('warehouse_id');
            $salesman = request()->input('salesman');
            $perPage = request()->input('per_page');
            $user = auth()->user();
            $isSalesman = $this->isSalesManger();
            $data = $this->model->with('warehouse:id,warehouse_name')
//                ->when($keyword, function ($query) use ($keyword) {
//                    $query->where('name', 'Like', "%$keyword%");
//                })
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%")
                            ->orWhere('salesman_code', 'like', "%{$keyword}%");
                    });
                })
                ->when($warehouseId, function ($query) use ($warehouseId) {
                    $query->where('warehouse_id', $warehouseId); // exact match
                })
                ->when($salesman, function ($query) use ($salesman) {
                    $query->where(function ($q) use ($salesman) {
                        $q->where('email', 'like', "%{$salesman}%")
                            ->orWhere('phone', 'like', "%{$salesman}%");
                    });
                })
                ->where(function ($query) use ($user) {
                    if ($user->warehouse_id){
                        $query->where('warehouse_id', $user->warehouse_id);
                    }
                })
                ->when($isSalesman, function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->orderBy('id', 'DESC')
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!can('staff_information.store')) {
            return $this->notPermitted();
        }
        try {
            $auth = auth()->user();
            $input = $request->all();
            $input['user_id'] = $auth->id;
            $input['status'] = $request->status ?? 1;

            $validate = $this->model->validate($input);
            if ($validate->fails()) {
                return returnData(5000, $validate->errors()->first(), 'Validation Error..!!');
            }

            DB::beginTransaction();

            $input['salesman_code'] = generateUniqueCode('SLR', 'salesman_code', 'salesmen');
            $salesman = $this->model->create($input);


            $warehouse = $request->input('warehouse_id') ? Warehouse::where('id', $request->input('warehouse_id'))->first() : null;

            User::insert([
                'salesman_id' => $salesman->id,
                'warehouse_id' => $request->input('warehouse_id'),
                'name' => $request->input('name'),
                'username' => $request->input('username'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'designation' => $request->input('designation_id'),
                'image' => $request->input('photo'),
                'password' => Hash::make($request->input('password')),
                'role_id' => $request->input('role_id'),
                'division_id' => $warehouse ? $warehouse->division_id : $request->input('division_id'),
                'district_id' => $warehouse ? $warehouse->district_id :  $request->input('district_id')
            ]);

            if (isset($request->salesmanFiles) && is_array($request->salesmanFiles)) {
                foreach ($request->salesmanFiles as $row) {
                    SalesmanFile::create([
                        'salesman_id' => $salesman->id,
                        'photo' => $row['photo'] ?? null,
                        'document' => $row['document'] ?? null,
                        'description' => $row['description'] ?? null,
                        'signature' => $row['signature'] ?? null,
                        'sil' => $row['sil'] ?? null,
                    ]);
                }
            }

            DB::commit();

            return returnData(2000, $salesman, 'Salesmen Added Successfully..!!');
        } catch (\Exception $exception) {
            DB::rollBack();
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        if (!can('staff_information.show')) {
            return $this->notPermitted();
        }
        try {
            $data = $this->model->find($id);
            if (!$data) {
                return returnData(5000, [], 'Data Not Found..!!');
            }
            return returnData(2000, $data);
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */

    public function edit($id)
    {
        if (!can('staff_information.edit')) {
            return $this->notPermitted();
        }
        try {
            $data = $this->model->with(['user', 'files'])->find($id);

            if (!$data) {
                return returnData(5000, [], 'Data Not Found..!!');
            }
            if ($data->user) {
                $data->name = $data->user->name;
                $data->username = $data->user->username;
                $data->role_id = $data->user->role_id;
                $data->warehouse_id = $data->user->warehouse_id;
                $data->designation_id = $data->user->designation;
            }

            return returnData(2000, $data);
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Something Went Wrong..!!');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        if (!can('staff_information.update')) {
            return $this->notPermitted();
        }
        try {
            $salesman = $this->model->find($id);
            if (!$salesman) {
                return returnData(5000, [], 'Salesman Not Found..!!');
            }
            $input = $request->all();
            $validate = $this->model->validate($input, $id);
            if ($validate->fails()) {
                return returnData(5000, $validate->errors()->first(), 'Validation Error..!!');
            }
            $salesman->update($input);

            $user = User::where('salesman_id', $id)->first();

            $warehouse = $request->input('warehouse_id') ? Warehouse::where('id', $request->input('warehouse_id'))->first() : null;

            $userData = [
                'salesman_id' => $salesman->id,
                'warehouse_id' => $request->input('warehouse_id'),
                'name' => $request->input('name'),
                'username' => $request->input('username'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'designation' => $request->input('designation_id'),
                'image' => $request->input('photo'),
                'password' => Hash::make($request->input('password')),
                'role_id' => $request->input('role_id'),
                'division_id' => $warehouse ? $warehouse->division_id : $request->input('division_id'),
                'district_id' => $warehouse ? $warehouse->district_id :  $request->input('district_id')
            ];
            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }

            if ($user) {
                $user->update($userData);
            }else{
                User::insert($userData);
            }

            // 4. Update Files (Simplest approach: delete old and re-add or sync)
            if (isset($request->salesmanFiles) && is_array($request->salesmanFiles)) {
                SalesmanFile::where('salesman_id', $id)->delete(); // Clear old records
                foreach ($request->salesmanFiles as $row) {
                    SalesmanFile::create([
                        'salesman_id' => $salesman->id,
                        'photo' => $row['photo'] ?? null,
                        'document' => $row['document'] ?? null,
                        'description' => $row['description'] ?? null,
                        'signature' => $row['signature'] ?? null,
                        'sil' => $row['sil'] ?? null,
                    ]);
                }
            }

            return returnData(2000, $salesman, 'Salesman Updated Successfully..!!');
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        if (!can('staff_information.destroy')) {
            return $this->notPermitted();
        }
        try {
            $data = $this->model->find($id);
            if (!$data) {
                return returnData(5000, [], 'Data Not Found..!!');
            }

            $invoice = Invoice::where('salesman_id', $id)->exists();
            $targetProduct = TargetProduct::where('salesman_id', $id)->exists();
            $assignTerritory = AssignTerritory::where('salesman_id', $id)->exists();
            $salary = Salary::where('salesman_id', $id)->exists();

            if ($invoice || $targetProduct || $assignTerritory || $salary) {
                return returnData(3000, null, 'This Salesman is in use and cannot be deleted.');
            }

            $data->delete();
            return returnData(2000, [], 'Salesman Deleted Successfully..!!');
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }
}

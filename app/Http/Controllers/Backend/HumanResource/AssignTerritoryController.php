<?php

namespace App\Http\Controllers\Backend\HumanResource;

use App\Helpers\Helper;
use App\Models\HumanResource\TargetProduct;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\HumanResource\AssignTerritory;
use App\Models\HumanResource\Salesman;
use Illuminate\Support\Facades\DB;

class AssignTerritoryController extends Controller
{
    use Helper;

    public function __construct()
    {
        $this->model = new AssignTerritory();
    }
    public function index()
    {
        if (!can('assign_targets.index')) {
            return $this->notPermitted();
        }
        try {
            $keyword = request()->input('keyword');
            $warehouseId = request()->input('warehouse_id');
            $salesmanId = request()->input('salesman_id');
            $perPage = request()->input('per_page');
            $user = auth()->user();
            $isSalesman = $this->isSalesManger();

            $data = $this->model
                ->with('sales_man:id,name', 'warehouse:id,warehouse_name','assign_target.product')
                ->leftJoin('divison', 'assign_territories.division_id', '=', 'divison.id')
                ->leftJoin('district', 'assign_territories.district_id', '=', 'district.id')
                ->leftJoin('thana', 'assign_territories.upazila_id', '=', 'thana.id')
                ->select(
                    'assign_territories.*',
                    'divison.division_name',
                    'district.district_name',
                    'thana.upazila_name'
                )
                ->checkWarehouse()
                ->where('assign_territories.status', 1)
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->whereHas('sales_man', function ($s) use ($keyword) {
                            $s->where('name', 'LIKE', "%{$keyword}%");
                        })->orWhereHas('warehouse', function ($w) use ($keyword) {
                            $w->where('warehouse_name', 'LIKE', "%{$keyword}%");
                        });
                    });
                })
                ->when($warehouseId, function ($query) use ($warehouseId) {
                    $query->where('warehouse_id', 'like', "%$warehouseId%");
                })
                ->when($salesmanId, function ($query) use ($salesmanId) {
                    $query->where('salesman_id', 'like', "%$salesmanId%");
                })
                ->where(function ($query) use ($user) {
                    if ($user->division_id){
                        $query->where('assign_territories.division_id', $user->division_id);
                    }
                    if ($user->district_id){
                        $query->where('assign_territories.district_id', $user->district_id);
                    }
                    if ($user->warehouse_id){
                        $query->where('assign_territories.warehouse_id', $user->warehouse_id);
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
        if (!can('assign_targets.store')) {
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

            if (!isset($request->assigned_product) || !is_array($request->assigned_product)) {
                return returnData(5000, null, 'You Must Select Some Product First..!!');
            }

            DB::beginTransaction();

            $input['salesman_code'] = generateUniqueCode('SLR', 'salesman_code', 'salesmen');
            $data = $this->model->create($input);

            foreach ($request->assigned_product as $row) {
                $taregtPrd = new TargetProduct();
                $taregtPrd->fill([
                    'assing_territories_id'=> $data->id,
                    'salesman_id' => $data->salesman_id,
                    'product_id'       => $row['product_id'],
                    'target_qty'    => $row['target_qty'] ??  0,
                    'target_type'    => $row['target_type'] ?? 0,
                    'from_amount' => $row['from_amount'] ?? 0,
                    'to_amount' => $row['to_amount'] ?? 0,
                    'commission' => $row['commission'] ?? 0,
                ]);
                $taregtPrd->save();
            }
            DB::commit();

            return returnData(2000, $data, 'Salesmen Added Successfully..!!');
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        if (!can('assign_targets.show')) {
            return $this->notPermitted();
        }
        try {
            $data = $this->model
                ->with('sales_man', 'warehouse', 'assign_target.product','district','division','upazila')
                ->find($id);
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
        $data = $this->model->with('assign_target')->find($id);

        if (!$data) {
            return returnData(5000, [], 'Data Not Found..!!');
        }

        return returnData(2000, $data);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        if (!can('assign_targets.update')) {
            return $this->notPermitted();
        }
        try {
            $data = $this->model->find($id);
            if (!$data) {
                return returnData(5000, [], 'Data Not Found');
            }
            $validate = $this->model->validate($request->all());
            if ($validate->fails()) {
                return returnData(5000, $validate->errors()->first());
            }
            $data->update($request->all());
            $incomingIds = collect($request->assigned_product)->pluck('id')->filter()->toArray();

            foreach ($request->assigned_product as $row) {
                TargetProduct::updateOrCreate(
                    [
                        'id' => $row['id'] ?? null
                    ],
                    [
                        'assing_territories_id'=> $data->id,
                        'salesman_id' => $data->salesman_id,
                        'product_id' => $row['product_id'],
                        'target_qty' => $row['target_qty'],
                        'from_amount' => $row['from_amount'],
                        'to_amount' => $row['to_amount'],
                        'target_type' => $row['target_type'],
                        'commission' => $row['commission'],
                    ]
                );
            }

            return returnData(2000, $data, 'Updated Successfully');

        } catch (\Exception $e) {
            return returnData(5000, $e->getMessage());
        }
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        if (!can('assign_targets.destroy')) {
            return $this->notPermitted();
        }
        try {
            $data = $this->model->find($id);
            if (!$data) {
                return returnData(5000, [], 'Data Not Found..!!');
            }
            $data->assign_target()->delete();
            $data->delete();
            return returnData(2000, [], 'Salesman Deleted Successfully..!!');
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }




    public function getSalesmanWarehouse($salesmanId)
    {
        $salesman = Salesman::with('warehouse')->find($salesmanId);

        if (!$salesman) {
            return returnData(5000, [], 'Salesman Not Found');
        }

        $wh = $salesman->warehouse;

        return returnData(2000, [
            'warehouse_id' => $wh->id ?? null,
            'division_id'  => $wh->division_id ?? null,
            'district_id'  => $wh->district_id ?? null,
            'upazila_id'   => $wh->upazila_id ?? null,
            'area'         => $wh->area ?? '',
        ]);
    }

}

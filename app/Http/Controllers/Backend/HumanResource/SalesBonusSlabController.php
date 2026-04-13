<?php

namespace App\Http\Controllers\Backend\HumanResource;

use App\Helpers\Helper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\HumanResource\AssignTerritory;
use App\Models\HumanResource\SalesBonusSlab;
use App\Models\HumanResource\Salesman;

class SalesBonusSlabController extends Controller
{
    use Helper;

    public function __construct()
    {
        $this->model = new SalesBonusSlab();
    }
    public function index()
    {
        try {
            $keyword = request()->input('keyword');
            $bonusType = request()->input('bonus_type');

            $data = $this->model
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where('slab_min_percent', 'Like', "%$keyword%");
                })
                ->when($bonusType, function ($query) use ($bonusType) {
                    $query->where('bonus_type', 'Like', "%$bonusType%");
                })
               ->paginate(input('perPage'));

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

        try {
            $auth = auth()->user();
            $input = $request->all();
            $input['warehouse_id'] = ($auth->warehouse_id != 0) ? $auth->warehouse_id : null;
            $input['user_id'] = $auth->id;

            $bonusSlab = $request->input('bonusSlab');

            foreach ($bonusSlab as $index => $slab) {
                $validate = $this->model->validate($slab);
                if ($validate->fails()) {
                    return returnData(5000, "Row " . ($index + 1) . ": " . $validate->errors()->first(), 'Validation Error..!!');
                }
            }

            $createdData = [];
            foreach ($bonusSlab as $slab) {
                $slab['user_id'] = $input['user_id'];
                $slab['warehouse_id'] = $input['warehouse_id'];
                $data = $this->model->create($slab);
                $createdData[] = $data;
            }
            return returnData(2000, $createdData, 'Data Added Successfully..!!');
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
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
    public function edit(string $id)
    {
        try {
            $bonusSlab = $this->model->find($id);

            if (!$bonusSlab) {
                return returnData(4040, null, 'Bonus slab not found..!!');
            }
            $data = [
                'id' => $bonusSlab->id,
                'bonusSlab' => [
                    [
                        'id' => $bonusSlab->id,
                        'bonus_type' => $bonusSlab->bonus_type,
                        'slab_min_percent' => $bonusSlab->slab_min_percent,
                        'slab_max_percent' => $bonusSlab->slab_max_percent,
                        'bonus_amount' => $bonusSlab->bonus_amount
                    ]
                ]
            ];

            return returnData(2000, $data);
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $input = $request->input('bonusSlab')[0];

            $data = $this->model->find($id);
            if (!$data) {
                return returnData(5000, [], 'Data Not Found..!!');
            }

            $data->update([
                'bonus_type' => $input['bonus_type'],
                'slab_min_percent' => $input['slab_min_percent'],
                'slab_max_percent' => $input['slab_max_percent'],
                'bonus_amount' => $input['bonus_amount']
            ]);

            return returnData(2000, $data, 'Data Updated Successfully..!!');
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $data = $this->model->find($id);
            if (!$data) {
                return returnData(5000, [], 'Data Not Found..!!');
            }
            $data->delete();
            return returnData(2000, [], 'Data Deleted Successfully..!!');
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }
}

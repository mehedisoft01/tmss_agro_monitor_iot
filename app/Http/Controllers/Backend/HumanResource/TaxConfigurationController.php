<?php

namespace App\Http\Controllers\Backend\HumanResource;

use App\Helpers\Helper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\HumanResource\TaxConfiguration;

class TaxConfigurationController extends Controller
{
    use Helper;

    public function __construct()
    {
        $this->model = new TaxConfiguration();
    }
    public function index()
    {
        try {
            $keyword = request()->input('keyword');
            $userId = auth()->id();
            $lowerUserIds = myLowerUserIds();
            $data = $this->model
                ->checkWarehouse()
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where('tax_year', 'Like', "%$keyword%");
                })
                ->where(function ($q) use ($userId, $lowerUserIds) {
                    $q->where('user_id', $userId)
                        ->orWhereIn('user_id', $lowerUserIds);
                })->paginate(input('perPage'));

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
            $input['warehouse_id'] = ($auth->warehouse_id != 0) ? $auth->warehouse_id : null;
            $input['user_id'] = $auth->id;

            $taxSlabs = $request->input('taxSlab');

            foreach ($taxSlabs as $index => $slab) {
                $validate = $this->model->validate($slab);
                if ($validate->fails()) {
                    return returnData(5000, "Row " . ($index + 1) . ": " . $validate->errors()->first(), 'Validation Error..!!');
                }
            }

            $createdData = [];
            foreach ($taxSlabs as $slab) {
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
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // dd($id);
        try {
            $taxSlab = $this->model->find($id);

            if (!$taxSlab) {
                return returnData(4040, null, 'Tax slab not found..!!');
            }
            $data = [
                'id' => $taxSlab->id,
                'taxSlab' => [
                    [
                        'id' => $taxSlab->id,
                        'tax_year' => $taxSlab->tax_year,
                        'slab_min' => $taxSlab->slab_min,
                        'slab_max' => $taxSlab->slab_max,
                        'rate_percent' => $taxSlab->rate_percent
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
            $input = $request->input('taxSlab')[0];

            $data = $this->model->find($id);
            if (!$data) {
                return returnData(5000, [], 'Data Not Found..!!');
            }

            $data->update([
                'tax_year' => $input['tax_year'],
                'slab_min' => $input['slab_min'],
                'slab_max' => $input['slab_max'],
                'rate_percent' => $input['rate_percent']
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

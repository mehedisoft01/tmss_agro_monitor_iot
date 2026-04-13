<?php

namespace App\Http\Controllers\Backend\Accounting;

use App\Helpers\Helper;
use Illuminate\Http\Request;
use App\Models\ChartOfAccounts;
use App\Http\Controllers\Controller;

class ChartOfAccountController extends Controller
{

    use Helper;

    public function __construct()
    {
        $this->model = new ChartOfAccounts();
    }

    public function index()
    {
        try {
            $keyword = request()->input('keyword');
            $data = $this->model
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where('name', 'Like', "%$keyword%");
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
            $input = $request->all();
            $validate = $this->model->validate($input);
            if ($validate->fails()) {
                return returnData(5000, $validate->errors()->first(), 'Validation Error..!!');
            }
            $data = $this->model->create($input);
            return returnData(2000, $data, 'Chart Added Successfully..!!');
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
        try {
            $input = $request->all();
            $validate = $this->model->validate($input);
            if ($validate->fails()) {
                return returnData(5000, $validate->errors()->first(), 'Validation Error..!!');
            }
            $data = $this->model->find($id);
            if (!$data) {
                return returnData(5000, [], 'Data Not Found..!!');
            }

            $data->update($input);
            return returnData(2000, $data, 'Updated Successfully..!!');
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
            return returnData(2000, [], 'Deleted Successfully..!!');
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }
}

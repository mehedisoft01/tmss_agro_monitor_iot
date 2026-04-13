<?php

namespace App\Http\Controllers\Backend\ProductManagement;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductDocument;
use Illuminate\Http\Request;

class ProductDocumentController extends Controller
{
    use Helper;

    public function __construct()
    {
        if (!can(request()->route()->action['as'])) {
            return returnData(5001, null, 'You are not authorized to access this page');
        }
        $this->model = new ProductDocument();
    }

    public function index()
    {
        if (!can('product_document.index')) {
            return $this->notPermitted();
        }

        try {
            $keyword = request()->input('keyword');
            $productId = request()->input('product_id');
            $perPage = request()->input('per_page');

            $data = $this->model
                ->checkWarehouse()
                ->with(['product'])
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where('document_name', 'like', "%$keyword%");
                })
                ->when($productId, function ($query) use ($productId) {
                    $query->where('product_id', 'like', "%$productId%");
                })
                ->orderBy('id','DESC')
                ->paginate($perPage);

            return returnData(2000, $data);
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }



    public function store(Request $request)
    {
        if (!can('product_document.store')) {
            return $this->notPermitted();
        }
        try {
            $auth = auth()->user();
            $input = $request->all();
            $input['warehouse_id'] = ($auth->warehouse_id != 0) ? $auth->warehouse_id : null;
            $input['user_id'] = $auth->id;

            $validate = $this->model->validate($input);
            if ($validate->fails()) {
                return returnData(3000, $validate->errors());
            }


            if (isset($input['file_path']) && !empty($input['file_path'])) {
                $input['file_path'] = json_encode($input['file_path']);
            } else {
                $input['file_path'] = json_encode([]);
            }

            $this->model->fill($input);
            $this->model->save();

            return returnData(2000, null, 'Successfully Inserted');

        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }


    public function show($id)
    {
        if (!can('product_document.show')) {
            return $this->notPermitted();
        }

        try {
            $data = $this->model
                ->with('product')
                ->findOrFail($id);

            $data->file_path = json_decode($data->file_path, true);

            return returnData(2000, $data);
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Record not found or something went wrong.');
        }
    }



    public function edit($id)
    {
        $data = $this->model->find($id);

        if (!$data) {
            return returnData(5000, [], 'Data Not Found..!!');
        }

        $data->file_path = json_decode($data->file_path, true);

        return returnData(2000, $data);
    }


    public function update(Request $request, $id)
    {
        if (!can('product_document.update')) {
            return $this->notPermitted();
        }
        try {
            $input = $request->all();

            // logged in user_id override
            $input['user_id'] = auth()->id();

            $validation = $this->model->validate($input);
            if ($validation->fails()) {
                return response()->json(['status' => 3000, 'errors' => $validation->errors()], 300);
            }

            if (isset($input['file_path']) && !empty($input['file_path'])) {
                $input['file_path'] = json_encode($input['file_path']);
            } else {
                $input['file_path'] = json_encode([]);
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

    public function destroy(Request $request, $id)
    {
        if (!can('product_document.destroy')) {
            return $this->notPermitted();
        }
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

    public function multiple(Request $request)
    {
        if (!can('set_markup_pricing.destroy')) {
            return $this->notPermitted();
        }
        try{
            $selectedKeys = $request->input('selectedKeys', []);
            $ids = is_array($selectedKeys) ? $selectedKeys : [];

            if (empty($ids)) {
                return returnData(4000, null, 'No IDs provided');
            }
            $deleted = [];
            $errors = [];

            foreach ($ids as $id) {
                $data = ProductDocument::where('id', $id)->first();

                if (!$data) {
                    $errors[] = "ID {$id} not found";
                    continue;
                }

                $data->delete();
                $deleted[] = $id;
            }

            return returnData(2000, null,count($deleted)." Item Deleted and ".json_encode($errors)." Item Not Deleted");
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }

    }
}

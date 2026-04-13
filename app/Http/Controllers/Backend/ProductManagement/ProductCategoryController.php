<?php

namespace App\Http\Controllers\Backend\ProductManagement;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    use Helper;

    public function __construct()
    {
        if (!can(request()->route()->action['as'])) {
            return returnData(5001, null, 'You are not authorized to access this page');
        }
        $this->model = new ProductCategory;
    }


    public function index()
    {
        if (!can('product_categories.index')) {
            return $this->notPermitted();
        }

        try {
            $keyword = request()->input('keyword');

            $data = $this->model
                ->checkWarehouse()
                ->whereNull('parent_id')
                ->with(['subcategories.parent_category'])
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where('category_name', 'like', "%$keyword%");
                })
                ->orderBy('id', 'DESC')
                ->paginate(request('perPage'));

            $data->getCollection()->transform(function ($category) {
                foreach ($category->subcategories as $sub) {
                    $sub->parent_name = optional($sub->parent_category)->category_name ?? '-';
                }
                return $category;
            });

            return returnData(2000, $data);
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }



    public function store(Request $request)
    {
        if (!can('product_categories.store')) {
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

            $this->model->fill($input);
            $this->model->save();

            return returnData(2000, null, 'Successfully Inserted');

        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }



    public function update(Request $request, $id)
    {
        if (!can('product_categories.update')) {
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


    public function destroy($id)
    {
        if (!can('product_categories.destroy')) {
            return $this->notPermitted();
        }
        try {
            $data = $this->model->where('id', $id)->first();
            if (!$data) {
                return returnData(5000, null, 'Data Not found');
            }

            $productCategory = Product::where('parent_id', $id)
                ->orWhere('sub_category_id', $id)
                ->orWhere('sub_sub_category_id', $id)
                ->exists();

            if($productCategory){
                return returnData(4000,null,'There are products in this category, cannot be deleted.');
            }

            $data->delete();

            return returnData(2000, $data, 'Successfully Deleted');

        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }
}


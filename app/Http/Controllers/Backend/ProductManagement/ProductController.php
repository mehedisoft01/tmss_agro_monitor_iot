<?php

namespace App\Http\Controllers\Backend\ProductManagement;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Inventory\Stock;
use App\Models\Inventory\StockAdjustment;
use App\Models\Inventory\StockPurchase;
use App\Models\ProductManagement\Product;
use App\Models\Sales\InvoiceItem;
use App\Models\Sales\OrderItem;
use App\ProductStock\ProductStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    use Helper;

    public function __construct()
    {
//        if (!can(request()->route()->action['as'])) {
//            return returnData(5001, null, 'You are not authorized to access this page');
//        }
        $this->model = new Product();
    }


    public function index()
    {
        if (!can('product_list.index')) {
            return $this->notPermitted();
        }

        try {
            $keyword = request()->input('keyword');
            $user = auth()->user();
            $perPage = request()->input('per_page');
            $parentId = request()->input('parent_id');
            $subCategoryId = request()->input('sub_category_id');
            $subSubCategoryId = request()->input('sub_sub_category_id');

            $data = $this->model->with(['parentCategory', 'subCategory', 'subSubCategory', 'brand'])
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('product_name', 'like', "%{$keyword}%")
                            ->orWhere('product_code', 'like', "%{$keyword}%");
                    });
                })
                ->when($parentId, function ($query) use ($parentId) {
                    $query->where('parent_id', 'like', "%$parentId%");
                })
                ->when($subCategoryId, function ($query) use ($subCategoryId) {
                    $query->where('sub_category_id', 'like', "%$subCategoryId%");
                })
                ->when($subSubCategoryId, function ($query) use ($subSubCategoryId) {
                    $query->where('sub_sub_category_id', 'like', "%$subSubCategoryId%");
                })
//                ->where(function ($query) use ($user) {
//                    if ($user->division_id){
//                        $query->where('division_id', $user->division_id);
//                    }
//                    if ($user->district_id){
//                        $query->where('district_id', $user->district_id);
//                    }
//                    if ($user->warehouse_id){
//                        $query->where('warehouse_id', $user->warehouse_id);
//                    }
//                })
                ->orderBy('id', 'DESC')
                ->paginate($perPage);

            return returnData(2000, $data);
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }



    public function store(Request $request)
    {
//        ddA($request);
        if (!can('product_list.store')) {
            return $this->notPermitted();
        }

        try {
            $auth = auth()->user();
            $input = $request->all();
            $input['user_id'] = $auth->id;

            if (empty($input['parent_id'])) {
                return returnData(5000, null, 'Please select a Category');
            }

            $validate = $this->model->validate($input);
            if ($validate->fails()) {
                return returnData(3000, $validate->errors());
            }


            if (isset($input['image']) && !empty($input['image'])) {
                $input['image'] = json_encode($input['image']);
            } else {
                $input['image'] = json_encode([]);
            }
            if (isset($input['custom_document_file']) && !empty($input['custom_document_file'])) {
                $input['custom_document_file'] = json_encode($input['custom_document_file']);
            } else {
                $input['custom_document_file'] = json_encode([]);
            }
            DB::beginTransaction();

            $this->model->fill($input);
            $this->model->save();
            $getId = $this->model->id;

//            if (
//                isset($input['stock_quantity']) &&
//                isset($input['cost_price']) &&
//                $input['stock_quantity'] > 0 &&
//                $input['cost_price'] > 0
//            ) {
//                StockPurchase::create([
//                    'purchase_date' => now(),
//                    'user_id'       => $auth->id,
//                    'warehouse_id'  => $input['warehouse_id'],
//                    'product_id'    => $getId,
//                    'product_code' =>$input['product_code'],
//                    'purchase_qty'  => $input['stock_quantity'],
//                    'total_qty'  => $input['stock_quantity'],
//                    'unit_cost'     => $input['cost_price'],
//                    'sub_total'     => $input['stock_quantity'] * $input['cost_price'],
//                    'grand_total'   => $input['stock_quantity'] * $input['cost_price'],
//                    'open_stock_status'   => 'Opening Stock',
//                ]);
//            }

//            $getData = DB::table('products')
//                ->where('warehouse_id', $input['warehouse_id'])
//                ->where('product_code', $input['product_code'])
//                ->get();
//
//            ProductStockService::initStock(
//                (object)[
//                    'id' => $getId,
//                    'warehouse_id' =>$input['warehouse_id'],
//                    'product_code' =>$input['product_code'],
//                    'product_id' =>$getId,
//                    'parent_id' =>$input['parent_id'],
//                    'sub_category_id' =>$input['sub_category_id'],
//                    'sub_sub_category_id' =>$input['sub_sub_category_id'] ?? 0,
//                    'cost_price' =>$getData->sum('cost_price'),
//                    'current_stock' =>$getData->sum('stock_quantity'),
//                    'alert_quantity' => $input['alert_quantity'] ?? 0,
//                    'user_id' => $auth->id,
//                ],
//                1,
//                $auth->id,
//                'Stock Purchase'
//            );

            DB::commit();

            return returnData(2000, null, 'Successfully Inserted');
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }



    public function show($id)
    {
        if (!can('product_list.show')) {
            return $this->notPermitted();
        }

        try {
            $data = $this->model
                ->with(['parentCategory', 'subCategory', 'subSubCategory', 'brand','productUnit','saleUnit'])
                ->findOrFail($id);


            $stock = Stock::where('product_id', $id)
                ->where('status', 1)
                ->latest('id')
                ->first();

            $data->selling_price = $stock ? $stock->selling_price : 'Not Set';
            $data->dealer_price  = $stock ? $stock->dealer_price : 'Not Set';

            $data->image = json_decode($data->image, true);
            $data->custom_document_file = json_decode($data->custom_document_file, true);

            return returnData(2000, $data);
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Record not found or something went wrong.');
        }
    }



    public function edit($id)
    {
        if (!can('product_list.edit')) {
            return $this->notPermitted();
        }

        try {
            $data = $this->model
                ->with(['parentCategory', 'subCategory', 'subSubCategory'])
                ->findOrFail($id);

            $data->image = json_decode($data->image, true);
            $data->custom_document_file = json_decode($data->custom_document_file, true);

            return returnData(2000, $data);
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }




    public function update(Request $request, $id)
    {
        if (!can('product_list.update')) {
            return $this->notPermitted();
        }
        try {
            $input = $request->all();
            $auth = auth()->user();
            // logged in user_id override
            $input['user_id'] = auth()->id();

            $validation = $this->model->validate($input);
            if ($validation->fails()) {
                return response()->json(['status' => 3000, 'errors' => $validation->errors()], 300);
            }

            if (isset($input['image']) && !empty($input['image'])) {
                $input['image'] = json_encode($input['image']);
            } else {
                $input['image'] = json_encode([]);
            }
            if (isset($input['custom_document_file']) && !empty($input['custom_document_file'])) {
                $input['custom_document_file'] = json_encode($input['custom_document_file']);
            } else {
                $input['custom_document_file'] = json_encode([]);
            }
            DB::beginTransaction();

            $data = $this->model->find($id);

            if ($data) {
                $data->update($input);

                $getId = $data->id;

//                if (
//                    isset($input['stock_quantity']) &&
//                    isset($input['cost_price']) &&
//                    $input['stock_quantity'] > 0 &&
//                    $input['cost_price'] > 0
//                ) {
//                    StockPurchase::updateOrCreate(
//                        [
//                            'product_id'   => $getId,
//                            'warehouse_id' => $input['warehouse_id'],
//                        ],
//                        [
//                        'purchase_date' => now(),
//                        'user_id'       => $auth->id,
//                        'warehouse_id'  => $input['warehouse_id'],
//                        'product_id'    => $getId,
//                        'product_code' =>$input['product_code'],
//                        'purchase_qty'  => $input['stock_quantity'],
//                        'total_qty'  => $input['stock_quantity'],
//                        'unit_cost'     => $input['cost_price'],
//                        'sub_total'     => $input['stock_quantity'] * $input['cost_price'],
//                        'grand_total'   => $input['stock_quantity'] * $input['cost_price'],
//                        'open_stock_status'   => 'Opening Stock',
//                        ]
//                    );
//                }

//                $getData = DB::table('products')
//                    ->where('warehouse_id', $input['warehouse_id'])
//                    ->where('product_code', $input['product_code'])
//                    ->get();
//
//                ProductStockService::initStock((object)[
//                        'warehouse_id' =>$input['warehouse_id'],
//                        'product_code' =>$input['product_code'],
//                        'product_id' =>$getId,
//                        'parent_id' =>$input['parent_id'],
//                        'sub_category_id' =>$input['sub_category_id'],
//                        'sub_sub_category_id' =>$input['sub_sub_category_id'] ?? 0,
//                        'cost_price' =>$getData->sum('cost_price'),
//                        'current_stock' =>$getData->sum('stock_quantity'),
//                        'alert_quantity' => $input['alert_quantity'] ?? 0,
//                    ], 1, $auth->id, 'Stock Purchase');

            DB::commit();

                return returnData(2000, null, 'Successfully Updated');
            }
            return returnData(2000, null, 'Unsuccessful Updated');
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }



    public function destroy($id)
    {
        if (!can('product_list.destroy')) {
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
                $data = Product::where('id', $id)->first();

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

    public function expiryAlertList()
    {
        try {
            $keyword = request()->input('keyword');

            $today = now()->toDateString();
            $alertDate = now()->addDays(30)->toDateString();

            $query = Stock::with(['product','warehouse'])
                ->where('current_stock', '>', 0)
                ->whereHas('product', function ($q) use ($today, $alertDate) {
                    $q->whereBetween('expire_date', [$today, $alertDate]);
                });
            if ($keyword) {
                $query->whereHas('product', function ($q) use ($keyword) {
                    $q->where('product_name', 'LIKE', "%{$keyword}%")
                        ->orWhere('product_code', 'LIKE', "%{$keyword}%");
                });
            }
            $data = $query->paginate(request('perPage'));

            // 🧠 extra fields
            $data->getCollection()->transform(function ($item) {
                $expiryDate = \Carbon\Carbon::parse($item->product->expire_date);
                $daysLeft = now()->diffInDays($expiryDate, false);

                $item->expiry_date = $expiryDate->format('Y-m-d');
                $item->days_left = $daysLeft;
                $item->expiry_status = $daysLeft < 0 ? 'Expired' : 'Near Expiry';

                return $item;
            });

            return returnData(2000, $data);

        } catch (\Exception $e) {
            return returnData(5000, $e->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }

    public function publicQr($id)
    {
        $product = Product::with(['brand', 'productUnit', 'saleUnit'])->find($id);

        if (!$product) {
            return view('product_qr', ['product' => null]);
        }

        $stock = Stock::where('product_id',$id)
            ->where('status',1)
            ->latest('id')
            ->first();

        return view('product_qr', ['product' => $product,'stock' => $stock]);
    }

}

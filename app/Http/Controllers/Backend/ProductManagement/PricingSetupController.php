<?php

namespace App\Http\Controllers\Backend\ProductManagement;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\ProductManagement\Pricing;
use App\Models\ProductManagement\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PricingSetupController extends Controller
{
    use Helper;

    public function __construct()
    {
//        if (!can(request()->route()->action['as'])) {
//            return returnData(5001, null, 'You are not authorized to access this page');
//        }
        $this->model = new Pricing();
    }


    public function index()
    {
        if (!can('set_markup_pricing.index')) {
            return $this->notPermitted();
        }

        try {
            $keyword = request()->input('keyword');

            $data = $this->model
                ->with(['product','dealer'])
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where('name', 'like', "%$keyword%");
                })
                ->orderBy('id','DESC')
                ->paginate(request('perPage'));

            return returnData(2000, $data);
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }



    public function create()
    {
        //
    }


    public function store(Request $request)
    {
//        ddA($request);
        if (!can('set_markup_pricing.store')) {
            return $this->notPermitted();
        }
//        ddA($request);
        try {
            $input = $request->all();

            // logged in user id insert
            $input['user_id'] = auth()->id();

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


    public function show($id)
    {
        try {
            $data = $this->model->findOrFail($id);
            return returnData(2000, $data);
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Record not found or something went wrong.');
        }
    }



    public function edit(string $id)
    {
        //
    }


    public function update(Request $request, $id)
    {
        if (!can('set_markup_pricing.update')) {
            return $this->notPermitted();
        }
        try {
            $input = $request->all();

            $input['user_id'] = auth()->id();

            $validation = $this->model->validate($input);
            if ($validation->fails()) {
                return response()->json(['status' => 3000, 'errors' => $validation->errors()], 200);
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
        if (!can('set_markup_pricing.destroy')) {
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
                $data = Pricing::where('id', $id)->first();

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



    public function getProducts(Request $request)
    {
        $dealerId = $request->selected_id;
        $today = Carbon::today();

        $products = Product::join('product_serial_groups as psg', 'psg.product_id', '=', 'products.id')
            ->checkWarehouse()
            ->where('products.status', 1)
            ->selectRaw("products.id,psg.id as psg_id, products.product_name, products.product_code,psg.selling_price,psg.dealer_price,psg.warehouse_id,psg.cost_price,markup_percentage")
            ->withCount([
                'serials as quantity' => function ($q) {
                    $q->where('status', 0);
                }
            ])
            ->whereHas('serials', function ($q) {
                $q->where('status', 0);
            })
            ->orderBy('products.id', 'DESC')
            ->get()
            ->map(function ($product) use ($dealerId, $today) {

                $selling_price = $product->selling_price;
                $dealer_price  = $product->dealer_price;

                // Dealer-specific pricing logic
                $markup = Pricing::where('product_id', $product->id)
                    ->where('status',1)
                    ->whereDate('effective_from','<=',$today)
                    ->whereDate('effective_to','>=',$today)
                    ->where(function ($q) use ($dealerId) {
                        $q->where(function ($q1) use ($dealerId) {
                            $q1->where('pricing_type',2)
                                ->where('dealer_id',$dealerId);
                        })
                            ->orWhere(function ($q2) {
                                $q2->where('pricing_type',1)
                                    ->whereNull('dealer_id');
                            });
                    })
                    ->orderByRaw('CASE WHEN pricing_type = 2 THEN 0 ELSE 1 END')
                    ->first();

                if ($markup) {
                    $selling_price = $markup->sales_price;
                    $dealer_price  = $markup->dealer_price;
                }

                return [
                    'id'            => $product->id,
                    'psg_id'        => $product->psg_id,
                    'product_name'  => $product->product_name . " (MRP: $selling_price, DP: $dealer_price)",
                    'product_code'  => $product->product_code,
                    'selling_price' => $selling_price,
                    'dealer_price'  => $dealer_price,
                    'quantity'      => $product->quantity
                ];
            });

        return returnData(2000, ['products' => $products], '');
    }


}


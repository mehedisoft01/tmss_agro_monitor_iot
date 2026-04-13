<?php

namespace App\Http\Controllers\api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Dealer\Dealer;
use App\Models\Inventory\StockPurchase;
use App\Models\Inventory\Warehouse;
use App\Models\ProductManagement\Product;
use App\Models\Sales\Customer;
use App\Models\Sales\Order;
use Illuminate\Http\Request;

class IndexApiController extends Controller
{
    use Helper;

    public function index_warehouse()
    {
        $this->model = new Warehouse();
        try {
            $keyword = request()->input('keyword');
            $divisionId = request()->input('division_id');
            $districtId = request()->input('district_id');
            $upazilaId = request()->input('upazila_id');
            $user = auth()->user();
            $isSalesman = $this->isSalesManger();

            $data = $this->model
                ->with(['division:id,division_name', 'district:id,district_name', 'upazila:id,upazila_name'])
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('warehouse_name', 'like', "%{$keyword}%")
                            ->orWhere('warehouse_code', 'like', "%{$keyword}%")
                            ->orWhere('phone', 'like', "%{$keyword}%")
                            ->orWhere('email', 'like', "%{$keyword}%");
                    });
                })
                ->when($divisionId, function ($query) use ($divisionId) {
                    $query->where('division_id', 'Like', "%$divisionId%");
                })
                ->when($districtId, function ($query) use ($districtId) {
                    $query->where('district_id', 'Like', "%$districtId%");
                })
                ->when($upazilaId, function ($query) use ($upazilaId) {
                    $query->where('upazila_id', 'Like', "%$upazilaId%");
                })
                ->where(function ($query) use ($user) {
                    if ($user->division_id){
                        $query->where('division_id', $user->division_id);
                    }
                    if ($user->district_id){
                        $query->where('district_id', $user->district_id);
                    }
                    if ($user->warehouse_id){
                        $query->where('id', $user->warehouse_id);
                    }
                })
                ->when($isSalesman, function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->orderBy('id','DESC')
                ->get();
//                ->paginate(input('perPage'))

            return returnData(2000, $data);
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }
    public function index_dealer()
    {
        $this->model = new Dealer();
        try {
            $keyword = request()->input('keyword');
            $dealer = request()->input('dealer');
            $approval_status = request()->input('approval_status') ? request()->input('approval_status') : 1;
            $auth = auth()->user();
            $user = auth()->user();
            $isSalesman = $this->isSalesManger();

            $data = $this->model
                ->with([
                    'approve:id,name',
                    'address.division:id,division_name',
                    'address.district:id,district_name',
                    'address.upazila:id,upazila_name',
                ])
                ->when($auth->dealer_id != null, function ($query) use ($auth) {
                    $query->where('dealers.id', $auth->dealer_id);
                })
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('dealers.name', 'like', "%{$keyword}%")
                            ->orWhere('dealers.city', 'like', "%{$keyword}%");
                    });
                })
                ->when($dealer, function ($query) use ($dealer) {
                    $query->where(function ($q) use ($dealer) {
                        $q->where('dealers.dealer_code', 'like', "%{$dealer}%")
                            ->orWhere('dealers.phone', 'like', "%{$dealer}%");
                    });
                })
                ->whereHas('address', function ($query) use ($user) {
                    if ($user->division_id) {
                        $query->where('p_division_id', $user->division_id);
                    }
                    if ($user->district_id) {
                        $query->where('p_district_id', $user->district_id);
                    }
                })
                ->when($isSalesman, function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->where('dealers.approval_status', $approval_status)
                ->orderBy('dealers.id', 'DESC')
                ->get();
            return returnData(2000, $data);

        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }
    public function index_product()
    {
        $this->model = new Product();
        if (!can('product_list.index')) {
            return $this->notPermitted();
        }

        try {
            $keyword = request()->input('keyword');
            $user = auth()->user();
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
                ->orderBy('id', 'DESC')
                ->get();

            return returnData(2000, $data);
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }
    public function index_order()
    {
        $this->model = new Order();
        if (!can('order_information.index')) {
            return $this->notPermitted();
        }

        try {
            $keyword = request()->input('keyword');
            $dealerId = request()->input('dealer_id');
            $customerId = request()->input('customer_id');
            $dateFrom     = request()->input('order_date_from');
            $dateTo       = request()->input('order_date_to');
            $user = auth()->user();
            $isSalesman = $this->isSalesManger();

            $data = $this->model
//                ->checkWarehouse()
                ->with(['customer', 'approve:id,name', 'dealer', 'paymentConfirmedByUser', 'createdByUser', 'orderApprovedByUser'])
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where('order_no', 'like', "%$keyword%");
                })
                ->when($dealerId, function ($query) use ($dealerId) {
                    $query->where('dealer_id', 'like', "%$dealerId%");
                })
                ->when($customerId, function ($query) use ($customerId) {
                    $query->where('customer_id', 'like', "%$customerId%");
                })
                ->when($dateFrom && $dateTo, function ($query) use ($dateFrom, $dateTo) {
                    $query->whereBetween('order_date', [$dateFrom, $dateTo]);
                })

                ->when($dateFrom && !$dateTo, function ($query) use ($dateFrom) {
                    $query->whereDate('order_date', '>=', $dateFrom);
                })

                ->when(!$dateFrom && $dateTo, function ($query) use ($dateTo) {
                    $query->whereDate('order_date', '<=', $dateTo);
                })
                ->where(function ($query) use ($user) {
                    if ($user->division_id) {
                        $query->where('division_id', $user->division_id);
                    }
                    if ($user->district_id) {
                        $query->where('district_id', $user->district_id);
                    }
                    if ($user->warehouse_id) {
                        $query->where('warehouse_id', $user->warehouse_id);
                    }
                })
                ->when($isSalesman, function ($query) use ($user) {
                    $query->where('created_by', $user->id);
                })
                ->orderBy('id', 'DESC')
                ->get();

            return returnData(2000, $data);
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }
    public function index_customer()
    {
        $this->model = new Customer();
        if (!can('customers.index')) {
            return $this->notPermitted();
        }

        try {
            $keyword = request()->input('keyword');
            $divisionId = request()->input('p_division_id');
            $districtId = request()->input('p_district_id');
            $upazilaId = request()->input('p_upazila_id');
            $user = auth()->user();
            $isSalesman = $this->isSalesManger();

            $data = $this->model
                ->with([
                    'address.division:id,division_name',
                    'address.district:id,district_name',
                    'address.upazila:id,upazila_name',
                    'warehouse'
                ])                ->when($keyword, function ($query) use ($keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%")
                            ->orWhere('phone', 'like', "%{$keyword}%")
                            ->orWhere('email', 'like', "%{$keyword}%");
                    });
                })
                ->when($divisionId, function ($query) use ($divisionId) {
                    $query->whereHas('address', function ($q) use ($divisionId) {
                        $q->where('p_division_id', $divisionId);
                    });
                })
                ->when($districtId, function ($query) use ($districtId) {
                    $query->whereHas('address', function ($q) use ($districtId) {
                        $q->where('p_district_id', $districtId);
                    });
                })
                ->when($upazilaId, function ($query) use ($upazilaId) {
                    $query->whereHas('address', function ($q) use ($upazilaId) {
                        $q->where('p_upazila_id', $upazilaId);
                    });
                })
                ->whereHas('address', function ($query) use ($user) {

                    $query->where('type', 1);

                    if ($user->division_id){
                        $query->where('p_division_id', $user->division_id);
                    }

                    if ($user->district_id){
                        $query->where('p_district_id', $user->district_id);
                    }
                })
                ->when($user->warehouse_id, function ($query) use ($user) {
                    $query->where(function ($q) use ($user) {
                        $q->whereNull('warehouse_id')
                            ->orWhere('warehouse_id', $user->warehouse_id);
                    });
                })
                ->when($isSalesman, function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->orderBy('customers.id','DESC')
                ->get();

            return returnData(2000, $data);

        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }
    public function index_stock_product(Request $request)
    {
        $this->model = new StockPurchase();
        if (!can('stock_product.index')) {
            return $this->notPermitted();
        }

        try {
            $keyword = request()->input('keyword');

            $data = $this->model
                ->with(['product', 'warehouse'])
                ->when($keyword, function ($query) use ($keyword) {
                    $query->whereHas('product', function ($q) use ($keyword) {
                        $q->where('product_name', 'LIKE', "%{$keyword}%");
                    });
                })
                ->orderBy('id','DESC')
                ->get();
//                ->paginate(input('perPage'));

            return returnData(2000, $data);
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }


    public function index()
    {
        //
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
        //
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
        //
    }
}

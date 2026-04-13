<?php

namespace App\Http\Controllers\Backend\Sales;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Sales\Customer;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    use Helper;

    public function __construct()
    {
//        if (!can(request()->route()->action['as'])) {
//            return returnData(5001, null, 'You are not authorized to access this page');
//        }
        $this->model = new Customer();
    }

    public function index()
    {
        if (!can('customers.index')) {
            return $this->notPermitted();
        }

        try {
            $keyword = request()->input('keyword');
            $divisionId = request()->input('p_division_id');
            $districtId = request()->input('p_district_id');
            $upazilaId = request()->input('p_upazila_id');
            $perPage = request()->input('per_page');
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
                ->paginate($perPage);

            return returnData(2000, $data);

        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }




    public function store(Request $request)
    {
        if (!can('customers.store')) {
            return $this->notPermitted();
        }

        try {
            $auth = auth()->user();
            $input = $request->all();
            $input['user_id'] = $auth->id;

            $validate = $this->model->validate($input);
            if ($validate->fails()) {
                return returnData(5000, $validate->errors());
            }

            $data = $this->model->create($input);

            Address::create([
                'customer_id'     => $data->id,
                'type'        => 1,
                'p_division_id' => $request->p_division_id,
                'p_district_id' => $request->p_district_id,
                'p_upazila_id'  => $request->p_upazila_id,
                'p_area'        => $request->p_area,
            ]);

            return returnData(2000, null, 'Customer Successfully Inserted');

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



    public function edit($id)
    {
        $data = $this->model->find($id);

        if (!$data) {
            return returnData(5000, [], 'Data Not Found..!!');
        }

        $data->addresses = Address::where('customer_id', $id)->where('type', 1)->first();

        return returnData(2000, $data);
    }



    public function update(Request $request, $id)
    {
        if (!can('customers.update')) {
            return $this->notPermitted();
        }

        try {
            $input = $request->all();
            $input['user_id'] = auth()->id();

            $validation = $this->model->validate($input);
            if ($validation->fails()) {
                return response()->json(['status' => 3000, 'errors' => $validation->errors()], 300);
            }

            $data = $this->model->find($id);
            if ($data) {

                $data->update($input);

                Address::updateOrCreate(
                    ['customer_id' => $data->id, 'type' => 1],
                    [
                        'p_division_id' => $request->p_division_id,
                        'p_district_id' => $request->p_district_id,
                        'p_upazila_id'  => $request->p_upazila_id,
                        'p_area'        => $request->p_area,
                    ]
                );

                return returnData(2000, null, 'Customer Successfully Updated');
            }

        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }




    public function destroy($id)
    {
        if (!can('customers.destroy')) {
            return $this->notPermitted();
        }
        try {
            $data = $this->model->where('id', $id)->first();
            if (!$data) {
                return returnData(5000, null, 'Data Not found');
            }

            $data->delete();

            return returnData(2000, $data, 'Customer Successfully Deleted');

        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }


    public function getAddress($type, $id)
    {
        $address = Address::where($type . '_id', $id)
            ->where('status', 1)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$address) {
            return returnData(5000, null, 'Address not found');
        }

        if ($address->type == 1) {
            $response = [
                'division_id' => $address->p_division_id,
                'district_id' => $address->p_district_id,
                'upazila_id' => $address->p_upazila_id,
                'area' => $address->p_area,
            ];
        } else { // type = 2 (shipping) 
            $response = [
                'division_id' => $address->s_division_id,
                'district_id' => $address->s_district_id,
                'upazila_id' => $address->s_upazila_id,
                'area' => $address->s_area,
            ];
        }
//        ddA($address);
        return returnData(2000, $response, ucfirst($type) . ' Address Successfully fetched');
    }


    public function multiple(Request $request)
    {
        if (!can('customers.destroy')) {
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
                $data = Customer::where('id', $id)->first();

                if (!$data) {
                    $errors[] = "ID {$id} not found";
                    continue;
                }
                $data->address()->delete();
                $data->delete();
                $deleted[] = $id;
            }

            return returnData(2000, null,count($deleted)." Item Deleted and ".json_encode($errors)." Item Not Deleted");
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }

    }

}

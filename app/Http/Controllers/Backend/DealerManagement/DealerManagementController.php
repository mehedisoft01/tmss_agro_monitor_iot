<?php

namespace App\Http\Controllers\Backend\DealerManagement;

use App\Helpers\Helper;
use App\Models\Accounting\DealerLedger;
use App\Models\Address;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Dealer\Dealer;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\Concerns\Has;

class DealerManagementController extends Controller
{
    use Helper;

    public function __construct()
    {
        $this->model = new Dealer();
    }


    public function index()
    {
        try {
            $keyword = request()->input('keyword');
            $dealer = request()->input('dealer');
            $perPage = request()->input('per_page');
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
                ->paginate($perPage);
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

            $input['user_id'] = auth()->id();
            $input['status'] = $request->status ?? 1;
            $input['approval_status'] = $request->approval_status ?? 1;
            if ($input['approval_status'] == 1) {
                $input['approved_by'] = auth()->id();
                $input['approved_date'] = now();
            }
            $validate = $this->model->validate($input);
            if ($validate->fails()) {
                return returnData(5000, $validate->errors()->first(), 'Validation Error..!!');
            }

            if (!empty($input['password'])) {
                $input['password'] = Hash::make($input['password']);
            }
            if (isset($input['attachments']) && !empty($input['attachments'])) {
                $input['attachments'] = json_encode($input['attachments']);
            } else {
                $input['attachments'] = json_encode([]);
            }


            $input['dealer_code'] = generateUniqueCode('DLR', 'dealer_code', 'dealers');
            $data = $this->model->create($input);

            Address::create([
                'dealer_id'     => $data->id,
                'type'        => 1,
                'p_division_id' => $request->p_division_id,
                'p_district_id' => $request->p_district_id,
                'p_upazila_id'  => $request->p_upazila_id,
                'p_area'        => $request->p_area,
            ]);

            return returnData(2000, $data, 'Dealer Registered Successfully..!!');
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

            $data->attachments = json_decode($data->attachments, true);

            $address = Address::with(['division', 'district', 'upazila'])
                ->where('dealer_id', $id)
                ->where('type', 1)
                ->first();

            if ($address) {
                $data->p_division_name = $address->division->division_name ?? null;
                $data->p_district_name = $address->district->district_name ?? null;
                $data->p_upazila_name  = $address->upazila->upazila_name ?? null;
                $data->p_area          = $address->p_area ?? null;
            }

            // Orders & Invoice
            $data->total_orders = Order::where('dealer_id', $id)->where('status', 1)->count();

            $data->total_invoice = Invoice::where('dealer_id', $id)->where('status', 1)->count();

            // 🔥 Payment & Due
            $ledger = DealerLedger::where('dealer_id', $id)->where('type', 1);

            $totalDebit  = $ledger->sum('debit');
            $totalCredit = $ledger->sum('credit');

            $data->total_payment = $totalCredit;
            $data->total_due = $totalDebit - $totalCredit;


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
        $data = $this->model->find($id);

        if (!$data) {
            return returnData(5000, [], 'Data Not Found..!!');
        }
        $data->attachments = json_decode($data->attachments, true);

        $address = Address::where('dealer_id', $id)->where('type', 1)->first();
        $data->addresses = $address;

        return returnData(2000, $data);
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

            $input['attachments'] = isset($input['attachments']) && !empty($input['attachments']) ? json_encode($input['attachments']) : json_encode([]);

            if (!empty($input['password'])) {
                $input['password'] = Hash::make($input['password']);
            } else {
                unset($input['password']);
            }
            $data->update($input);

            Address::updateOrCreate(
                ['dealer_id' => $data->id, 'type' => 1],
                [
                    'p_division_id' => $request->p_division_id,
                    'p_district_id' => $request->p_district_id,
                    'p_upazila_id'  => $request->p_upazila_id,
                    'p_area'        => $request->p_area,
                ]
            );

            return returnData(2000, $data, 'Dealer Updated Successfully..!!');

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

            return returnData(2000, [], 'Dealer Deleted Successfully..!!');
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }
}

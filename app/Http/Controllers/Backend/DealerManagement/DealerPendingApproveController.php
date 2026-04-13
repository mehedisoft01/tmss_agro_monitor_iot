<?php

namespace App\Http\Controllers\Backend\DealerManagement;

use App\Helpers\Helper;
use Illuminate\Http\Request;
use App\Models\Dealer\Dealer;
use App\Http\Controllers\Controller;

class DealerPendingApproveController extends Controller
{
    use Helper;

    public function __construct()
    {
        $this->model = new Dealer();
    }
    public function index()
    {
        try {
            $user = auth()->user();
            $isSalesman = $this->isSalesManger();

            $keyword = request()->input('keyword');
            $dealer = request()->input('dealer');
            $perPage = request()->input('per_page');
            $data = $this->model->with('approve:id,name')
                ->leftJoin('addresses', 'dealers.id', '=', 'addresses.dealer_id')
                ->leftJoin('divison', 'addresses.p_division_id', '=', 'divison.id')
                ->leftJoin('district', 'addresses.p_district_id', '=', 'district.id')
                ->leftJoin('thana', 'addresses.p_upazila_id', '=', 'thana.id')
                ->select(
                    'dealers.*',
                    'divison.division_name as p_division_name',
                    'district.district_name as p_district_name',
                    'thana.upazila_name as p_upazila_name',
                    'addresses.p_area'
                )
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where('name', 'Like', "%$keyword%");
                })
                ->when($dealer, function ($query) use ($dealer) {
                    $query->where(function ($q) use ($dealer) {
                        $q->where('dealer_code', 'like', "%{$dealer}%")
                            ->orWhere('phone', 'like', "%{$dealer}%");
                    });
                })
                ->where(function ($query) use ($user) {
                    if ($user->division_id){
                        $query->where('addresses.p_division_id', $user->division_id);
                    }
                    if ($user->district_id){
                        $query->where('addresses.p_district_id', $user->district_id);
                    }

                })
                ->when($isSalesman, function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->where('approval_status', 0)
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
        // dd('Not Implemented', $request->all());
        try {
            $input = $request->all();
            $dealer = $this->model->find($input['id']);
            $userId = auth()->user()->id;

            if (!$dealer) {
                return returnData(4040, null, 'Dealer not found.');
            }

            $dealer->update([
                'approval_status' => $input['approval_status'],
                'approved_by' => $userId,
                'user_id'     => $userId
            ]);

            return returnData(2000, $dealer, 'Approve Successfully..!!');
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id) {}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    public function approveIndex()
    {
        try {
            $keyword = request()->input('keyword');
            $userId = auth()->id();
            $lowerUserIds = myLowerUserIds();
            $data = $this->model->with('approve:id,name')
                ->leftJoin('addresses', 'dealers.id', '=', 'addresses.dealer_id')
                ->leftJoin('divison', 'addresses.p_division_id', '=', 'divison.id')
                ->leftJoin('district', 'addresses.p_district_id', '=', 'district.id')
                ->leftJoin('thana', 'addresses.p_upazila_id', '=', 'thana.id')
                ->select(
                    'dealers.*',
                    'divison.division_name as p_division_name',
                    'district.district_name as p_district_name',
                    'thana.upazila_name as p_upazila_name',
                    'addresses.p_area'
                )
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where('name', 'Like', "%$keyword%");
                })
                ->where(function ($q) use ($userId, $lowerUserIds) {
                    $q->where('user_id', $userId)
                        ->orWhereIn('user_id', $lowerUserIds);
                })
                ->where('approval_status', 1)
                ->paginate(input('perPage'));

            return returnData(2000, $data);
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }
}

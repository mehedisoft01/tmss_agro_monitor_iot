<?php

namespace App\Http\Controllers\Backend\Accounting;

use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Accounting\DealerLedger;
use App\Models\Accounting\PaymentCollection;
use App\Models\Sales\Invoice;

class PaymentCollectionController extends Controller
{
    use Helper;

    public function __construct()
    {
        $this->model = new PaymentCollection();
    }

    public function index()
    {
        try {
            $keyword = request()->input('keyword');
            $type = request()->input('type');
            $customerId = request()->input('customer_id');
            $dealerId = request()->input('dealer_id');
            $perPage = request()->input('per_page');
            $user = auth()->user();
            $isSalesman = $this->isSalesManger();

            $data = $this->model
                ->leftJoin('invoices as i', 'i.id', '=', 'payment_collections.invoice_id')
                ->leftJoin('customers as c', 'c.id', '=', 'i.customer_id')
                ->leftJoin('dealers as d', 'd.id', '=', 'i.dealer_id')
                ->leftJoin('users as u', 'u.id', '=', 'payment_collections.user_id')
                ->select(
                    'payment_collections.*',
                    'i.invoice_no',
                    'c.name as customer_name',
                    'd.name as dealer_name',
                    'u.name as user_name',
                    DB::raw("
                    CASE 
                        WHEN payment_collections.payment_mode = 1 THEN 'Cash'
                        WHEN payment_collections.payment_mode = 2 THEN 'Bank'
                        WHEN payment_collections.payment_mode = 3 THEN 'Mobile'
                        ELSE 'Unknown'
                    END as payment_mode_text
                ")
                )
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where('payment_collections.receipt_no', 'LIKE', "%{$keyword}%")
                        ->orWhere('i.invoice_no', 'LIKE', "%{$keyword}%")
                        ->orWhere('c.name', 'LIKE', "%{$keyword}%")
                        ->orWhere('d.name', 'LIKE', "%{$keyword}%");
                })
                ->when($type, function ($query) use ($type) {
                    if ($type === 'customer') {
                        $query->whereNotNull('payment_collections.customer_id');
                    } elseif ($type === 'dealer') {
                        $query->whereNotNull('payment_collections.dealer_id');
                    }
                })
                ->when($customerId, function ($query) use ($customerId) {
                    $query->where('payment_collections.customer_id', $customerId);
                })
                ->when($dealerId, function ($query) use ($dealerId) {
                    $query->where('payment_collections.dealer_id', $dealerId);
                })

                ->when($isSalesman, function ($query) use ($user) {
                    $query->where('created_by', $user->id);
                })
                ->orderBy('payment_collections.id', 'desc')
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
        // dd($request->all());
        try {
            $input = $request->all();
            $input['user_id'] = auth()->id();

            $validate = $this->model->validate($input);
            if ($validate->fails()) {
                return returnData(5000, $validate->errors()->first(), 'Validation Error..!!');
            }

            DB::beginTransaction();

            $cust_delar_id = Invoice::where('id', $input['invoice_id'])->first();
            if ($cust_delar_id->dealer_id) {
                $input['dealer_id'] = $cust_delar_id->dealer_id;
                $type = 1;
            } else {
                $input['customer_id'] = $cust_delar_id->customer_id;
                $type = 2;
            }

            $input['receipt_no'] = generateUniqueCode('PY', 'receipt_no', 'payment_collections');
            $data = $this->model->create($input);



            // dd($data);

            dealerLeagerBalance([
                'payment_id'       => $data->id,
                'invoice_id'       => $data->invoice_id,
                'customer_id'      => $cust_delar_id->customer_id,
                'dealer_id'        => $data->dealer_id,
                'transaction_type' => 2,
                'type'             => $type,
                'date'             => $data->date,
                'debit'            => 0,
                'credit'           => $data->amount,
                'remarks'          =>  $data->remarks,
            ]);

            $ledger = DB::table('dealer_ledgers')
                ->where('invoice_id', $data->invoice_id)
                ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
                ->first();

            if ($ledger && $ledger->total_debit <= $ledger->total_credit) {
                Invoice::where('id', $data->invoice_id)->update([
                    'invoice_status'       => 1,
                    'payment_confirmed_by' => auth()->id(),
                ]);
            }


            DB::commit();

            return returnData(2000, $data, 'Data Inserted Successfully..!!');
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
            return returnData(2000, $data);
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
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

            if ($data->dealer_id) {
                $type = 1;
            } else {
                $type = 2;
            }

            DB::beginTransaction();

            $data->update($input);
            $getID = DealerLedger::where('payment_id', $id)->first();

            dealerLeagerBalance([
                'id'               => $getID->id,
                'payment_id'       => $id,
                'invoice_id'       => $data->invoice_id,
                'customer_id'      => $data->customer_id,
                'dealer_id'        => $data->dealer_id,
                'transaction_type' => 2,
                'type'             => $type,
                'date'             => $data->date,
                'debit'            => 0,
                'credit'           => $data->amount,
                'remarks'          =>  $data->remarks,
            ]);

            DB::commit();
            return returnData(2000, $data, 'Data Updated Successfully..!!');
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }


    public function destroy(string $id)
    {
        try {
            $data = $this->model->find($id);
            if (!$data) {
                return returnData(5000, [], 'Data Not Found..!!');
            }
            DB::table('dealer_ledgers')->where('payment_id', $id)->delete();
            $data->delete();
            return returnData(2000, [], 'Data Deleted Successfully..!!');
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }

    public function getDueAmount(Request $request)
    {
        $id = $request->input('id');

        $totals = DealerLedger::where('invoice_id', $id)
            ->selectRaw("
            SUM(CASE WHEN transaction_type = 1 THEN COALESCE(debit, 0) ELSE 0 END) AS total_debit,
            SUM(CASE WHEN transaction_type = 2 THEN COALESCE(credit, 0) ELSE 0 END) AS total_credit
        ")
            ->first();
        $totalDebit  = $totals->total_debit  ?? 0;
        $totalCredit = $totals->total_credit ?? 0;
        $dueAmount   = (float) $totalDebit - (float) $totalCredit;

        $data = [
            'invoice_id'   => $id,
            'total_debit'  => (float) $totalDebit,
            'total_credit' => (float) $totalCredit,
            'due_amount'   => $dueAmount,
        ];
        // dd($data);
        return returnData(2000, $data);
    }
}

<?php

namespace App\Http\Controllers\Backend\Sales;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Accounting\PaymentCollection;
use App\Models\Address;
use App\Models\HumanResource\Salesman;
use App\Models\Inventory\ProductStockMovement;
use App\Models\Inventory\Stock;
use App\Models\Inventory\Warehouse;
use App\Models\ProductManagement\Product;
use App\Models\ProductSerial;
use App\Models\Sales\Invoice;
use App\Models\Sales\InvoiceItem;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    use Helper;

    public function __construct()
    {
        //        if (!can(request()->route()->action['as'])) {
        //            return returnData(5001, null, 'You are not authorized to access this page');
        //        }
        $this->model = new Invoice();
    }

    public function index()
    {
                if (!can('invoice_information.index')) {
                    return $this->notPermitted();
                }

        try {
            $keyword = request()->input('keyword');
            $dealerId = request()->input('dealer_id');
            $customerId = request()->input('customer_id');
            $warehouseId = request()->input('warehouse_id');
            $dateFrom     = request()->input('form_date');
            $dateTo       = request()->input('to_date');
            $perPage      = request()->input('per_page');
            $serial      = request()->input('serial');
            $user = auth()->user();
            $isSalesman = $this->isSalesManger();

            $data = $this->model
                ->with(['order', 'items','customer','dealer','warehouse','createdByUser','approvedByUser'])
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('invoice_no', 'like', "%{$keyword}%")
                            ->orWhere('order_no', 'like', "%{$keyword}%");
                    });
                })
                ->when($dealerId, function ($query) use ($dealerId) {
                    $query->where('dealer_id', 'like', "%$dealerId%");
                })
                ->when($customerId, function ($query) use ($customerId) {
                    $query->where('customer_id', 'like', "%$customerId%");
                })
                ->when($warehouseId, function ($query) use ($warehouseId) {
                    $query->where('warehouse_id', 'like', "%$warehouseId%");
                })
                ->when($dateFrom && $dateTo, function ($query) use ($dateFrom, $dateTo) {
                    $query->whereBetween('invoice_date', [$dateFrom, $dateTo]);
                })
                ->when($dateFrom && !$dateTo, function ($query) use ($dateFrom) {
                    $query->whereDate('invoice_date', '>=', $dateFrom);
                })
                ->when($serial, function ($query) use ($serial) {
                    $query->whereHas('items.productSerials', function ($q) use ($serial) {
                        $q->where('serial', 'like', "%{$serial}%");
                    });
                })
                ->when(!$dateFrom && $dateTo, function ($query) use ($dateTo) {
                    $query->whereDate('invoice_date', '<=', $dateTo);
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
//                ->orWhere('salesman_id', $user->salesman_id)
                ->where('invoice_status', 1)

                ->orderBy('id', 'DESC')
                ->paginate($perPage);

            return returnData(2000, $data);
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }


    public function prints($id)
    {

        $invoice = Invoice::with(['customer','dealer', 'items.product','createdByUser','productSerials.product', 'createdByUser.salesman.designation'])
            ->where('invoice_no', $id)
            ->orWhere('order_no', $id)
            ->first();

//        $salesman = Salesman::with('designation')
//            ->where('user_id', $invoice->created_by)
//            ->first();

        if (!$invoice) {
            abort(404, "Invoice not found for {$id}");
        }

        $salesman = optional($invoice->createdByUser)->salesman;


        $subTotal   = $invoice->items->sum('total_price');
        $discount   = $invoice->discount ?? 0;
        $grandTotal = $invoice->net_amount ?? ($subTotal - $discount);

        $amountInWords = $this->numberToWords((int) round($grandTotal));

        if ($invoice->customer_id) {
            $address = Address::where('customer_id', $invoice->customer_id)
                ->orderBy('created_at', 'desc')
                ->first();
        } else {
            $address = Address::where('dealer_id', $invoice->dealer_id)
                ->orderBy('created_at', 'desc')
                ->first();
        }

        $settings = DB::table('settings')
            ->where('setting_type', 'company_information')
            ->pluck('value', 'key');
        return view('invoice.print', compact('invoice','settings','address','amountInWords','grandTotal','subTotal','discount',    'salesman'
        ));
    }

    private function numberToWords($number)
    {
        $words = [
            0 => 'Zero', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
            5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
            10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen',
            14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen',
            17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen',
            20 => 'Twenty', 30 => 'Thirty', 40 => 'Forty',
            50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy',
            80 => 'Eighty', 90 => 'Ninety'
        ];

        if ($number < 21) {
            return $words[$number];
        }

        if ($number < 100) {
            return $words[floor($number / 10) * 10]
                . ($number % 10 ? ' ' . $words[$number % 10] : '');
        }

        if ($number < 1000) {
            return $words[floor($number / 100)] . ' Hundred'
                . ($number % 100 ? ' ' . $this->numberToWords($number % 100) : '');
        }

        if ($number < 100000) {
            return $this->numberToWords(floor($number / 1000)) . ' Thousand'
                . ($number % 1000 ? ' ' . $this->numberToWords($number % 1000) : '');
        }

        if ($number < 10000000) {
            return $this->numberToWords(floor($number / 100000)) . ' Lakh'
                . ($number % 100000 ? ' ' . $this->numberToWords($number % 100000) : '');
        }

        return $number;
    }

    public function generate($order_no)
    {
        try {
            $order = Order::with(['dealerItems','customerItems'])->where('order_no', $order_no)->first();

            if (!$order) {
                return returnData(5000, null, 'Order not found');
            }

            return returnData(2000, [
                'order' => $order,
                'items' => $order->items
            ]);
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }




    public function store(Request $request)
    {
//        ddA($request);
        try {

            $auth = auth()->user();
            $order = null;

            if ($request->order_no) {
                $order = Order::with('items')->where('order_no', $request->order_no)->first();

                if (!$order) {
                    return returnData(5000, null, 'Order not found');
                }

                if ($order->invoice_id) {
                    return returnData(5000, null, 'Invoice already generated for this order');
                }
            }

            DB::beginTransaction();

//            $warehouseId = $auth->warehouse_id != 0 ? $auth->warehouse_id : null;
//            $warehouse = $warehouseId ? Warehouse::find($warehouseId) : null;
//            $divisionId = $warehouse->division_id ?? null;
//            $districtId = $warehouse->district_id ?? null;


            //login user warehouse
            $warehouseId = $auth->warehouse_id != 0 ? $auth->warehouse_id : null;

            if ($request->salesman_id) {

                $salesman = User::find($request->salesman_id);

                if ($salesman && $salesman->warehouse_id) {
                    $warehouseId = $salesman->warehouse_id;
                }
            }

            $warehouse = $warehouseId ? Warehouse::find($warehouseId) : null;
            $divisionId = $warehouse->division_id ?? null;
            $districtId = $warehouse->district_id ?? null;

            // Create invoice
            $invoice = Invoice::create([
                'invoice_no' => $order
                    ? 'INV-' . str_pad($order->id, 6, '0', STR_PAD_LEFT)
                    : 'INV-DIR-' . str_pad((Invoice::max('id') + 1), 6, '0', STR_PAD_LEFT),
                'order_no' => $order ? $order->order_no : null,
                'order_id' => $order ? $order->id : null,
                'customer_id' => $order && $order->customer_id ? $order->customer_id : ($request->customer_id ?? null),
                'dealer_id'   => $order && $order->dealer_id ? $order->dealer_id : ($request->dealer_id ?? null),
                'invoice_date' => $request->order_date,
                'delivery_date' => $request->delivery_date,
                'total_qty' => $request->total_qty,
                'total_amount' => $request->total_amount,
                'discount' => $request->discount ?? 0,
                'net_amount' => $request->net_amount,
                'salesman_id' => $request->salesman_id,
                'user_id' => $auth->id,
                'warehouse_id' => $warehouseId,
                'division_id' => $divisionId,
                'district_id' => $districtId,
                'created_by' => auth()->id(),
            ]);

            InvoiceItem::where('invoice_id', $invoice->id)->delete();

            // Create invoice items
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'warehouse_id' => $product->warehouse_id ?? null,
                    'product_code' => $product->product_code ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                    'remarks' => $item['remarks'] ?? '',
                    'serial_group_id' => $item['serial_group_id'] ?? null,
                ]);
            }


            // Update order if exists
            if ($order) {
                $order->update(['invoice_id' => $invoice->id, 'order_status' => 3]);
            }

            if($invoice->customer_id){
                $type = 2;
            }else{
                $type = 1;
            }

            dealerLeagerBalance([
                'invoice_id'       => $invoice->id,
                'customer_id'      => $invoice->customer_id,
                'dealer_id'        => $invoice->dealer_id,
                'transaction_type' => 1,
                'type'              => $type,
                'date'             => $invoice->invoice_date,
                'debit'            => $invoice->net_amount ?? 0,
                'credit'           => 0,
                'remarks'          =>  'purchase',
            ]);

            DB::commit();

            return returnData(2000, $invoice, 'Invoice generated successfully!');
        } catch (\Exception $exception) {
            DB::rollBack();
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }



    public function show($id)
    {
        if (!can('invoice_information.show')) {
            return $this->notPermitted();
        }

        try {

            $data = $this->model->with(['customer', 'dealer', 'createdByUser', 'paymentConfirmedByUser', 'approvedByUser', 'orderApprovedByUser',
                'order.division:id,division_name', 'order.district:id,district_name', 'order.upazila:id,upazila_name',])
                ->findOrFail($id);

            // Always get items from invoice_items by current invoice id
            $items = InvoiceItem::join('products', 'products.id', '=', 'invoice_items.product_id')
                ->join('product_serial_groups as psg', 'psg.id', '=', 'invoice_items.serial_group_id')
                ->selectRaw("invoice_items.*, psg.id as serial_group_id, products.id, CONCAT(product_name,' (MRP: ', psg.selling_price,', DP: ', psg.dealer_price, ')') AS product_name, products.product_code,psg.selling_price,psg.dealer_price,psg.warehouse_id,markup_percentage,psg.cost_price")
                ->where('invoice_id', $data->id)
                ->get();

            foreach ($items as $item) {
                $item->selected_serials = ProductSerial::where('product_id', $item->product_id)
                    ->where('serial_group_id', $item->serial_group_id)
                    ->where('invoice_id', $data->id)
                    ->get(['id', 'serial']);
            }

            if (!$data->order_id) {

                $address = Address::with(['division','district','upazila'])
                    ->where('customer_id', $data->customer_id)
                    ->where('type', 1)
                    ->where('status', 1)
                    ->latest()
                    ->first();

                if ($address) {

                    $fakeOrder = new Order();

                    $fakeOrder->setRelation('division', $address->division);
                    $fakeOrder->setRelation('district', $address->district);
                    $fakeOrder->setRelation('upazila', $address->upazila);
                    $fakeOrder->area = $address->p_area;

                    $data->setRelation('order', $fakeOrder);
                }
            }

            $data->setAttribute('items', $items);

            return returnData(2000, $data);

        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Record not found or something went wrong.');
        }
    }






    public function invoiceHistory(Request $request)
    {
        if ($request->customer_id && $request->dealer_id && $request->invoice_no && $request->from_date && $request->to_date && $request->type) {
            return returnData(400, [], 'Please select at least one filter: Invoice No, or Date Range');
        }

        $user = auth()->user();
        $lowerUserIds = myLowerUserIds();
        $isSalesman = $this->isSalesManger();

        $query = Invoice::with('customer', 'items.product', 'createdByUser','dealer')
//            ->where('invoice_status', 1)
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
            });

        if ($request->invoice_no) {
            $query->where('invoice_no', 'LIKE', "%{$request->invoice_no}%");
        }

        if ($request->dealer_id) {
            $query->where('dealer_id', $request->dealer_id);
        }

        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->type) {
            if ($request->type == 'customer') {
                $query->whereNotNull('customer_id');
            } elseif ($request->type == 'dealer') {
                $query->whereNotNull('dealer_id');
            }
        }

        if ($request->from_date && $request->to_date) {
            $query->whereBetween('invoice_date', [$request->from_date, $request->to_date]);
        } else {
            if ($request->from_date || $request->to_date) {
                return returnData(404, [], 'Please select both From Date and To Date');
            }
        }

        $invoices = $query->orderBy('id', 'desc')->get();

        if ($invoices->isEmpty()) {
            return returnData(404, [], 'No invoice found for your search!');
        }

        return returnData(2000, $invoices, '');
    }



    public function destroy($id)
    {
//        if (!can('order_information.destroy')) {
//            return $this->notPermitted();
//        }

        try {
            $data = $this->model->where('id', $id)->first();
            if (!$data) {
                return returnData(5000, null, 'Data Not found');
            }

            $data->delete();

            return returnData(2000, $data, 'Invoice Successfully Deleted');
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
                $data = Invoice::where('id', $id)->first();

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


    public function productSerials(Request $request)
    {
        try {
            $productId = $request->query('product_id');
            $serialGroupId = $request->query('serial_group_id');

            $data = ProductSerial::where('product_id', $productId)
                ->where('serial_group_id', $serialGroupId)
                ->where('status', 0)
                ->get(['id', 'serial']);

            return returnData(2000, $data);
        } catch (\Exception $e) {
            return returnData(5000, $e->getMessage());
        }
    }




    public function confirm(Request $request)
    {
        $invoice = Invoice::findOrFail($request->invoice_id);

        foreach ($request->serials as $row) {
            ProductSerial::where('id', $row['serial_id'])
                ->where('product_id', $row['product_id'])
                ->update([
                    'invoice_id' => $invoice->id,
                    'status' => 1,
                ]);
        }

        if ($invoice->order_no) {
            // Request / Order invoice
            $invoice->invoice_status = 1;
            $invoice->approved_by = auth()->id();

            if ($request->delivery_date) {
                $invoice->delivery_date = $request->delivery_date;
            }


        } else {
            // Direct invoice
            $invoice->invoice_status = 0;
            $invoice->approved_by = auth()->id();
        }

        $invoice->updated_at = now();
        $invoice->save();

        return response()->json(['status' => true]);
    }


}

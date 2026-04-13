<?php

namespace App\Http\Controllers\Backend\HumanResource;

use Carbon\Carbon;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\HumanResource\Payroll;

class PayrollController extends Controller
{
    use Helper;

    public function __construct()
    {
        $this->model = new Payroll();
    }

    public function index()
    {
        try {
            $warehouse_id = request()->input('warehouse_id');
            $salesman_id  = request()->input('salesman_id');
            $year         = request()->input('year');
            $month        = request()->input('month');

            // Required year & month
            if (!$year || !$month) {
                return returnData(3000, null, 'Must Be Select Year and Month');
            }

            // Fetch all active salesmen (warehouse optional)
            $salesmen = DB::table('salesmen')
                ->where('status', 1)
                ->when($warehouse_id, function($q) use ($warehouse_id) {
                    $q->where('warehouse_id', $warehouse_id);
                })
                ->when($salesman_id, function($q) use ($salesman_id) {
                    $q->where('id', $salesman_id);
                })
                ->whereNotIn('id', function($query) use ($year, $month) {
                    $query->select('salesman_id')
                        ->from('payrolls')
                        ->whereYear('salary_month', $year)
                        ->whereMonth('salary_month', $month);
                })->get();

            if ($salesmen->isEmpty()) {
                return returnData(3000, null, 'All payrolls for this month have already been generated');
            }

            $results = [];

            foreach ($salesmen as $emp) {
                $emp = (object)$emp;

                $monthStart = Carbon::create($year, $month, 1)->startOfDay();
                $monthEnd   = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

                // Territory (optional)
                $territory = DB::table('assign_territories')
                    ->where('status', 1)
                    ->where('salesman_id', $emp->id)
                    ->whereDate('target_start_date', '<=', $monthEnd)
                    ->whereDate('target_end_date', '>=', $monthStart)
                    ->first();

                // ===================== SALES TOTAL =====================
                $totalDp = DB::table('invoices')
                        ->where('salesman_id', $emp->id)
                        ->where('status', 1)
                        ->whereNotNull('dealer_id')
                        ->whereYear('invoice_date', $year)
                        ->whereMonth('invoice_date', $month)
                        ->sum('net_amount') ?? 0;

                $totalMrp = DB::table('invoices')
                        ->where('salesman_id', $emp->id)
                        ->where('status', 1)
                        ->whereNotNull('customer_id')
                        ->whereYear('invoice_date', $year)
                        ->whereMonth('invoice_date', $month)
                        ->sum('net_amount') ?? 0;

                // ===================== QTY =====================
                $customerQty = DB::table('invoices')
                        ->where('salesman_id', $emp->id)
                        ->where('status', 1)
                        ->whereNotNull('customer_id')
                        ->whereYear('invoice_date', $year)
                        ->whereMonth('invoice_date', $month)
                        ->sum('total_qty') ?? 0;

                $dealerQty = DB::table('invoices')
                        ->where('salesman_id', $emp->id)
                        ->where('status', 1)
                        ->whereNotNull('dealer_id')
                        ->whereYear('invoice_date', $year)
                        ->whereMonth('invoice_date', $month)
                        ->sum('total_qty') ?? 0;

                // ===================== INITIAL COMMISSION =====================
                $dpCommission = 0;
                $mrpCommission = 0;

                // ===================== GET ALL TARGETS =====================
                $targets = DB::table('assign_target_products')
                    ->where('salesman_id', $emp->id)
                    ->get();

                // ===================== LOOP TARGETS =====================
                foreach ($targets as $target) {

                    // TYPE 1 → Percent Based
                    if ($target->target_type == 1) {

                        // Customer → MRP
                        if ($totalMrp >= $target->from_amount && $totalMrp <= $target->to_amount) {
                            $percent = $target->commission ?? 0;
                            $mrpCommission += ($totalMrp * $percent) / 100;
                        }

                        // Dealer → DP → fixed 1%
                        if ($totalDp > 0) {
                            $dpCommission += ($totalDp * 1) / 100;
                        }
                    }

                    // TYPE 2 → Fixed per Qty
                    if ($target->target_type == 2) {
                        $fixed = $target->commission ?? 0;

                        // Customer Sale → MRP Commission
                        $customerQtyTarget = DB::table('invoice_items')
                                ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                                ->where('invoices.salesman_id', $emp->id)
                                ->where('invoices.status', 1)
                                ->whereNotNull('invoices.customer_id')
                                ->whereYear('invoices.invoice_date', $year)
                                ->whereMonth('invoices.invoice_date', $month)
                                ->where('invoice_items.product_id', $target->product_id)
                                ->sum('invoice_items.quantity') ?? 0;

                        // Dealer Sale → DP Commission
                        $dealerQtyTarget = DB::table('invoice_items')
                                ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                                ->where('invoices.salesman_id', $emp->id)
                                ->where('invoices.status', 1)
                                ->whereNotNull('invoices.dealer_id')
                                ->whereYear('invoices.invoice_date', $year)
                                ->whereMonth('invoices.invoice_date', $month)
                                ->where('invoice_items.product_id', $target->product_id)
                                ->sum('invoice_items.quantity') ?? 0;

                        if ($customerQtyTarget > 0) {
                            $mrpCommission += $customerQtyTarget * $fixed;
                        }

                        if ($dealerQtyTarget > 0) {
                            $dpCommission += $dealerQtyTarget * $fixed;
                        }
                    }
                }

                // ===================== SALARY =====================
                $salary = DB::table('salary_configurations')
                    ->where('salesman_id', $emp->id)
                    ->first();

                $basic = $salary->basic_salary ?? 0;
                $allowance = $salary->allowance ?? 0;
                $dailySalary = $salary->daily_salary ?? 0;
                $hourlySalary = $salary->hourly_salary ?? 0;

                // Absent
                $absentDays = DB::table('attendances')
                    ->where('salesman_id', $emp->id)
                    ->where('status', 'A')
                    ->whereYear('attendance_date', $year)
                    ->whereMonth('attendance_date', $month)
                    ->count();

                $attendanceAmount = $absentDays * $dailySalary;

                // Over Time & Late
                $overTimeData = DB::table('over_times')
                    ->where('salesman_id', $emp->id)
                    ->where('status', 1)
                    ->whereYear('ot_date', $year)
                    ->whereMonth('ot_date', $month)
                    ->select(
                        DB::raw('SUM(late_hours) as total_late_hours'),
                        DB::raw('SUM(over_time_hours) as total_ot_hours')
                    )
                    ->first();

                $lateHours = $overTimeData->total_late_hours ?? 0;
                $overTimeHours = $overTimeData->total_ot_hours ?? 0;

                $lateFee = $lateHours * $hourlySalary;
                $overTimeAmount = $overTimeHours * $hourlySalary * 2;

                // ===================== TAX CALCULATION =====================
                $annualGross = ($basic + $allowance) * 12;

                $taxSlab = DB::table('tax_configurations')
                    ->where('tax_year', $year)
                    ->where('status', 1)
                    ->get();

                $annualTax = 0;
                foreach ($taxSlab as $slab) {
                    $min = (float)$slab->slab_min;
                    $max = $slab->slab_max !== null ? (float)$slab->slab_max : INF;
                    $rate = (float)$slab->rate_percent / 100.0;

                    if ($annualGross >= $min && $annualGross <= $max) {
                        $annualTax = $annualGross * $rate;
                        break;
                    }
                }

                $monthlyTax = $annualTax / 12;

                $netpay = ($basic + $allowance + $dpCommission + $mrpCommission + $overTimeAmount)
                    - ($attendanceAmount + $lateFee + $monthlyTax);

                $results[] = [
                    'salesman_id' => $emp->id,
                    'salesman_name' => $emp->name,
                    'salesman_code' => $emp->salesman_code,
                    'salary_month' => Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d'),
                    'basic_salary' => round($basic),
                    'allowance' => round($allowance),
                    'mrp_commission' => round($mrpCommission),
                    'dp_commission' => round($dpCommission),
                    'late' => round($lateFee),
                    'over_time' => round($overTimeAmount),
                    'attendance' => round($attendanceAmount, 2),
                    'target_bonus' => 0,
                    'target_loss' => 0,
                    'gross_salary' => round($basic + $allowance + $dpCommission + $mrpCommission + $overTimeAmount),
                    'tax' => round($monthlyTax, 2),
                    'netpay' => round($netpay),
                ];
            }

            return returnData(2000, $results);

        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Something Went Wrong!');
        }
    }
    public function create()
    {
        //
    }

    public function show($salaryId)
    {
        try {
            // Get all payroll entries for this salary_id
            $salaries = Payroll::with('salesman:id,name')
                ->where('salary_id', $salaryId)
                ->get();

            if ($salaries->isEmpty()) {
                return returnData(404, null, 'Salary not found');
            }

            // Map totals for frontend (optional)
            $salariesData = $salaries->map(function($item) {
                return [
                    'id' => $item->id,
                    'salesman' => $item->salesman,
                    'salesman_code' => $item->salesman_code,
                    'salary_month' => $item->salary_month,
                    'basic_salary' => $item->basic_salary,
                    'allowance' => $item->allowance,
                    'over_time' => $item->over_time,
                    'dp_commission' => $item->dp_commission,
                    'mrp_commission' => $item->mrp_commission,
                    'bonus' => $item->bonus,
                    'late' => $item->late,
                    'attendance' => $item->attendance,
                    'tax' => $item->tax,
                    'netpay' => $item->netpay,
                ];
            });

            return returnData(2000, $salariesData);

        } catch (\Exception $e) {
            return returnData(5000, $e->getMessage(), 'Something went wrong');
        }
    }
    public function store(Request $request)
    {
        try {
            $auth = auth()->user();
            $input = $request->all();

            if (!is_array($input) || empty($input)) {
                return returnData(5000, null, 'Invalid data format.');
            }

            $monthlyTotals = [
                'user_id' => $auth->id,
                'warehouse_id' => ($auth->warehouse_id != 0) ? $auth->warehouse_id : null,
                'salary_month' => $input[0]['salary_month'] ?? null,
                'total_basic_salary' => 0,
                'total_allowance' => 0,
                'total_over_time' => 0,
                'total_dp_commission' => 0,
                'total_mrp_commission' => 0,
                'total_bonus' => 0,
                'total_late' => 0,
                'total_attendance' => 0,
                'total_tax' => 0,
                'total_netpay' => 0,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $payrollRows = []; // Save individual payrolls temporarily

            foreach ($input as $index => $payrollData) {
                $payrollData['warehouse_id'] = $monthlyTotals['warehouse_id'];
                $payrollData['user_id'] = $auth->id;

                // Duplicate check
                $existingRecord = $this->model
                    ->where('salesman_id', $payrollData['salesman_id'])
                    ->where('salary_month', $payrollData['salary_month'])
                    ->first();

                if ($existingRecord) {
                    return returnData(3000, null, "This Month's Data already exists for salesman ID {$payrollData['salesman_id']}");
                }

                // Validate payroll
                $validate = $this->model->validate($payrollData);
                if ($validate->fails()) {
                    return returnData(5000, $validate->errors()->first(), "Validation Error in record " . ($index + 1));
                }

                // Add to monthly totals
                $monthlyTotals['total_basic_salary'] += $payrollData['basic_salary'] ?? 0;
                $monthlyTotals['total_allowance'] += $payrollData['allowance'] ?? 0;
                $monthlyTotals['total_over_time'] += $payrollData['over_time'] ?? 0;
                $monthlyTotals['total_dp_commission'] += $payrollData['dp_commission'] ?? 0;
                $monthlyTotals['total_mrp_commission'] += $payrollData['mrp_commission'] ?? 0;
                $monthlyTotals['total_bonus'] += $payrollData['bonus'] ?? 0;
                $monthlyTotals['total_late'] += $payrollData['late'] ?? 0;
                $monthlyTotals['total_attendance'] += $payrollData['attendance'] ?? 0;
                $monthlyTotals['total_tax'] += $payrollData['tax'] ?? 0;
                $monthlyTotals['total_netpay'] += $payrollData['netpay'] ?? 0;

                $payrollRows[] = $payrollData; // Save for later insert
            }

            // Insert monthly totals first
            $monthlyId = DB::table('monthly_salaries')->insertGetId($monthlyTotals);

            // Insert individual payrolls with monthly_id link
            foreach ($payrollRows as &$row) {
                $row['salary_id'] = $monthlyId; // link to monthly summary
                $this->model->insert($row);
            }

            return returnData(2000, null, 'Payrolls and Monthly Totals added successfully!');

        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong!');
        }
    }
//    private function calculateSalaryForEmployee($emp, $salary, $territory, $year, $month)
//    {
//        if (!$salary) {
//            return [
//                'salesman_id' => $emp->id,
//                'salesman_name' => $emp->name,
//                'salesman_code' => $emp->salesman_code,
//                'error' => 'Salary structure not found'
//            ];
//        }
//
//        $basic = (float)$salary->basic_salary;
//        $achieved = (float)($territory->achieved_amount ?? 0);
//        $target = (float)($territory->target_amount ?? 0);
//
//        $achievementPercent = (float)($territory->achievement_percent ?? 0);
//
//
//        $totalEarning = 0.0;
//        $totalDeduction = 0.0;
//
//        // Load slabs (assumes numeric columns: slab_min_percent, slab_max_percent, bonus_amount)
//        $slabs = DB::table('sales_bonus_slabs')->where('status', 1)->get();
//
//
//        foreach ($slabs as $row) {
//            $min = (float)$row->slab_min_percent;
//            $max = (float)$row->slab_max_percent;
//            $percent = (float)$row->bonus_amount;
//
//            if ($row->bonus_type == 2) {
//                if ($achievementPercent >= $min && $achievementPercent <= $max) {
//                    $totalDeduction = $basic * ($percent / 100.0);
//                    break;
//                }
//            }
//        }
//        $fillUp = 0.0;
//        foreach ($slabs as $row) {
//            $min = (float)$row->slab_min_percent;
//            $max = (float)$row->slab_max_percent;
//            $percent = (float)$row->bonus_amount;
//            $fillUp = $achieved - $target;
//            if ($row->bonus_type == 1 && $fillUp > 0) {
//                if ($achievementPercent >= $min && $achievementPercent <= $max) {
//                    $totalEarning = $fillUp * ($percent / 100.0);
//                    break;
//                }
//            }
//        }
//
//
//        // Final
//        $gross = $basic + $totalEarning - $totalDeduction;
//
//        // ******* Bangladesh tax dedcution system wise calculation *******
//        $grossMonthly = $basic + $totalEarning - $totalDeduction;
//        $grossAnnual = $grossMonthly * 12.0;
//
//        $exempted = min($grossAnnual / 3.0, 450000.0);
//        $taxableAnnual = max(0.0, $grossAnnual - $exempted);
//
//        // Rebate Rules Description
//        $actualInvestment = $actualInvestment ?? 0.0;
//        $rebate_by_taxable = $taxableAnnual * 0.03;                 // 3% of taxable
//        $rebate_by_invest  = $actualInvestment * 0.15;              // 15% of actual investment
//        $rebateCap         = 1000000.0;                            // 10 lac cap
//        $rebateAmount = min($rebate_by_taxable, $rebate_by_invest, $rebateCap);
//
//        $taxSlabs = DB::table('tax_configurations')
//            ->where('tax_year', $year)
//            ->where('status', 1)
//            ->orderBy('slab_min')
//            ->get();
//
//        $taxAnnual = 0.0;
//        $remaining = $taxableAnnual;
//        $prevLimit = 0.0;
//        foreach ($taxSlabs as $slab) {
//            $slabMin = (float)$slab->slab_min;
//            $slabMax = $slab->slab_max !== null ? (float)$slab->slab_max : INF;
//            $rate = (float)$slab->rate_percent / 100.0;
//
//            $start = max($prevLimit, $slabMin);
//            $end = min($taxableAnnual, $slabMax);
//
//            if ($end > $start) {
//                $inSlab = $end - $start;
//                $taxAnnual += $inSlab * $rate;
//            }
//
//            $prevLimit = max($prevLimit, $slabMax);
//            if ($prevLimit >= $taxableAnnual) break;
//        }
//
//        // Apply rebate and minimum annual tax only when computed tax > 0
//        $minimumAnnualTax = 5000.0;
//        if ($taxAnnual > 0.0) {
//            $taxAfterRebateAnnual = max(0.0, $taxAnnual - $rebateAmount);
//            $taxAfterRebateAnnual = max($taxAfterRebateAnnual, $minimumAnnualTax);
//        } else {
//            $taxAfterRebateAnnual = 0.0;
//        }
//
//        $monthlyTax = $taxAfterRebateAnnual / 12.0;
//        // ******* Bangladesh tax dedcution system wise calculation end*******
//
//
//        $net = $gross - $monthlyTax;
//
//        $date = date('Y-m-t', strtotime("$year-$month-01"));
//
//        return [
//            'salesman_id' => $emp->id,
//            'salesman_name' => $emp->name,
//            'salary_month' => $date,
//            'salesman_code' => $emp->salesman_code,
//            'basic_salary' => round($basic),
//            'allowance' => round($salary->allowance),
//            'mrp_commission' => round($emp->mrp_commission),
//            'dp_commission' => round($emp->dp_commission),
//            'dp_commission_percent' => round($emp->dp_commission_percent),
//            'mrp_commission_percent' => round($emp->mrp_commission_percent),
//            'late' => round($emp->late_fee),
//            'over_time' => round($emp->over_time_amount),
//            'netpay' => round($emp->netpay),
//            'attendance' => round($emp->attendance_amount, 2),
//            'target_bonus' => round($totalEarning, 2),
//            'target_loss' => round($totalDeduction, 2),
//            'gross_salary' => round($gross, 2),
//            'tax' => round($monthlyTax, 2),
//            'net_salary' => round($net, 2),
//            'territory_target' => $target,
//            'territory_achieved' => $achieved,
//            'achievement_percent' => round($achievementPercent, 2),
//            'fill_up_amount' => round($fillUp, 2),
//        ];
//    }





    public function payrollPayslips(Request $request)
    {
        try {
            $year = $request->input('year');
            $month = $request->input('month');
            $keyword = $request->input('keyword'); // Filter value
            $warehouse_id = auth()->user()->warehouse_id;

            $query = DB::table('monthly_salaries')
                ->when($warehouse_id && $warehouse_id != 0, function($q) use ($warehouse_id) {
                    $q->where('warehouse_id', $warehouse_id);
                })
                ->when($year, function($q) use ($year) {
                    $q->whereYear('salary_month', $year);
                })
                // Keyword jodi month hoy (e.g. 01, 05, 12)
                ->when($keyword, function($q) use ($keyword) {
                    $q->where('salary_month', 'like', '%' . $keyword . '%');
                });

            $monthlySalaries = $query->get();

            return returnData(2000, $monthlySalaries);

        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }


    public function editSalary(Request $request)
    {
        try {
            $data = $request->input('data');
            if (!$data || !isset($data['id'])) {
                return returnData(5000, null, 'Invalid data provided.');
            }
            // dd($data);

            $payroll = $this->model->find($data['id']);
            if (!$payroll) {
                return returnData(3000, null, 'Payroll record not found.');
            }

            $payroll->basic_salary = $data['basic_salary'] ?? $payroll->basic_salary;
            $payroll->target_bonus = $data['target_bonus'] ?? $payroll->target_bonus;
            $payroll->target_loss = $data['target_loss'] ?? $payroll->target_loss;
            $payroll->gross_salary = $data['gross_salary'] ?? $payroll->gross_salary;
            $payroll->tax = $data['tax'] ?? $payroll->tax;
            $payroll->net_salary = $data['net_salary'] ?? $payroll->net_salary;

            $payroll->save();

            return returnData(2000, null, 'Salary data updated successfully!');
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }
    public function Payslips(Request $request)
    {
        try {
            $year = $request->input('year');
            $month = $request->input('month');
            $keyword = request()->input('keyword');
            $salesman_id = $request->input('salesman_id');

            $payrolls = $this->model->with('salesman:id,name')
                ->checkWarehouse()
//                ->when($year, fn($q) => $q->whereYear('salary_month', $year))
//                ->when($month, fn($q) => $q->whereMonth('salary_month', $month))
                ->when($salesman_id, function ($query) use ($salesman_id) {
                    $query->where('salesman_id', 'Like', "%$salesman_id%");
                })
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where('salary_month', 'Like', "%$keyword%");
                })
                ->get();

            return returnData(2000, $payrolls);
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }
    public function destroy($id)
    {
        try {
            $data = DB::table('monthly_salaries')->where('id', $id)->first();
            if (!$data) {
                return returnData(5000, null, 'Data Not found');
            }

            DB::table('payrolls')->where('salary_id', $id)->delete();

            DB::table('monthly_salaries')->where('id', $id)->delete();

            return returnData(2000, $data, 'Successfully Deleted');

        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }
}

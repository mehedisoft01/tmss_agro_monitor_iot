<?php

namespace App\Http\Controllers\Backend\HumanResource;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\HumanResource\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    use Helper;

    public function __construct()
    {
        $this->model = new Attendance();
    }
    public function index()
    {
        try {

            $monthInput = request()->input('month');

            if (!$monthInput) {
                return returnData(5000, null, 'Month is required');
            }

            [$year, $month] = explode('-', $monthInput);

            $attendance = Attendance::whereYear('attendance_date', $year)
                ->whereMonth('attendance_date', $month)
                ->get();

            $holidays = DB::table('holiday_configurations')
                ->where('holiday_year', $year)
                ->whereMonth('holiday_date', $month)
                ->where('status', 1)
                ->get(['holiday_date']);

            return returnData(2000, [
                'attendance' => $attendance,
                'holidays'   => $holidays
            ]);

        } catch (\Exception $e) {
            return returnData(5000, $e->getMessage());
        }
    }
    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        if (!can('attendance.store')) {
            return $this->notPermitted();
        }
        $auth = auth()->user();

        $salesmen = $request->salesman ?? [];
        $selectedMonth = $request->selected_month ?? date('Y-m');

        foreach ($salesmen as $staff) {
            if (!isset($staff['attendance_days'])) continue;

            foreach ($staff['attendance_days'] as $day => $value) {
                $status = $value['status'] ?? 'A';

                $attendanceDate = $selectedMonth . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);

                $attendance = Attendance::where('salesman_id', $staff['id'])
                    ->where('attendance_date', $attendanceDate)
                    ->first();

                if ($attendance) {
                    $attendance->update([
                        'status' => $status,
                        'user_id' => $auth->id
                    ]);
                } else {
                    Attendance::create([
                        'salesman_id' => $staff['id'],
                        'attendance_date' => $attendanceDate,
                        'status' => $status,
                        'user_id' => $auth->id
                    ]);
                }
            }
        }
        return response()->json([
            'status' => 2000,
            'message' => 'Attendance saved successfully.'
        ]);
    }



    public function show(string $id)
    {
        //
    }


    public function update(Request $request, $id)
    {

    }

    public function destroy($id)
    {
        if (!can('attendance.destroy')) {
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
}

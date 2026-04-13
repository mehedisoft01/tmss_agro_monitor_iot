<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\RBAC\Module;
use App\Models\RBAC\Permission;
use App\Models\RBAC\RoleModules;
use Dompdf\Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class HolidayConfigurationController extends Controller
{

    use Helper;

    public function __construct()
    {
        $this->model = new Holiday();
    }

    public function index()
    {
        try {
            $keyword = request()->input('keyword');
            $holiday_type = request()->input('holiday_type');
            $holiday_date = request()->input('holiday_date');
            $perPage = request()->input('per_page');
            $data = $this->model
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where('remarks', 'Like', "%$keyword%");
                })
                ->when($holiday_type, function ($query) use ($holiday_type) {
                    $query->where('holiday_type', 'Like', "%$holiday_type%");
                })
                ->when($holiday_date, function ($query) use ($holiday_date) {
                    $query->where('holiday_date', 'Like', "%$holiday_date%");
                })
                ->orderBy('holiday_date','desc')
                ->paginate($perPage);

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
        try {
            $input = $request->all();
            $validation = $this->model->validate($input);
            if ($validation->fails()) {
                return response()->json(['status' => 3000, 'errors' => $validation->errors()]);
            }

            if ((int)$input['holiday_type'] === 1) {
                $year = $input['year'];
                $dayName = strtolower($input['holiday_days']);
                $dates = [];

                $startDate = Carbon::create($year, 1, 1);
                $endDate   = Carbon::create($year, 12, 31);

                while ($startDate->lte($endDate)) {

                    if (strtolower($startDate->format('l')) === $dayName) {
                        $dates[] = $startDate->toDateString();
                    }

                    $startDate->addDay();
                }

                foreach ($dates as $date) {
                    $existingHoliday = $this->model->where('holiday_type', $input['holiday_type'])->where('holiday_date', $date)
                        ->where('status',1)->first();
                    if (!$existingHoliday) {
                        $this->model->create([
                            'holiday_type' => $input['holiday_type'],
                            'holiday_days' => date('l', strtotime($date)),
                            'holiday_date' => $date,
                            'holiday_year' => $year,
                            'remarks' => "Weekend Holiday"
                        ]);
                    }
                }
            }
            if ((int)$input['holiday_type'] === 2) {
                $fix_holiday = collect($request->input('fix_holiday'))->where('checked', 1)->toArray();
                foreach ($fix_holiday as $holiday) {
                    $monthDay = $holiday['value'];
                    $year = $input['year'];
                    $formatDate = $year.'-'.$monthDay;
                    $holidayDate = Carbon::parse($formatDate);
                    $holidayDays = $holidayDate->format('l');
                    $existingHoliday = $this->model->where('holiday_type', $input['holiday_type'])
                        ->where('holiday_date', $holidayDate)->where('status',1)->first();

                    if (!$existingHoliday) {
                        $this->model->create([
                            'holiday_type' => $input['holiday_type'],
                            'holiday_days' => $holidayDays,
                            'holiday_date' => $holidayDate,
                            'holiday_year' => $year,
                            'remarks' => $holiday['name'],
                        ]);
                    }
                }
            }
            if ((int)$input['holiday_type'] === 3) {
                if (empty($input['to_date'])) {
                    $holidayDate = Carbon::parse($input['to_date']);
                    $dayOfWeek = $holidayDate->format('l');
                    $existingHoliday = $this->model->where('holiday_type', $input['holiday_type'])
                        ->where('status',1)
                        ->where('holiday_date', $holidayDate->toDateString())
                        ->first();
                    if (!$existingHoliday) {
                        $this->model->create([
                            'holiday_type' => $input['holiday_type'],
                            'holiday_days' => $dayOfWeek,
                            'holiday_date' => $holidayDate->toDateString(),
                            'holiday_year' => $holidayDate->year,
                            'remarks' => $input['remarks'],
                        ]);
                    } else {
                        return returnData(3000, null, 'Holiday already exists for the same date.');
                    }
                } else {
                    $fromDate = Carbon::parse($input['from_date']);
                    $toDate = Carbon::parse($input['to_date']);
                    while ($fromDate->lte($toDate)) {
                        $dayOfWeek = $fromDate->format('l');
                        $holidayDate = $fromDate->toDateString();
                        $existingHoliday = $this->model->where('holiday_type', $input['holiday_type'])
                            ->where('holiday_date', $holidayDate)
                            ->where('status',1)
                            ->first();
                        if (!$existingHoliday) {
                            $this->model->create([
                                'holiday_type' => $input['holiday_type'],
                                'holiday_days' => $dayOfWeek,
                                'holiday_date' => $holidayDate,
                                'holiday_year' => $fromDate->year,
                                'remarks' => $input['remarks'],
                            ]);
                        }
                        $fromDate->addDay();
                    }
                }
            }
            return returnData(2000, null, 'Successfully Inserted');
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        try {
            $input = $request->all();
            $validation = $this->model->validate($input);
            if ($validation->fails()) {
                return response()->json(['status' => 2000, 'errors' => $validation->errors()], 200);
            }
            $data = $this->model->find($id);
            if ($data) {
                $data->update($input);
                return returnData(2000, null, 'Successfully Updated');
            }
            return returnData(5000, null, 'Data Not found');
        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }

    public function destroy($id)
    {
        try {
            $data = $this->model->where('id', $id)->first();
            if ($data) {
                $data->delete();
                return returnData(2000, $data, 'Successfully Deleted');
            }
            return returnData(5000, null, 'Data Not found');

        } catch (\Exception $exception) {
            return returnData(5000, $exception->getMessage(), 'Whoops, Something Went Wrong..!!');
        }
    }

    public function multiple(Request $request)
    {
        $selectedKeys = $request->input('selectedKeys', []);
        $ids = is_array($selectedKeys) ? $selectedKeys : [];

        if (empty($ids)) {
            return returnData(4000, null, 'No IDs provided');
        }

        $deleted = [];
        $errors = [];

        foreach ($ids as $id) {
            $data = Holiday::where('id', $id)->first();

            if (!$data) {
                $errors[] = "ID {$id} not found";
                continue;
            }
            // Delete main record
            $data->delete();
            $deleted[] = $id;
        }

        return returnData(2000, null,count($deleted)." Item Deleted and ".json_encode($errors)." Item Not Deleted");
    }
}

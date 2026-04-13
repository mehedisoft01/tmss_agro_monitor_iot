<?php

namespace App\Http\Controllers\Backend\HumanResource;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\HumanResource\OverTime;
use Illuminate\Http\Request;

class OverTimeController extends Controller
{
    use Helper;

    public function __construct()
    {
        $this->model = new OverTime();
    }
    public function index()
    {
        if (!can('over_time.index')) {
            return $this->notPermitted();
        }
        try {
            $keyword = request()->input('keyword');
            $perPage = request()->input('per_page');

            $data = $this->model
                ->with([
                    'salaryConfig' => function($query) {
                        $query->select('salesman_id', 'hourly_salary', 'basic_salary');
                    },
                    'salesman' => function($query) {
                        $query->select('id', 'name', 'salesman_code');
                    }
                ])
                ->when($keyword, function ($query) use ($keyword) {
                    $query->whereHas('salesman', function ($q) use ($keyword) {
                        $q->where('name', 'LIKE', "%{$keyword}%")
                            ->orWhere('salesman_code', 'LIKE', "%{$keyword}%")
                            ->orWhere('ot_date', 'LIKE', "%{$keyword}%");
                    });
                })
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
        if (!can('over_time.store')) {
            return $this->notPermitted();
        }
        try {
            $auth = auth()->user();
            $salesmen = $request->salesman ?? [];

            foreach ($salesmen as $salesman) {
                if (!isset($salesman['overtime']) || !is_array($salesman['overtime'])) {
                    continue;
                }

                foreach ($salesman['overtime'] as $date => $ot) {
                    $existing = OverTime::where('salesman_id', $salesman['id'])
                        ->where('ot_date', $date)
                        ->first();

                    if ($existing) {
                        return response()->json([
                            'status' => 3000,
                            'message' => "Overtime date {$date} has already been submitted."
                        ]);
                    }
                    OverTime::create([
                        'salesman_id' => $salesman['id'],
                        'ot_date' => $date,
                        'over_time_hours' => $ot['hours'] ?? 0,
                        'late_hours' => $ot['late_hours'] ?? 0,
                        'user_id' => $auth->id,
                    ]);
                }
            }

            return response()->json(['status' => 2000, 'message' => 'Overtime saved successfully.']);

        } catch (\Exception $e) {
            return response()->json(['status' => 5000, 'message' => $e->getMessage()]);
        }
    }



    public function show(string $id)
    {
        //
    }

    public function edit($id)
    {

        $record = $this->model->with('salesman')->find($id);

        if (!$record) {
            return returnData(5000, [], 'Data Not Found..!!');
        }
        $overtime = [
            $record->ot_date => [
                'hours' => (float) $record->over_time_hours,
                'late_hours' => (float) $record->late_hours,
            ]
        ];

        $salesman = [
            'id' => $record->salesman_id,
            'name' => $record->salesman->name ?? '',
            'salesman_code' => $record->salesman->salesman_code ?? '',
            'overtime' => $overtime
        ];

        return returnData(2000, ['salesman' => [$salesman]]);
    }

    public function update(Request $request, $id)
    {
        if (!can('over_time.update')) {
            return $this->notPermitted();
        }
        try {
            $auth = auth()->user();
            $salesmen = $request->salesman ?? [];

            foreach ($salesmen as $salesman) {
                if (!isset($salesman['overtime']) || !is_array($salesman['overtime'])) continue;

                foreach ($salesman['overtime'] as $date => $ot) {
                    OverTime::updateOrCreate(
                        [
                            'salesman_id' => $salesman['id'],
                            'ot_date' => $date
                        ],
                        [
                            'over_time_hours' => $ot['hours'] ?? 0,
                            'late_hours' => $ot['late_hours'] ?? 0,
                            'over_time_amount' => $ot['amount'] ?? 0,
                            'user_id' => $auth->id
                        ]
                    );
                }
            }

            return response()->json([
                'status' => 2000,
                'message' => 'Overtime updated successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 5000,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function destroy($id)
    {
        if (!can('over_time.destroy')) {
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

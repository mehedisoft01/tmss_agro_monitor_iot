<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SiteReadingController extends Controller
{
    use Helper;

    public function index(Request $request)
    {
        $device = $request->input('device_id');
        $date_from = $request->input('date_from');
        $date_to = $request->input('date_to');

        $data = DB::table('site_readings as sr')
            ->leftJoin('soil_devices as d', 'sr.site_id', '=', 'd.id')
            ->when($device, function ($query) use ($device) {
                $query->where('d.device_id', $device);
            })
            ->when($date_from && $date_to, function ($query) use ($date_from, $date_to) {
                $query->whereBetween('sr.created_at', [
                    $date_from . ' 00:00:00',
                    $date_to . ' 23:59:59'
                ]);
            })
            ->orderBy('sr.created_at', 'desc')
            ->orderBy('sr.created_at')
            ->select(
                'sr.*',
                'd.device_name',
                'd.device_id'
            )
            ->paginate($request->input('perPage', 15))
            ->through(function ($item) {
                $item->formatted_date = \Carbon\Carbon::parse($item->created_at)
                    ->format('Y-m-d H:i');
                return $item;
            });

        return returnData(2000, $data);
    }
}

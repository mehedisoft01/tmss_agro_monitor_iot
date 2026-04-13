<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppNotificationController extends Controller
{
    public function index($isInSide = false, $limit = 5, $page = 0)
    {
        $limit = request()->input('limit') ? request()->input('limit') : $limit;
        $page = request()->input('page') ? request()->input('page') : $page;
        $skip = ($page - 1) * $limit;
        $userId = auth()->id();

        $notificationData = [
            'total' => DB::table('app_notifications')->where('status', 0)->where('send_to', $userId)->count(),
            'data' => AppNotification::selectRaw("*, 0 as toggle")->where('send_to', $userId)->where('status', 0)->limit($limit)->skip($skip)->orderBy('id', 'desc')->get(),
            'limit' => $limit,
            'page' => $page,
        ];

        if ($isInSide) {
            return $notificationData;
        }

        return returnData(2000, $notificationData);
    }

    public function show($id)
    {
        $authID =  auth()->user()->id;
        AppNotification::where('id', $id)->update([
            'status' => 1,
            'notification_to' => $authID,
        ]);
        return returnData(2000, $id);
    }
}

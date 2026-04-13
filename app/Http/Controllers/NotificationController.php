<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $is_read = request()->input('is_read');

        $perPage = $is_read !== null ? 2 : input('perPage');

        $data = Notification::with('device')
            ->orderBy('id', 'desc')
            ->when($is_read !== null, function ($query) use ($is_read) {
                $query->where('is_read', $is_read);
            })
            ->paginate($perPage);

        return returnData(2000, $data);
    }

    public function unreadCount()
    {
        $count = Notification::where('is_read', 0)->count();
        return returnData(2000, $count);
    }

    public function markAsRead($id)
    {
        $data = Notification::find($id);
        if ($data) {
            $data->is_read = 1;
            $data->save();
        }
        return returnData(2000);
    }
}

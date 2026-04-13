<?php

namespace App\Http\Controllers;

use App\Models\DeviceStatus;
use Illuminate\Http\Request;

class DeviceStatusController extends Controller
{

    public function index()
    {
        $data = DeviceStatus::with('device')->orderBy('id','desc')->paginate(input('perPage'));
        return returnData(2000,$data);
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}

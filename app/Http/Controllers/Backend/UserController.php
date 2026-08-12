<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\RBAC\Module;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use Helper;
    public function __construct()
    {
        if (!can(request()->route()->action['as'])) {
            return returnData(5001, null, 'You are not authorized to access this page');
        }
        $this->model = new User();
    }

    public function index()
    {
        $featureId = auth()->user()->last_feature_id;

        $data = User::with('fissureNames')
            ->whereHas('fissureNames', function ($q) use ($featureId) {
                $q->where('feature_id', $featureId);
            })
            ->paginate(input('perPage'));

        return returnData(2000, $data);
    }

    public function create()
    {
        //
    }
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $loggedInUser = auth()->user();
            $featureId = $loggedInUser->last_feature_id;
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->username = $request->username;
            $user->role_id = $request->role_id;
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }
            $user->last_feature_id = $featureId;
            $user->save();

            if ($featureId) {

                DB::table('user_features')->insert([
                    'user_id' => $user->id,
                    'feature_id' => $featureId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::commit();
            return returnData(2000, null, 'User created successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            return returnData(5000, $e->getMessage());
        }
    }
    public function show($id)
    {
        $perPage = request()->input('perPage');
        $data = $this->model->where('id', $id)
            ->orderBy('id', 'DESC')
            ->paginate($perPage);

        return returnData(2000, $data);
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {

            $loggedInUser = auth()->user();

            $featureId = $loggedInUser->last_feature_id;

            $user = User::findOrFail($id);
            $user->name = $request->name;
            $user->email = $request->email;
            $user->username = $request->username;
            $user->role_id = $request->role_id;
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }
            $user->last_feature_id = $featureId;
            $user->save();
            DB::table('user_features')->where('user_id', $user->id)->delete();
            if ($featureId) {

                DB::table('user_features')->insert([
                    'user_id' => $user->id,
                    'feature_id' => $featureId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
            return returnData(2000, null, 'User updated successfully');

        } catch (\Throwable $e) {
            DB::rollBack();
            return returnData(5000, $e->getMessage());
        }
    }
    public function destroy($id)
    {
        if (!can('users.destroy')) {
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

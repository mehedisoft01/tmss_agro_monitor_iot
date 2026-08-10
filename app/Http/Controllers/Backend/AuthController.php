<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SupportController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function login(){
        return view('auth.login');
    }

//    public function doLogin(Request $request){
//        $credentials = [
//            'username' => $request->username,
//            'password' => $request->password,
//        ];
//        $remember_me = $request->input('remember_me');
//
//        $remember = ($remember_me && $remember_me == 'on') ? true : false;
//
//        if (Auth::attempt($credentials, $remember)) {
//            return redirect('admin/dashboard');
//        }
//
//        return back()->withErrors(['login' => 'Invalid credentials']);
//    }
    public function doLogin(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);
        $credentials = [
            'username' => $request->username,
            'password' => $request->password,
        ];
        if (Auth::attempt($credentials)) {

            $user = Auth::user();
            $features = DB::table('user_features')
                ->where('user_id', $user->id)
                ->pluck('feature_id');
            if ($features->count() == 0) {
                Auth::logout();

                return back()->withErrors([
                    'login' => 'No feature assigned to this user'
                ]);
            }
            $lastFeature = $user->last_feature_id;
            if (!$lastFeature || !$features->contains($lastFeature)) {
                $lastFeature = $features->first();
            }
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'last_feature_id' => $lastFeature
                ]);
            session([
                'feature_id' => $lastFeature,
            ]);

            return redirect('/admin/dashboard');
        }
        return back()->withErrors([
            'login' => 'Invalid credentials'
        ]);
    }

    public function logout(){
        Auth::logout();
        return redirect(route('login'));
    }

    public function index(){
        $user = auth()->user();
        return returnData(2000, $user);
    }
    public function store(Request $request)
    {
        $user = User::find(auth()->id());
        if (!$user) {
            return returnData(5000, null, 'User Not Found');
        }

        $reqFor = $request->input('request');

        if ($reqFor === 'theme') {
            $request->validate(['theme' => 'required|string']);
            $user->theme = $request->input('theme');
            $user->save();

            return returnData(2000, $user, 'Successfully Theme Updated');
        }

        $conObj = new SupportController();
        $local = collect($conObj->getLocals())->pluck('locale')->toArray();
        $arrString = implode(',', $local);

        if ($reqFor === 'locale') {
            $request->validate(['locale' => "required|string|in:$arrString"]);

            $user->locale = $request->input('locale');
            $user->save();

            return returnData(2000, $user, 'Successfully Locale Updated');
        }

        $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . auth()->id(),
            'phone' => 'nullable|string|max:20',
            'theme' => 'nullable|string',
        ]);

        $user->fill($request->all());
        $user->save();

        return returnData(2000, $user, 'Successfully Updated');
    }

    public function update(Request $request)
    {
        $user = User::find(auth()->id());
        if (!$user) {
            return returnData(5000, null, 'User Not Found');
        }
        $request->validate([
            'name'  => 'nullable',
            'email' => 'nullable',
            'username' => 'nullable',
            'phone' => 'nullable',
            'image' => 'nullable',
        ]);

        $user->name  = $request->name ?? $user->name;
        $user->email = $request->email ?? $user->email;
        $user->username = $request->username ?? $user->username;
        $user->phone = $request->phone ?? $user->phone;
        $user->image = $request->image ?? $user->image;
        $user->locale = $request->locale ?? $user->locale;
        $user->theme = $request->theme ?? $user->theme;


        if ($request->filled('password') && $request->filled('new_password')) {
            if (!\Hash::check($request->password, $user->password)) {
                return returnData(5000, null, 'Current password is incorrect');
            }
            $request->validate([
                'new_password' => ''
            ]);
            $user->password = \Hash::make($request->new_password);
        }

        $user->save();

        return returnData(2000, $user, 'Profile updated successfully');
    }

    public function selectFeature(Request $request)
    {
        $request->validate([
            'feature_id' => 'required'
        ]);

        $user = Auth::user();

        DB::table('users')
            ->where('id', $user->id)
            ->update([
                'last_feature_id' => $request->feature_id
            ]);

        session([
            'feature_id' => $request->feature_id,
            'last_feature_id' => $request->feature_id
        ]);
        return response()->json([
            'status' => true,
            'message' => 'Feature updated successfully'
        ]);
    }

    public function feature()
    {
        $user_id = auth()->id();

        $userFeatureIds = DB::table('user_features')
            ->where('user_id', $user_id)
            ->pluck('feature_id')
            ->toArray();

        $features = DB::table('features')
            ->where('status', 1)
            ->whereIn('id', $userFeatureIds)
            ->get();

        return response()->json($features);
    }

}

<?php

namespace App\Http\Controllers\api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\RBAC\Module;
use App\Models\RBAC\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminApiAuthController extends Controller
{
    use Helper;
    public function __construct()
    {
        $this->model = new User();
    }

    public function appInFo()
    {
        $configs = configs(['name', 'logo']);
        $logo = isset($configs['logo']) ? $configs['logo'] : publicImage('images/logo.png');
        return returnData(2000, $logo);
    }

    public function login(Request $request)
    {
        $credentials = [];

        $credentials['password'] = $request->input('password');
        $credentials['username'] = $request->input('username');

        $token = JWTAuth::attempt($credentials);


        try {
            if ($token) {
                return returnData(2000, $this->respondWithToken($token, auth()->user()));
            } else {
                return returnData(4001, 'Invalid Username or Password');
            }
        } catch (JWTAuthException $e) {
            return returnData(5000, 'Failed to create Token');
        }
    }

    public function logout()
    {
        $this->guard()->logout();

        return returnData(2000, null, 'Successfully logged out');
    }
    public function me()
    {
        return returnData(2000, auth()->user());
    }


    public function refresh()
    {
        return $this->respondWithToken($this->guard()->refresh());
    }

    protected function respondWithToken($token, $user = [])
    {
        $role_id = $user->role_id;
        $permissions = Permission::whereHas('role_permissions', function ($query) use ($role_id) {
            $query->where('role_id', $role_id);
        })->get();

        $permittedModules = collect($permissions)->pluck('module_id');
        $permissions = collect($permissions)->pluck('name');

        $menus = Module::whereIn('id', $permittedModules)->get();

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => \auth()->guard('api')->factory()->getTTL() * 60,
            'user' => $user,
            'guard' => 'api', //new added
            'config' => configs(['name', 'logo']),
            'permission' => $permissions,
            'menu' => $menus

        ];
    }

    public function guard()
    {
        return Auth::guard('api');
    }

}

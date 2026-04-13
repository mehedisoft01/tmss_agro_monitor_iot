<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserProfileController extends Controller
{
    use Helper;
    public function __construct()
    {
        $this->model = new User();
    }
    public function index()
    {
        $user = auth()->user();
        $data = $this->model->where('id', $user->id)->orderBy('id','desc')->paginate(15);
        return returnData(2000, $data);
    }


    public function create()
    {
        //
    }


    public function store(Request $request)
    {
        try{

            $user = Auth::user();
            if(auth()->user()){
                $user->name = $request->input('name');
                $user->company_name = $request->input('company_name');
                $user->address = $request->input('address');
                $user->nid = $request->input('nid');
                $user->contact_number = $request->input('contact_number');
                $user->email = $request->input('email');
                $user->layout = $request->input('layout');
                $user->save();
                if($request->input('password')){
                    if (!Hash::check($request->old_password, $user->password)) {
                        return returnData(3000, null, 'Old password is incorrect');
                    }
                    $user->password = Hash::make($request->password);
                }
                $user->save();
            }
            return returnData(2000, null, 'Successfully Updated');
        }catch (\Exception $exception){
            return returnData(5000, $exception->getMessage(), $exception->getMessage());
        }


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

    }


    public function destroy($id)
    {
        //
    }
}

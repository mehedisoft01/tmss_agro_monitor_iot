<?php

namespace App\Http\Controllers\Frontend;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Address\District;
use App\Models\Address\Division;
use App\Models\Address\Upazila;
use App\Models\Dealer\Dealer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FrontendLoginController extends Controller
{
     use Helper;

    public function authApp()
    {
        return view('frontend');
    }

    public function index(){
        return view('web.index');
    }

    public function login(){
        $data['next_url'] = \request()->input('next_url');
        return view('web.auth.login');
    }

    public function doLogin(Request $request){
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = [
            'email' => $request->email,
            'password' => $request->password,
            'approval_status' => 1,
        ];

       if (Auth::guard('dealers')->attempt($credentials)) {
        session()->flash('success', 'Successfully Login');
            return redirect('/auth/dashboard');
    }

       session()->flash('error', 'Credentials did not match');
        return redirect()->back();
    }

    public function logout(){
        Auth::guard('dealers')->logout();
        return redirect(route('web.login'));
    }

    public function authUser(){
        $user = Auth('dealers')->user();
        $userData = $user->makeHidden(['password']);
        return returnData(2000, $userData);
    }


    public function register()
    {
        $divisions = Division::all();
        return view('web.auth.registration', compact('divisions'));
    }

    public function getDistricts($division_id)
    {
        $districts = District::where('division_id', $division_id)->get();
        return response()->json($districts);
    }

    public function getUpazilas($district_id)
    {
        $upazilas = Upazila::where('district_id', $district_id)->get();
        return response()->json($upazilas);
    }

    public function doRegistration(Request $request)
    {
//        dd($request);

        $request->validate([
            'name' => 'required',
            'phone' => 'required|unique:dealers,phone',
            'email' => 'required|email|unique:dealers,email',
            'password' => 'required|min:6',
        ]);

        $dealerCode = generateUniqueCode('DLR', 'dealer_code', 'dealers');
        try {
            DB::beginTransaction();

            $data =  Dealer::create([
                'name'              => $request->name,
                'phone'             => $request->phone,
                'email'             => $request->email,
                'company_name'      => $request->company_name,
                'contact_person'    => $request->contact_person,
                'dealer_reference'  => $request->dealer_reference,
                'city'              => $request->city,
                'country'           => $request->country,
                'registration_date' => $request->registration_date,
                'gst_number'        => $request->gst_number,
                'bank_account'      => $request->bank_account,
                'password'          => Hash::make($request->password),
                'dealer_code'       => $dealerCode,
                'approval_status'   => 0,
                'type'              => 2,
                'attachments'       => null,
            ]);

            Address::create([
                'dealer_id'     => $data->id,
                'type'          => 1,
                'p_division_id' => $request->division,
                'p_district_id' => $request->district,
                'p_upazila_id'  => $request->upazila,
                'p_area'        => $request->p_area,
            ]);

            DB::commit();
            session()->flash('success', 'Registration Successful');
            return redirect("web_login");
        } catch (\Exception $exception) {
            DB::rollBack();
            session()->flash('error', 'Something went wrong, please try again');
            return redirect()->back()->withInput();
        }

    }

}

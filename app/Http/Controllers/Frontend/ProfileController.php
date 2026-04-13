<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Dealer\Dealer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function index(){
        $user = Auth('dealers')->user();
        $userData = $user->makeHidden(['password']);
        return returnData(2000, $userData);
    }

    public function update(Request $request)
    {
        $dealer = Auth::guard('dealers')->user();
        $dealer = Dealer::where('id', $dealer->id)->first();

        $dealer->fill($request->all([
            'password' => 'nullable|string',
            'new_password' => 'nullable|string',
            ]));

        if ($request->filled('password')) {

            if (!Hash::check($request->password, $dealer->password)) {
                return response()->json([
                    'status' => 5000,
                    'message' => "Old password is incorrect"
                ]);
            }

            if (!$request->filled('new_password')) {
                return response()->json([
                    'status' => 5000,
                    'message' => "New password is required"
                ]);
            }

            $validated['password'] = Hash::make($request->new_password);
        }

        $dealer->update($validated);

        return returnData(2000, null, "Profile updated successfully");
    }

}

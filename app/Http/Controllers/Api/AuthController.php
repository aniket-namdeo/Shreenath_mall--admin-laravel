<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\DeliveryUser;
use App\Models\Referrals;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\User_addresses;
use Exception;
use Illuminate\Support\Str;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'contact' => 'required|string|unique:users,contact',
            'password' => 'required|string',
            'referral_code' => 'nullable'
        ], [
            'email.unique' => 'A user with this email already exists.',
            'contact.unique' => 'Mobile number already exists.'
        ]);

        $newUser = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'contact' => $request->input('contact'),
            'password' => Hash::make($request->input('password')),
            'user_type' => 'User',
            'referral_code' => $request->referral_code,
            'my_referral_code' => strtoupper(Str::random(8)),
            'deviceId' => $request->input('deviceId'),
        ]);

        if ($request->referral_code) {
            $referrer = DeliveryUser::where(array('referral_code'=>$request->referral_code))->first();
            $userType = "";

            if($referrer){
                $userType = "marketing";
            }else{
                $referrer = User::where(array('my_referral_code'=>$request->referral_code))->first();
                $userType = "user";
            }
            
            if ($referrer) {
                $referrer->wallet_balance += 20;
                $newUser->wallet_balance += 20;
                $referrer->save();
                $newUser->save();

                Referrals::create([
                    'referrer_id' => $referrer->id,
                    'referred_id' => $newUser->id,
                    'referr_type' => $userType,
                ]);
            }
        }
        return response()->json(['message' => 'User created successfully', 'data' => $newUser, 'status' => true], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'deviceId' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found, Register your account', 'status' => false], 401);
        }
        
        if ($request->deviceId) {
            $user->deviceId = $request->deviceId;
            $user->save();
        }

        if ($user && Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Login successfully',
                'user' => $user,
                'status' => true
            ]);
        } else {
            return response()->json(['message' => 'Please Enter Correct Password', 'status' => false], 401);
        }
    }


    public function forgetPassword(Request $request)
    {
        try {
            $user = User::where('email', $request->email)->first();

            if ($user) {
                $otp = random_int(100000, 999999);
                $user->otp = $otp;
                $user->save();

                $subject = "Reset OTP for Password";
                $message = "$otp -  OTP for change password";

                // change otp status to 0 for always verify new otp
                User::where(array('email' => $request->email))->update(['otp_status' => '0']);

                send_mail($user->email, $subject, $message);
                return response()->json(['status' => true, 'message' => 'Otp send successfully']);
            } else {
                return response()->json(['status' => false, 'message' => 'User not found']);
            }
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => $e]);
        }
    }

    public function verifyOtp(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        if ($user) {
            $userotp = $user->otp;
            $userotp_status = $user->otp_status;
            if ($userotp_status == 0) {
                if ($request->otp == $userotp) {
                    $result = User::where(array('email' => $request->email))->update(['otp_status' => '1']);
                    return response()->json(['status' => true, 'message' => 'Otp Verified']);
                } else {
                    return response()->json(['status' => false, 'message' => 'Otp Incorrect']);
                }
            } else {
                return response()->json(['status' => false, 'message' => 'Otp already verified, send otp again']);
            }
        } else {
            return response()->json(['status' => false, 'message' => 'User not found']);
        }
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'new_password' => 'required|string|min:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['status' => false, 'message' => 'User not found'], 404);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['status' => true, 'message' => 'Password set successfully'], 200);
    }


    public function changePassword(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'old_password' => 'required',
            'new_password' => 'required|string|min:6',
        ]);
        $user = User::where('id', $request->id)->first();
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        // $user = User::where('email', $request->email)->first();

        if ($user && Hash::check($request->old_password, $user->password)) {
            $user->password = Hash::make($request->new_password);
            $user->save();
        } else {
            return response()->json(['status' => false, 'message' => 'Old password is incorrect'], 400);
        }

        return response()->json(['status' => true, 'message' => 'Password set successfully'], 200);
    }

    public function updateProfile(Request $request, $id)
    {
        $data = [];

        if ($request->name) {
            $data['name'] = $request->name;
        }
        if ($request->contact) {
            $data['contact'] = $request->contact;
        }
        if ($request->dob) {
            $data['dob'] = $request->dob;
        }
        if ($request->email) {
            $data['email'] = $request->email;
        }
        if ($request->gender) {
            $data['gender'] = $request->gender;
        }

        if ($request->password) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('profile_image')) {
            $imageName = time() . '_profile_image.' . $request->profile_image->extension();
            $request->profile_image->move(public_path('uploads/user'), $imageName);
            $full_path = "uploads/user/" . $imageName;
            $data['profile_image'] = $full_path;
        }

        $result = User::where('id', $id)->update($data);

        return response()->json([
            'message' => 'User updated successfully',
            'data' => null,
            'status' => true
        ], 200);

    }

    public function getUser($id)
    {
        $result = User::select('id', 'name', 'email', 'contact', 'gender', 'dob', 'profile_image', 'wallet_balance', 'my_referral_code')->where('id', $id)->first();
        if ($result) {

            return response()->json(['status' => true, 'data' => $result], 200);
        } else {
            return response()->json(['status' => false, 'message' => 'User not found'], 404);
        }
    }

    public function addAddress(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'name' => 'required|string',
            'contact' => 'required|integer',
            'address_type' => 'required',
            'house_address' => 'required|string',
            'street_address' => 'required|string',
            'landmark' => 'nullable|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'country' => 'nullable|string',
            'pincode' => 'required|string',
            'default_address' => 'nullable|integer|in:0,1',
        ]);

        $validated['country'] = 101;

        if ($request->latitude) {
            $validated['latitude'] = $request->latitude;
        }
        if ($request->longitude) {
            $validated['longitude'] = $request->longitude;
        }

        $address = User_addresses::create($validated);

        if ($request->input('default_address') == 1) {
            User_addresses::where('user_id', $request->input('user_id'))
                ->where('id', '!=', $address->id)
                ->update(['default_address' => 0]);
        }

        return response()->json(['message' => 'Address created successfully', 'data' => $address], 201);
    }


    public function getUserAddress($id)
    {
        $addresses = User_addresses::where('user_id', $id)->get();

        if ($addresses->isEmpty()) {
            return response()->json(['error' => 'No addresses found for the user'], 404);
        }

        return response()->json(['message' => 'Addresses retrieved successfully', 'data' => $addresses], 200);

    }

    public function getParticularAddress($id)
    {
        $address = User_addresses::where('id', $id)->first();
        if (!$address) {
            return response()->json(['error' => 'No addresses found with this id'], 404);
        }

        return response()->json(['message' => 'Addresses retrieved successfully', 'data' => $address], 200);

    }

    public function addressUpdate(Request $request, $id)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'name' => 'required|string',
            'contact' => 'required|integer',
            'address_type' => 'required',
            'house_address' => 'required|string',
            'street_address' => 'required|string',
            'landmark' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
            'pincode' => 'required|string',
            'default_address' => 'nullable|integer|in:0,1',
        ]);
        $checkaddresses = User_addresses::where('id', $id)->get();

        if ($request->latitude) {
            $validated['latitude'] = $request->latitude;
        }
        if ($request->longitude) {
            $validated['longitude'] = $request->longitude;
        }

        if ($checkaddresses->isEmpty()) {
            return response()->json(['error' => 'No addresses found with this id'], 404);
        }

        $address = User_addresses::findOrFail($id);

        if ($request->input('default_address') == 1) {
            User_addresses::where('user_id', $request->input('user_id'))
                ->where('id', '!=', $id)
                ->update(['default_address' => 0]);
        }

        $address->update($validated);

        return response()->json(['message' => 'Address updated successfully', 'data' => $address]);
    }


    public function deleteAddress($id)
    {
        $address = User_addresses::findOrFail($id);
        $address->delete();
        return response()->json(['message' => 'Address deleted successfully',], 201);
    }

    public function state($country_id)
    {

        $state = State::where(array('country_id' => $country_id))->get();

        return response()->json(['message' => 'Data got successfully', 'data' => $state], 200);
    }

    public function city($name)
    {
        $state = State::where(array('name' => $name))->first();
        $city = City::where(array('state_id' => $state->state_id))->get();
        return response()->json(['message' => 'Data got successfully', 'data' => $city], 200);
    }

    public function getAdminDetails()
    {
        $result = User::select('id', 'contact', 'latitude', 'longitude')->where('user_type', 'Admin')->first();

        if ($result) {
            return response()->json(['status' => true, 'data' => $result], 200);
        } else {
            return response()->json(['status' => false, 'message' => 'User not found'], 404);
        }
    }


}

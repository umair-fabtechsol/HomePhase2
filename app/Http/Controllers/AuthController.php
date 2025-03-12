<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
class AuthController extends Controller
{
    public function form()
    {


        return view('form');
    }
    public function Register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        $data = $request->all();

        $user = User::create($data);

        return response()->json([
            'message' => 'User successfully registered!',
            'user' => $user,
        ], 200);
    }

    public function UpdateUser(Request $request)
    {

        $user = User::find($request->id);

        $user->update(['terms' => $request->terms]);
        return response()->json([
            'user' => $user,
        ], 201);
    }

    public function Userlogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'invalid credentials'
            ], 401);
        }
        if ($user->status == 1) {
            return response()->json([
                'message' => 'You account has been banned'
            ], 401);
        }

        if ($user->terms != 1) {
            return response()->json([
                'message' => 'You must accept the terms and conditions to log in'
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        return [
            'user' => $user,
            'token' => $token
        ];
    }

    public function googleLogin($role)
    {

        return Socialite::driver('google')
            ->stateless()
            ->with(['state' => $role])
            ->redirect();
    }

    public function googleHandle()
    {


        try {
            $user = Socialite::driver('google')->stateless()->user();
            $role = request('state');
            $findUser = User::where('email', $user->email)->first();

            if (!$findUser) {

                $createUser = new User();

                $createUser->name = $user->name;
                $createUser->email = $user->email;
                $createUser->phone = '123456';
                $createUser->role = $role;
                $createUser->password = Hash::make('aszx1234');
                $createUser->terms = 1;
                $createUser->save();

            }
        } catch (Exception $e) {

            dd($e->getMessage());
        }

        $getuser=User::where('email','=',$user->email)->first();
        Auth::login($getuser);
        $token = $getuser->createToken('auth_token')->plainTextToken;
        return [
            'token' =>$token,
            'getuser' => $getuser,

        ];
    }

    public function facebookLogin()
    {

        return Socialite::driver('facebook')->stateless()->redirect();
    }

    public function facebookHandle()
    {

        try {
            $user = Socialite::driver('facebook')->stateless()->user();

            $findUser = User::where('email', $user->email)->first();
            if (!$findUser) {

                $createUser = new User();

                $createUser->name = $user->name;
                $createUser->email = $user->email;
                $createUser->role = 1;
                $createUser->password = Hash::make('aszx1234');
                $createUser->terms = 1;
                $createUser->save();



                return [

                    'createUser' => $createUser,

                ];
            }
        } catch (Exception $e) {

            dd($e->getMessage());
        }
    }

    public function ForgetPassword(Request $request)
    {


        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $token = Password::createToken(User::where('email', $request->email)->first());

        if (!$token) {

            return response()->json(['error' => 'Unable to generate password reset token. Please try again later.'], 500);
        }


        Mail::to($request->email)->send(new ResetPasswordMail($token, $request->email));

        return response()->json(['message' => 'Password reset link sent to your email.'], 200);
    }

    public function ResetPassword(Request $request)
    {

        dd('ok');
    }

    public function ChangePassword(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'new_password.confirmed' => 'The new password and confirm password do not match.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $GetUserDetails = User::where('email', '=', $request->email)->first();

        $GetUserDetails->update(['password' => Hash::make($request->new_password)]);

        return response()->json(['message' => 'Your password has been reset successfully.'], 200);
    }
}
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
        if (!empty($data['phone']) && !str_starts_with($data['phone'], '+')) {
            $data['phone'] = '+' . $data['phone'];
        }
   
        $validator = Validator::make($data, [
            'phone' => ['required', 'phone:AUTO'], 
        ]);
 
        if ($validator->fails()) {
            return response()->json([
                'errors' => ['phone' => ['Invalid phone number']]
            ], 422);
        }
        $data['terms'] =$request->term;  
        $user = User::create($data);
        Auth::login($user);
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'message' => 'User successfully registered!',
            'user' => $user,
            'token' => $token
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
        // firsttimeLogin
        
        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'invalid credentials'
            ], 401);
        }
        if ($user->status == 1) {
            return response()->json([
                'ban' => 'Your account has been banned. Please contact the administrator.',
                'status' => $user->status,
            ], 401);
        }

        if ($user->terms != 1) {
            return response()->json([
                'message' => 'You must accept the terms and conditions to log in'
            ], 403);
        }


        if ($user = User::where('email', $request->email)->first()) {
            if($user->role==2){
                if ($user->firsttimeLogin == 1) {
                    $user->update(['firsttimeLogin' => 0]);
                    $user['firsttimeLogin'] = 1;
                } else {
                    $user->update(['firsttimeLogin' => 0]);
                }
            }
        }
        $token = $user->createToken('auth_token')->plainTextToken;
       
        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function googleLogin($role = null)
    {
     
        if (!$role) {
            
            return Socialite::driver('google')
            ->stateless()
            ->redirect();
        }else{
           
            return Socialite::driver('google')
            ->stateless()
            ->with(['state' => $role])
            ->redirect();
            
        }
        
    }

    public function googleHandle(Request $request)
    {


        try {
            
            $findUser = User::where('email', $request->email)->first();
            
            if (!$findUser) {

                $createUser = new User();
                if(!empty($request->role)){
                $createUser->name = $request->name;
                $createUser->email = $request->email;
                $createUser->phone = $request->phone;
                $createUser->role = $request->role;
                $createUser->password = Hash::make('aszx1234');
                $createUser->terms = 1;
                $createUser->save();
                Auth::login($createUser);
                $token = $createUser->createToken('auth_token')->plainTextToken;
                return response()->json([
                    'message' => 'User registered successfully',
                    'user' => $createUser,
                    'token' => $token,
                ]);
            }else{

                return response()->json([
                    'message' => 'User Role Not Found Please Sing Up First.',
                ]);
                
            }
            }
        } catch (Exception $e) {

            dd($e->getMessage());
        }

        $getuser=User::where('email','=',$request->email)->first();
      
        if($getuser){
        Auth::login($getuser);
        $token = $getuser->createToken('auth_token')->plainTextToken;
        return response()->json([
            'message' => 'User logged in successfully',
            'token' => $token,
            'user' => $getuser,
        ]);  
    }else{

        return [
            'message' => 'Credentials Not Found',
        ];
        
    }
    
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
        User::where('email','=',$request->email)->update(['token' => $token]);

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
            'token' => ['required', 'string'],
        ], [
            'new_password.confirmed' => 'The new password and confirm password do not match.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
    

    
        $GetUserDetails = User::where('email', '=', $request->email)->first();
        if ($GetUserDetails->token != $request->token) {
            return response()->json(['error' => 'Invalid token.'], 400);
        }
        $GetUserDetails->update(['password' => Hash::make($request->new_password),
        'token' => null,
    ]);
        
        return response()->json(['message' => 'Your password has been reset successfully.'], 200);
    }
}
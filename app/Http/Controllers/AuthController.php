<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

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
        if($user->status == 1){
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

    public function googleLogin()
    {

        return Socialite::driver('google')->stateless()->redirect();
    }

    public function googleHandle()
    {

        try {
            $user = Socialite::driver('google')->stateless()->user();
            dd($user);
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

    public function facebookLogin()
    {

        return Socialite::driver('facebook')->stateless()->redirect();
    }

    public function facebookHandle()
    {

        try {
            $user = Socialite::driver('facebook')->stateless()->user();
            dd($user);
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
}
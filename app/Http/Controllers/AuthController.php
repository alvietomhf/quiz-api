<?php

namespace App\Http\Controllers;

use App\Models\User;
use Facade\FlareClient\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function studentRegister(Request $request) {
        $input = $request->all();
        $validator = Validator::make($input, [
            'name' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string|confirmed',
            'avatar' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return $this->responseFailed('Validasi error', $validator->errors(), 400);
        }

        if ($request->hasFile('avatar')) {
            $input['avatar'] = time().'.'.request()->avatar->getClientOriginalExtension();
            
            request()->avatar->move(public_path('assets/images/avatar'), $input['avatar']);
        }
        
        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => bcrypt($input['password']),
            'role' => 'siswa',
            'avatar' => $input['avatar']
        ]);

        $token = $user->createToken('quizapptoken')->plainTextToken;

        $data = [
            'user' => $user,
            'token' => $token
        ];

        return $this->responseSuccess('Registrasi berhasil', $data, 201);
    }

    public function teacherRegister(Request $request) {
        $input = $request->all();
        $validator = Validator::make($input, [
            'name' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string|confirmed',
            'avatar' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return $this->responseFailed('Validasi error', $validator->errors(), 400);
        }

        if ($request->hasFile('avatar')) {
            $input['avatar'] = time().'.'.request()->avatar->getClientOriginalExtension();
            
            request()->avatar->move(public_path('assets/images/avatar'), $input['avatar']);
        }
        
        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => bcrypt($input['password']),
            'role' => 'guru',
            'avatar' => $input['avatar']
        ]);

        $token = $user->createToken('quizapptoken')->plainTextToken;

        $data = [
            'user' => $user,
            'token' => $token
        ];

        return $this->responseSuccess('Registrasi berhasil', $data, 201);
    }

    public function login(Request $request) {
        $input = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);

        if (!Auth::attempt($input)) {
            return $this->responseFailed('Email atau password anda salah', '', 401);
        }
        
        $user = User::where('email', $input['email'])->first();
        $token = $user->createToken('quizapptoken')->plainTextToken;

        $data = [
            'user' => $user,
            'token' => $token
        ];

        return $this->responseSuccess('Login berhasil', $data, 200);
    }

    public function logout(Request $request) {
        auth()->user()->tokens()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Logout berhasil'
        ]);
    }
}

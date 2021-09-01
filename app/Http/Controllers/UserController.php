<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function studentIndex()
    {
        $data = User::select('id', 'name', 'email', 'avatar')->where('role', 'siswa')->get();

        return $this->responseSuccess('Data list siswa', $data, 200);
    }

    public function teacherIndex()
    {
        $data = User::select('id', 'name', 'email', 'avatar')->where('role', 'guru')->get();

        return $this->responseSuccess('Data list guru', $data, 200);
    }

    public function store(Request $request)
    {
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
            $input['avatar'] = rand() . '.' . request()->avatar->getClientOriginalExtension();

            request()->avatar->move(public_path('assets/images/avatar/'), $input['avatar']);
        }

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => bcrypt($input['password']),
            'role' => 'guru',
            'avatar' => $input['avatar']
        ]);

        $data = [
            'user' => $user
        ];

        return $this->responseSuccess('Berhasil membuat data', $data, 201);
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}

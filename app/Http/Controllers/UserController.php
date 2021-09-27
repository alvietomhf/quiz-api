<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function studentIndex()
    {
        $data = User::select('id', 'name', 'email', 'avatar', 'number')->where('role', 'siswa')->orderBy('name', 'ASC')->get();

        return $this->responseSuccess('Data list siswa', $data, 200);
    }

    public function index()
    {
        $data = User::where('role', '!=', 'admin')->orderBy('role', 'DESC')->get();
        
        return $this->responseSuccess('Seluruh user', $data, 200);
    }

    public function teacherIndex()
    {
        $data = User::select('id', 'name', 'email', 'avatar')->where('role', 'guru')->orderBy('name', 'ASC')->get();

        return $this->responseSuccess('Data list guru', $data, 200);
    }

    public function show($id)
    {
        $data = User::find($id);
        if (!$data) return $this->responseFailed('Data tidak ditemukan', '', 404);

        return $this->responseSuccess('Data user', $data);
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }

    public function status()
    {
        $rawUsers = User::select('id', 'name', 'email', 'role', 'avatar', 'number', 'last_seen')
                        ->whereNotNull('last_seen')
                        ->orderBy('last_seen', 'DESC')
                        ->take(10)
                        ->get();

        $data = $rawUsers->map(function($user) {
            $user['online'] = false;
            if ($user->last_seen > Carbon::now()->subMinutes(2)) $user['online'] = true;

            return $user;
        });

        return $this->responseSuccess('Berhasil mendapatkan data', $data, 200);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Facade\FlareClient\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *      path="/api/register",
     *      operationId="authRegister",
     *      tags={"Auth"},
     *      summary="User Register",
     *      description="Register User Here",
     *      @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  type="object",
     *                  required={"name", "email", "password", "password_confirmation", "role"},
     *                  nullable={"avatar", "number"},
     *                  @OA\Property(property="name", type="string", example="John Doe"),
     *                  @OA\Property(property="email", type="email", example="johndoe@gmail.com"),
     *                  @OA\Property(property="password", type="password", example="Password`"),
     *                  @OA\Property(property="password_confirmation", type="password", example="Password`"),
     *                  @OA\Property(property="role", type="string", enum={"siswa", "guru"}, example="siswa"),
     *                  @OA\Property(property="avatar", type="file"),
     *                  @OA\Property(property="number", type="integer"),
     *              ),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Register success",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Registrasi berhasil"),
     *              @OA\Property(
     *                  property="data",
     *                  type="object",
     *                  example={
     *                      "user": {
     *                          "name": "John Doe",
     *                          "email": "johndoe@gmail.com",
     *                          "email_verified_at": "2022-08-29T06:19:06.000000Z",
     *                          "role": "siswa",
     *                          "avatar": "1255762734.jpg",
     *                          "number": 2,
     *                          "created_at": "2022-08-29T06:19:06.000000Z",
     *                          "updated_at": "2022-08-29T08:34:52.000000Z",
     *                          "id": 8,
     *                      }
     *                  }
     *              ),
     *              @OA\Property(property="token", type="string", example="2|pqLrmURrF0SmemD1TROWlWB1VIaUg1PE9r0uSf69"),
     *          )
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad request",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Validasi error"),
     *          )
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Internal server error",
     *      ),
     *  )
     */
    public function register(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'name' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string|confirmed',
            'role' => Rule::in(['siswa', 'guru']),
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'number' => 'sometimes|nullable|integer|unique:users,number',
        ]);

        if ($validator->fails()) {
            return $this->responseFailed('Validasi error', $validator->errors(), 400);
        }

        if ($request->hasFile('avatar')) {
            $input['avatar'] = rand() . '.' . request()->avatar->getClientOriginalExtension();

            request()->avatar->move(public_path('storage/images/avatar/'), $input['avatar']);
        }

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => bcrypt($input['password']),
            'role' => $input['role'],
            'avatar' => $input['avatar'],
            'number' => isset($input['number']) ? +$input['number'] : null,
        ]);

        $token = $user->createToken('quizapptoken')->plainTextToken;

        $data = [
            'user' => $user,
            'token' => $token
        ];

        return $this->responseSuccess('Registrasi berhasil', $data, 201);
    }

    /**
     * @OA\Post(
     *      path="/api/login",
     *      operationId="authLogin",
     *      tags={"Auth"},
     *      summary="User Login",
     *      description="Login User Here",
     *      @OA\RequestBody(
     *          @OA\JsonContent(
     *              type="object",
     *              required={"email", "password"},
     *              @OA\Property(property="email", type="email", example="siswa@gmail.com"),
     *              @OA\Property(property="password", type="password", example="Password`")
     *          ),
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  type="object",
     *                  required={"email", "password"},
     *                  @OA\Property(property="email", type="email", example="siswa@gmail.com"),
     *                  @OA\Property(property="password", type="password", example="Password`")
     *              ),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Login success",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Login berhasil"),
     *              @OA\Property(
     *                  property="data",
     *                  type="object",
     *                  example={
     *                      "user": {
     *                          "id": 1,
     *                          "name": "siswa",
     *                          "email": "siswa@gmail.com",
     *                          "email_verified_at": "2022-08-29T06:19:06.000000Z",
     *                          "role": "siswa",
     *                          "avatar": null,
     *                          "number": null,
     *                          "last_seen": "2022-08-29T08:34:52.370167Z",
     *                          "created_at": "2022-08-29T06:19:06.000000Z",
     *                          "updated_at": "2022-08-29T08:34:52.000000Z"
     *                      }
     *                  }
     *              ),
     *              @OA\Property(property="token", type="string", example="2|pqLrmURrF0SmemD1TROWlWB1VIaUg1PE9r0uSf69"),
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Incorrect email or password",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Email atau password anda salah"),
     *          )
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Internal server error",
     *      ),
     *   )
     */
    public function login(Request $request)
    {
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

        $user->update(['last_seen' => Carbon::now()]);

        return $this->responseSuccess('Login berhasil', $data, 200);
    }

    /**
     * @OA\Post(
     *      path="/api/logout",
     *      operationId="authLogout",
     *      tags={"Auth"},
     *      summary="User Logout",
     *      description="Logout User Here",
     *      security={{"sanctum":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Logout success",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Logout berhasil"),
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Internal server error",
     *      ),
     *  )
     */
    public function logout(Request $request)
    {
        auth()->user()->tokens()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Logout berhasil'
        ]);
    }
}

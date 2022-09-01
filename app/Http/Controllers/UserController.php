<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/students",
     *      operationId="getStudentsList",
     *      tags={"Users"},
     *      summary="Get List of Students",
     *      description="Get List of Students",
     *      security={{"sanctum":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Data list siswa"),
     *              @OA\Property(
     *                  property="data",
     *                  type="array",
     *                  example={{
     *                      "id": 2,
	 *		                "name": "Alex",
	 *	                    "email": "alex@gmail.com",
	 *		                "avatar": null,
	 *		                "number": null,
     *                  },{
     *                      "id": 3,
	 *		                "name": "Bill",
	 *	                    "email": "bill@gmail.com",
	 *		                "avatar": "1850228838.jpg",
	 *		                "number": 1,
     *                  }},
     *                  @OA\Items(
     *                      required={"id", "name", "email"},
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="name", type="string"),
     *                      @OA\Property(property="email", type="string", format="email"),
     *                      @OA\Property(property="avatar", type="string"),
     *                      @OA\Property(property="number", type="integer"),
     *                  ),
     *              ),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Internal server error",
     *      ),
     * )
     */
    public function studentIndex()
    {
        $data = User::select('id', 'name', 'email', 'avatar', 'number')->where('role', 'siswa')->orderBy('name', 'ASC')->get();

        return $this->responseSuccess('Data list siswa', $data, 200);
    }

    /**
     * @OA\Get(
     *      path="/api/users",
     *      operationId="getUsersList",
     *      tags={"Users"},
     *      summary="Get List of All Users",
     *      description="Get List of All Users",
     *      security={{"sanctum":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Seluruh user"),
     *              @OA\Property(
     *                  property="data",
     *                  type="array",
     *                  example={{
     *                      "id": 2,
	 *		                "name": "Kira",
	 *	                    "email": "kira@gmail.com",
	 *		                "email_verified_at": "2021-09-15T08:14:28.000000Z",
	 *		                "role": "guru",
	 *		                "avatar": null,
	 *		                "number": null,
	 *		                "last_seen": "2021-09-20 02:56:55",
	 *		                "created_at": "2021-09-15T08:14:28.000000Z",
	 *		                "updated_at": "2021-09-20T02:56:55.000000Z"
     *                  },{
     *                      "id": 3,
	 *		                "name": "John Doe",
	 *	                    "email": "johndoe@gmail.com",
	 *		                "email_verified_at": "2021-09-15T08:14:28.000000Z",
	 *		                "role": "siswa",
	 *		                "avatar": "1850228838.jpg",
	 *		                "number": 1,
	 *		                "last_seen": "2021-09-20 02:56:55",
	 *		                "created_at": "2021-09-15T08:14:28.000000Z",
	 *		                "updated_at": "2021-09-20T02:56:55.000000Z"
     *                  }},
     *                  @OA\Items(
     *                      required={"id", "name", "email", "role", "created_at", "updated_at"},
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="name", type="string"),
     *                      @OA\Property(property="email", type="string", format="email"),
     *                      @OA\Property(property="email_verified_at", type="string", format="date-time"),
     *                      @OA\Property(property="role", type="string"),
     *                      @OA\Property(property="avatar", type="string"),
     *                      @OA\Property(property="number", type="integer"),
     *                      @OA\Property(property="last_seen", type="string", format="date-time"),
     *                      @OA\Property(property="created_at", type="string", format="date-time"),
     *                      @OA\Property(property="updated_at", type="string", format="date-time"),
     *                  ),
     *              ),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Internal server error",
     *      ),
     * )
     */
    public function index()
    {
        $data = User::where('role', '!=', 'admin')->orderBy('role', 'DESC')->get();
        
        return $this->responseSuccess('Seluruh user', $data, 200);
    }

    /**
     * @OA\Get(
     *      path="/api/teachers",
     *      operationId="getTeachersList",
     *      tags={"Users"},
     *      summary="Get List of Teachers",
     *      description="Get List of Teachers",
     *      security={{"sanctum":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Data list guru"),
     *              @OA\Property(
     *                  property="data",
     *                  type="array",
     *                  example={{
     *                      "id": 2,
	 *		                "name": "Kira",
	 *	                    "email": "kira@gmail.com",
	 *		                "avatar": null,
     *                  },{
     *                      "id": 3,
	 *		                "name": "Mita",
	 *	                    "email": "mita@gmail.com",
	 *		                "avatar": "1850228838.jpg",
     *                  }},
     *                  @OA\Items(
     *                      required={"id", "name", "email"},
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="name", type="string"),
     *                      @OA\Property(property="email", type="string", format="email"),
     *                      @OA\Property(property="avatar", type="string"),
     *                  ),
     *              ),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Internal server error",
     *      ),
     * )
     */
    public function teacherIndex()
    {
        $data = User::select('id', 'name', 'email', 'avatar')->where('role', 'guru')->orderBy('name', 'ASC')->get();

        return $this->responseSuccess('Data list guru', $data, 200);
    }

    /**
     * @OA\Get(
     *      path="/api/users/{id}",
     *      operationId="showUser",
     *      tags={"Users"},
     *      summary="Get User Detail",
     *      description="Get User Detail",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="Id of User",
     *          required=true,
     *          example=1,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Data user"),
     *              @OA\Property(
     *                  property="data",
     *                  type="object",
     *                  example={
     *                      "id": 3,
	 *		                "name": "John Doe",
	 *	                    "email": "johndoe@gmail.com",
	 *		                "email_verified_at": "2021-09-15T08:14:28.000000Z",
	 *		                "role": "siswa",
	 *		                "avatar": "1850228838.jpg",
	 *		                "number": 1,
	 *		                "last_seen": "2021-09-20 02:56:55",
	 *		                "created_at": "2021-09-15T08:14:28.000000Z",
	 *		                "updated_at": "2021-09-20T02:56:55.000000Z"
     *                  }
     *              ),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Data not found",
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Internal server error",
     *      ),
     * )
     */
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

    /**
     * @OA\Get(
     *      path="/api/users/status",
     *      operationId="showUserStatus",
     *      tags={"Users"},
     *      summary="Get Users Status",
     *      description="Get Users Status",
     *      security={{"sanctum":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Berhasil mendapatkan data"),
     *              @OA\Property(
     *                  property="data",
     *                  type="array",
     *                  example={{
     *                      "id": 2,
	 *		                "name": "Kira",
	 *	                    "email": "kira@gmail.com",
	 *	                    "role": "siswa",
	 *		                "avatar": null,
	 *		                "number": 1,
	 *		                "last_seen": "2021-09-20 02:56:55",
     *                      "online": false
     *                  },{
     *                      "id": 3,
	 *		                "name": "Mita",
	 *	                    "email": "mita@gmail.com",
	 *	                    "role": "guru",
	 *		                "avatar": "1850228838.jpg",
	 *		                "number": 1,
	 *		                "last_seen": "2021-09-20 02:56:55",
     *                      "online": true
     *                  }},
     *                  @OA\Items(
     *                      required={"id", "name", "email", "role", "online"},
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="name", type="string"),
     *                      @OA\Property(property="email", type="string", format="email"),
     *                      @OA\Property(property="role", type="string"),
     *                      @OA\Property(property="avatar", type="string"),
     *                      @OA\Property(property="number", type="integer"),
     *                      @OA\Property(property="last_seen", type="string", format="date-time"),
     *                      @OA\Property(property="online", type="boolean"),
     *                  ),
     *              ),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Internal server error",
     *      ),
     * )
     */
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

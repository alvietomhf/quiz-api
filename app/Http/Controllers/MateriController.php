<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\Materi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class MateriController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/materi",
     *      operationId="getMateriList",
     *      tags={"Materi"},
     *      summary="Get List of Materi",
     *      description="Get List of Materi",
     *      security={{"sanctum":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Data"),
     *              @OA\Property(
     *                  property="data",
     *                  type="array",
     *                  example={{
     *                      "id": 1,
     *                      "user_id": 2,
     *                      "subject": "IPA",
     *                      "competence": "Mendeskripsikan tata surya",
     *                      "class": "VI",
     *                      "semester": "2",
     *                      "meet": "1",
     *                      "description": "Penjelasan materi tata surya",
     *                      "image_banner": "1830621834.jpg",
     *                      "created_at": "2021-09-26T12:56:48.000000Z",
     *                      "updated_at": "2021-09-26T13:00:38.000000Z",
     *                      "user": {
     *                          "id": 2,
     *                          "name": "guru",
     *                          "email": "guru@gmail.com",
     *                          "avatar": null
     *                      }
     *                  }},
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="user_id", type="integer"),
     *                      @OA\Property(property="subject", type="string"),
     *                      @OA\Property(property="competency", type="string"),
     *                      @OA\Property(property="class", type="string"),
     *                      @OA\Property(property="semester", type="string"),
     *                      @OA\Property(property="meet", type="string"),
     *                      @OA\Property(property="description", type="string"),
     *                      @OA\Property(property="created_at", type="string", format="date-time"),
     *                      @OA\Property(property="updated_at", type="string", format="date-time"),
     *                      @OA\Property(property="user", type="object"),
     *                ),
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
        $data = Materi::with(['user:id,name,email,avatar'])->get();

        return $this->responseSuccess('Data', $data);
    }

    /**
     * @OA\Get(
     *      path="/api/materi/{id}",
     *      operationId="showMateri",
     *      tags={"Materi"},
     *      summary="Get Materi Detail",
     *      description="Get Materi Detail",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="Id of Materi",
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
     *              @OA\Property(property="message", type="string", example="Detail data"),
     *              @OA\Property(
     *                  property="data",
     *                  type="object",
     *                  example={
     *                      "id": 1,
     *                      "user_id": 2,
     *                      "subject": "IPA",
     *                      "competence": "Mendeskripsikan tata surya",
     *                      "class": "VI",
     *                      "semester": "2",
     *                      "meet": "1",
     *                      "description": "Penjelasan materi tata surya",
     *                      "image_banner": "1830621834.jpg",
     *                      "created_at": "2021-09-26T12:56:48.000000Z",
     *                      "updated_at": "2021-09-26T13:00:38.000000Z",
     *                      "user": {
     *                          "id": 2,
     *                          "name": "guru",
     *                          "email": "guru@gmail.com",
     *                          "avatar": null
     *                      }
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
        $data = Materi::where('id', $id)->with(['user:id,name,email,avatar'])->first();
        if (!$data) return $this->responseFailed('Data tidak ditemukan', '', 404);

        return $this->responseSuccess('Detail data', $data, 200);
    }

    /**
     * @OA\Post(
     *      path="/api/guru/materi",
     *      operationId="storeMateri",
     *      tags={"Materi"},
     *      summary="Store Materi in DB",
     *      description="Store Materi in DB",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  type="object",
     *                  required={"subject", "competence", "class", "semester", "meet", "description"},
     *                  nullable={"image_banner"},
     *                  @OA\Property(property="subject", type="string", example="IPA"),
     *                  @OA\Property(property="competence", type="string", example="Mendeskripsikan tata surya"),
     *                  @OA\Property(property="class", type="string", example="VI"),
     *                  @OA\Property(property="semester", type="string", example="2"),
     *                  @OA\Property(property="meet", type="string", example="1"),
     *                  @OA\Property(property="description", type="string", example="Penjelasan materi tata surya"),
     *                  @OA\Property(property="image_banner", type="file"),
     *              ),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Success",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Data berhasil dibuat"),
     *              @OA\Property(
     *                  property="data",
     *                  type="object",
     *                  example={
     *                      "user_id": 2,
     *                      "subject": "IPA",
     *                      "competence": "Mendeskripsikan tata suryaa",
     *                      "class": "VI",
     *                      "semester": "2",
     *                      "meet": "1",
     *                      "description": "Penjelasan materi tata surya",
     *                      "image_banner": "1172193584.jpg",
     *                      "updated_at": "2021-09-26T13:00:59.000000Z",
     *                      "created_at": "2021-09-26T13:00:59.000000Z",
     *                      "id": 2
     *                  }
     *              ),
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
     *          response=401,
     *          description="Unauthorized",
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Internal server error",
     *      ),
     *  )
     */
    public function store(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'subject' => 'required|string',
            'competence' => 'required|string',
            'class' => 'required|string',
            'semester' => 'required|string',
            'meet' => 'required|string',
            'description' => 'required|string',
            'image_banner' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return $this->responseFailed('Validasi error', $validator->errors(), 400);
        }

        if ($request->hasFile('image_banner')) {
            $input['image_banner'] = rand().'.'.request()->image_banner->getClientOriginalExtension();
            
            request()->image_banner->move(public_path('storage/images/materi/'), $input['image_banner']);
        }

        $data = Materi::create([
            'user_id' => auth()->user()->id,
            'subject' => $input['subject'],
            'competence' => $input['competence'],
            'class' => $input['class'],
            'semester' => $input['semester'],
            'meet' => $input['meet'],
            'description' => $input['description'],
            'image_banner' => $input['image_banner'] ?? null
        ]);

        return $this->responseSuccess('Data berhasil dibuat', $data, 201);
    }

    /**
     * @OA\Post(
     *      path="/api/guru/materi/{id}",
     *      operationId="updateMateri",
     *      tags={"Materi"},
     *      summary="Update Materi in DB",
     *      description="Update Materi in DB",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="Id of Materi",
     *          required=true,
     *          example=1,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Parameter(
     *          name="_method",
     *          description="Spoofing put method",
     *          in="query",
     *          @OA\Schema(
     *              type="string", default="PUT"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  type="object",
     *                  required={"subject", "competence", "class", "semester", "meet", "description"},
     *                  nullable={"image_banner"},
     *                  @OA\Property(property="subject", type="string", example="IPA"),
     *                  @OA\Property(property="competence", type="string", example="Mendeskripsikan tata surya"),
     *                  @OA\Property(property="class", type="string", example="VI"),
     *                  @OA\Property(property="semester", type="string", example="2"),
     *                  @OA\Property(property="meet", type="string", example="1"),
     *                  @OA\Property(property="description", type="string", example="Penjelasan materi tata surya"),
     *                  @OA\Property(property="image_banner", type="file"),
     *              ),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Data berhasil diubah"),
     *              @OA\Property(
     *                  property="data",
     *                  type="object",
     *                  example={
     *                      "id": 2,
     *                      "user_id": 2,
     *                      "subject": "IPA",
     *                      "competence": "Mendeskripsikan tata suryaa",
     *                      "class": "VI",
     *                      "semester": "2",
     *                      "meet": "1",
     *                      "description": "Penjelasan materi tata surya",
     *                      "image_banner": "1172193584.jpg",
     *                      "updated_at": "2021-09-26T13:00:59.000000Z",
     *                      "created_at": "2021-09-26T13:00:59.000000Z",
     *                  }
     *              ),
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
     *  )
     */
    public function update($id, Request $request)
    {
        $materi = Materi::where('id', $id)->first();
        if (!$materi) return $this->responseFailed('Data tidak ditemukan', '', 404);

        $input = $request->all();
        $validator = Validator::make($input, [
            'subject' => 'required|string',
            'competence' => 'required|string',
            'class' => 'required|string',
            'semester' => 'required|string',
            'meet' => 'required|string',
            'description' => 'required|string',
            'image_banner' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return $this->responseFailed('Validasi error', $validator->errors(), 400);
        }

        $oldImage = $materi->image_banner;
        if ($request->hasFile('image_banner')) {
            File::delete('storage/images/materi/' . $oldImage);
            $input['image_banner'] = rand().'.'.request()->image_banner->getClientOriginalExtension();

            request()->image_banner->move(public_path('storage/images/materi/'), $input['image_banner']);
        } else {
            $input['image_banner'] = $oldImage;
        }

        $materi->update($input);

        $data = Materi::find($id);

        return $this->responseSuccess('Data berhasil diubah', $data, 200);
    }

    /**
     * @OA\Delete(
     *      path="/api/guru/materi/{id}",
     *      operationId="destroyMateri",
     *      tags={"Materi"},
     *      summary="Delete Materi",
     *      description="Delete Materi",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="Id of Materi",
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
     *              @OA\Property(property="message", type="string", example="Data berhasil dihapus"),
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
     *  )
     */
    public function destroy($id)
    {
        $materi = Materi::where('id', $id)->first();
        if (!$materi) return $this->responseFailed('Data tidak ditemukan', '', 404);

        if ($materi->image_banner) {
            File::delete('storage/images/materi/' . $materi->image_banner);
        }

        $materi->delete();

        return $this->responseSuccess('Data berhasil dihapus');
    }

    /**
     * @OA\Delete(
     *      path="/api/guru/materi/{id}/image",
     *      operationId="deleteImageMateri",
     *      tags={"Materi"},
     *      summary="Delete Materi Image",
     *      description="Delete Materi Image",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="Id of Materi",
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
     *              @OA\Property(property="message", type="string", example="Gambar berhasil dihapus"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Image not found",
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
     *  )
     */
    public function deleteMateriImage($id)
    {
        $materi = Materi::find($id);
        if (!$materi) return $this->responseFailed('Data tidak ditemukan', '', 404);
        if (!$materi->image_banner) return $this->responseFailed('Gambar tidak ada', '', 400);

        File::delete('storage/images/materi/' . $materi->image_banner);
        $materi->update(['image_banner' => null]);

        return $this->responseSuccess('Gambar berhasil dihapus');
    }

    public function uploadImage(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'files' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return $this->responseFailed('Validasi error', $validator->errors(), 400);
        }

        if ($request->hasFile('file')) {
            $input['file'] = rand().'.'.request()->file->getClientOriginalExtension();
            
            request()->file->move(public_path('storage/images/ck/'), $input['file']);
        }

        $data = Image::create(['file' => $input['file'] ?? null]);

        return $this->responseSuccess('Data berhasil dibuat', $data, 201);
    }
}

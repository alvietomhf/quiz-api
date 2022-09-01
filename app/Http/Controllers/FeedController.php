<?php

namespace App\Http\Controllers;

use App\Models\Feed;
use App\Models\FeedReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FeedController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/feeds",
     *      operationId="getFeedsList",
     *      tags={"Feeds"},
     *      summary="Get List of Feeds",
     *      description="Get List of Feeds",
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
     *                      "user_id": 1,
     *                      "message": "Hi there!",
     *                      "image": "2079572408.jpg",
     *                      "created_at": "2021-10-10T12:05:19.000000Z",
     *                      "updated_at": "2021-10-10T12:05:19.000000Z",
     *                      "user": {
     *                          "id": 1,
     *                          "name": "John Doe",
     *                          "email": "johndoe@gmail.com"
     *                      },
     *                      "replies": {{
     *                          "id": 1,
     *                          "feed_id": 1,
     *                          "user_id": 1,
     *                          "message": "Hallo there!",
     *                          "image": "2093156899.jpg",
     *                          "created_at": "2021-10-10T12:07:08.000000Z",
     *                          "updated_at": "2021-10-10T12:07:08.000000Z",
     *                          "user": {
     *                              "id": 1,
     *                              "name": "Mike",
     *                              "email": "mike@gmail.com"
     *                          }
     *                      }},
     *                  }},
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="user_id", type="integer"),
     *                      @OA\Property(property="message", type="string"),
     *                      @OA\Property(property="image", type="string"),
     *                      @OA\Property(property="created_at", type="string", format="date-time"),
     *                      @OA\Property(property="updated_at", type="string", format="date-time"),
     *                      @OA\Property(property="user", type="object"),
     *                      @OA\Property(
     *                          property="replies",
     *                          type="array",
     *                          @OA\Items(
     *                              @OA\Property(property="id", type="integer"),
     *                              @OA\Property(property="feed_id", type="integer"),
     *                              @OA\Property(property="user_id", type="integer"),
     *                              @OA\Property(property="message", type="string"),
     *                              @OA\Property(property="image", type="string"),
     *                              @OA\Property(property="created_at", type="string", format="date-time"),
     *                              @OA\Property(property="updated_at", type="string", format="date-time"),
     *                              @OA\Property(property="user", type="object"),
     *                          )
     *                      ),
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
    public function feedIndex()
    {
        $data = Feed::with(['user:id,name,email', 'replies.user' => function($q) {
            $q->select('id', 'name', 'email');
        }])->orderBy('created_at', 'DESC')->get();

        return $this->responseSuccess('Data', ($data ?? null));
    }

    /**
     * @OA\Post(
     *      path="/api/feeds",
     *      operationId="storeFeed",
     *      tags={"Feeds"},
     *      summary="Store Feed in DB",
     *      description="Store Feed in DB",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  type="object",
     *                  required={"message"},
     *                  nullable={"image"},
     *                  @OA\Property(property="message", type="string", example="Hi there!"),
     *                  @OA\Property(property="image", type="file"),
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
     *                      "message": "Hi there!",
     *                      "image": "1172193584.jpg",
     *                      "updated_at": "2021-09-26T13:00:59.000000Z",
     *                      "created_at": "2021-09-26T13:00:59.000000Z",
     *                      "id": 3
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
    public function feedStore(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'message' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return $this->responseFailed('Validasi error', $validator->errors(), 400);
        }

        if ($request->hasFile('image')) {
            $input['image'] = rand().'.'.request()->image->getClientOriginalExtension();
            
            request()->image->move(public_path('storage/images/feed/'), $input['image']);
        }
        
        $data = Feed::create([
            'user_id' => auth()->user()->id,
            'message' => $input['message'],
            'image' => $input['image'] ?? null
        ]);

        return $this->responseSuccess('Data berhasil dibuat', $data, 201);
    }

    /**
     * @OA\Post(
     *      path="/api/feeds/{feedId}/reply",
     *      operationId="storeReply",
     *      tags={"Feeds"},
     *      summary="Store Reply in DB",
     *      description="Store Reply in DB",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="feedId",
     *          in="path",
     *          description="Id of Feed",
     *          required=true,
     *          example=1,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  type="object",
     *                  required={"message"},
     *                  nullable={"image"},
     *                  @OA\Property(property="message", type="string", example="Hallo there!"),
     *                  @OA\Property(property="image", type="file"),
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
     *                      "feed_id": 1,
     *                      "message": "Hallo there!",
     *                      "image": "1172193584.jpg",
     *                      "updated_at": "2021-09-26T13:00:59.000000Z",
     *                      "created_at": "2021-09-26T13:00:59.000000Z",
     *                      "id": 4
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
    public function replyStore(Request $request, $feedId)
    {
        $feed = Feed::find($feedId);
        if(!$feed) return $this->responseFailed('Data feed tidak ditemukan', '', 404);

        $input = $request->all();
        $validator = Validator::make($input, [
            'message' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return $this->responseFailed('Validasi error', $validator->errors(), 400);
        }

        if ($request->hasFile('image')) {
            $input['image'] = rand().'.'.request()->image->getClientOriginalExtension();

            request()->image->move(public_path('storage/images/feed/'), $input['image']);
        }
        
        $data = FeedReply::create([
            'user_id' => auth()->user()->id,
            'feed_id' => +$feedId,
            'message' => $input['message'],
            'image' => $input['image'] ?? null
        ]);

        return $this->responseSuccess('Data berhasil dibuat', $data, 201);
    }
}

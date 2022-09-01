<?php

namespace App\Http\Controllers;

use App\Models\Option;
use App\Models\Quiz;
use App\Models\Result;
use App\Models\ResultEssay;
use App\Models\ResultQuiz;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ResultController extends Controller
{
    /**
     * @OA\Post(
     *      path="/api/siswa/result/{slug}/quiz",
     *      operationId="storeQuizAnswer",
     *      tags={"Results"},
     *      summary="Store Quiz Answer in DB",
     *      description="Store Quiz Answer in DB",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="Slug of Quiz",
     *          required=true,
     *          example="first-quiz-630ee5b27b98e",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  type="object",
     *                  required={"data"},
     *                  @OA\Property(
     *                      property="data",
     *                      type="array",
     *                      required={"id", "quiz_id", "options"},
     *                      example={{
     *                          "id": 1,
     *                          "quiz_id": 1,
     *                          "options": {
     *                              {
     *                                  "id": 1,
     *                                  "question_id": 1,
     *                                  "title": "Option A",
     *                                  "selected": 1,
     *                              },
     *                              {
     *                                  "id": 2,
     *                                  "question_id": 1,
     *                                  "title": "Option B",
     *                                  "selected": 0,
     *                              },
     *                              {
     *                                  "id": 3,
     *                                  "question_id": 1,
     *                                  "title": "Option C",
     *                                  "selected": 0,
     *                              },
     *                          }
     *                      }},
     *                      @OA\Items(
     *                          @OA\Property(property="id", type="integer", example=1),
     *                          @OA\Property(property="quiz_id", type="integer", example=1),
     *                          @OA\Property(
     *                              property="options",
     *                              type="array",
     *                              required={"id", "question_id", "title", "selected"},
     *                              @OA\Items(
     *                                  type="object",
     *                                  @OA\Property(property="id", type="integer", example=1),
     *                                  @OA\Property(property="question_id", type="integer", example=1),
     *                                  @OA\Property(property="title", type="string", example="Option A"),
     *                                  @OA\Property(property="selected", type="integer", enum={1,0}, example=1),
     *                              ),
     *                          ),
     *                      )
     *                  )
     *              ),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Jawaban berhasil disimpan"),
     *          )
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad request",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example={ "Validasi error", "User sudah mengerjakan quiz ini", "Waktu pengerjaan telah lewat" }),
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
    public function quizStore($slug, Request $request)
    {
        $quiz = Quiz::where('slug', $slug)->first();
        if (!$quiz) return $this->responseFailed('Data tidak ditemukan', '', 404);
        
        $isResult = Result::where([
                        'quiz_id' => $quiz->id,
                        'user_id' => auth()->user()->id
                    ])->first();
        if(isset($isResult)) {
            return $this->responseFailed('Gagal submit', 'User sudah mengerjakan quiz ini', 400);
        }

        $isAvailable = Carbon::parse($quiz->deadline)->toDateTimeString() > Carbon::now()->toDateTimeString() ? true : false;
        if(!$isAvailable) {
            return $this->responseFailed('Gagal submit', 'Waktu pengerjaan telah lewat', 400);
        }

        $inputRaw = $request->only('data');
        $validator = Validator::make($inputRaw, [
            'data' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->responseFailed('Validasi error', $validator->errors(), 400);
        }

        try {
            DB::beginTransaction();
            
            $inputRawData = $inputRaw['data'];
            if (gettype($inputRawData) == 'array') {
                $inputRawData = json_encode($inputRawData);
            }

            $input = json_decode($inputRawData);

            $data = [
                'user_id' => auth()->user()->id,
                'quiz_id' => $input[0]->quiz_id
            ];
            $result = Result::create($data);
            $score = 0;

            foreach ($input as $item) {
                foreach ($item->options as $option) {
                    $optionData = Option::find($option->id);
                    if (!property_exists($option, 'selected')) {
                        $optData = [
                            'result_id' => $result->id,
                            'question_id' => $option->question_id,
                            'option_id' => null,
                            'correct' => false,
                        ];
                        ResultQuiz::create($optData);
                        break;
                    }
                    if (isset($option->selected) && $option->selected == 1) {
                        $optData = [
                            'result_id' => $result->id,
                            'question_id' => $option->question_id,
                            'option_id' => $option->id,
                            'correct' => $optionData->correct == $option->selected ? true : false,
                        ];
                        $res = ResultQuiz::create($optData);
                        if ($res->correct) $score += 10;
                        break;
                    }
                }
            }
            $result->update(['score' => $score]);

            DB::commit();

            return $this->responseSuccess('Jawaban berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->responseFailed('Jawaban gagal disimpan');
        }
        
    }

    /**
     * @OA\Post(
     *      path="/api/siswa/result/{slug}/essay",
     *      operationId="storeEssayAnswer",
     *      tags={"Results"},
     *      summary="Store Essay Answer in DB",
     *      description="Store Essay Answer in DB",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="Slug of Essay",
     *          required=true,
     *          example="first-essay-630ee82c7a9da",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  type="object",
     *                  required={"question_id"},
     *                  nullable={"comment", "file"},
     *                  @OA\Property(property="question_id", type="integer", example=3),
     *                  @OA\Property(property="comment", type="string", example="Has been submitted"),
     *                  @OA\Property(property="file", type="file"),
     *              ),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Jawaban berhasil disimpan"),
     *          )
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad request",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example={ "Validasi error", "Waktu pengerjaan telah lewat" }),
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
    public function essayStore($slug, Request $request)
    {
        $quiz = Quiz::where('slug', $slug)->first();
        if (!$quiz) return $this->responseFailed('Data tidak ditemukan', '', 404);

        $isAvailable = Carbon::parse($quiz->deadline)->toDateTimeString() > Carbon::now()->toDateTimeString() ? true : false;
        if(!$isAvailable) {
            return $this->responseFailed('Gagal submit', 'Waktu pengerjaan telah lewat', 400);
        }

        $input = $request->all();
        $validator = Validator::make($input, [
            'question_id' => 'required',
            'comment' => 'nullable|string',
            'file' => 'nullable|mimes:jpeg,png,jpg,doc,docx,pdf',
        ]);

        if ($validator->fails()) {
            return $this->responseFailed('Validasi error', $validator->errors(), 400);
        }

        try {
            DB::beginTransaction();

            if ($request->hasFile('file')) {
                $input['file'] = rand() . '.' . request()->file->getClientOriginalExtension();
    
                request()->file->move(public_path('storage/files/quiz/'), $input['file']);
            }
    
            $data = [
                'user_id' => auth()->user()->id,
                'quiz_id' => $quiz->id
            ];
            $result = Result::create($data);
    
            ResultEssay::create([
                'result_id' => $result->id,
                'question_id' => $input['question_id'],
                'comment' => $input['comment'],
                'file' => $input['file']
            ]);

            DB::commit();
    
            return $this->responseSuccess('Jawaban berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->responseFailed('Jawaban gagal disimpan');
        }
    }

    /**
     * @OA\Get(
     *      path="/api/guru/result/{slug}/notsubmitted",
     *      operationId="getStudentNotSubmittedResult",
     *      tags={"Results"},
     *      summary="Get List Student Not Submitted Result",
     *      description="Get List Student Not Submitted Result",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="Slug of Quizzes",
     *          required=true,
     *          example="first-quiz-630ee5b27b98e",
     *          @OA\Schema(type="string")
     *      ),
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
     *                  example={
     *                      {
     *                          "id": 1,
     *                          "name": "John",
     *                          "email": "john@gmail.com",
     *                          "avatar": "2079572408.jpg",
     *                          "number": 1
     *                      },
     *                      {
     *                          "id": 2,
     *                          "name": "Doe",
     *                          "email": "doe@gmail.com",
     *                          "avatar": null,
     *                          "number": null,
     *                      },
     *                  },
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="name", type="string"),
     *                      @OA\Property(property="email", type="email"),
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
     *          response=404,
     *          description="Data not found",
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Internal server error",
     *      ),
     * )
     */
    public function resultNotSubmitted($slug)
    {
        $quiz = Quiz::where('slug', $slug)->first();
        if (!$quiz) return $this->responseFailed('Data tidak ditemukan', '', 404);

        $data = User::select('id', 'name', 'email', 'avatar', 'number')
                        ->where('role', 'siswa')
                        ->whereDoesntHave('results', function($q) use($quiz) {
                            $q->where('quiz_id', $quiz->id);
                        })->get();
        
        return $this->responseSuccess('Data', $data);
    }

    /**
     * @OA\Get(
     *      path="/api/guru/result/{slug}/quiz",
     *      operationId="getQuizResult",
     *      tags={"Results"},
     *      summary="Get List Quiz Result",
     *      description="Get List Quiz Result",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="Slug of Quizzes",
     *          required=true,
     *          example="first-quiz-630ee5b27b98e",
     *          @OA\Schema(type="string")
     *      ),
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
     *                      "name": "siswa",
     *                      "email": "siswa@gmail.com",
     *                      "avatar": "1727520681.jpg",
     *                      "number": null,
     *                      "results": {
     *                          {
     *                              "id": 1,
     *                              "user_id": 1,
     *                              "quiz_id": 1,
     *                              "score": 10,
     *                              "created_at": "2022-08-31T09:14:18.000000Z",
     *                              "result_quizzes": {
     *                                  {
     *                                      "id": 1,
     *                                      "result_id": 1,
     *                                      "question_id": 1,
     *                                      "option_id": 1,
     *                                      "correct": 1,
     *                                      "question": {
     *                                          "id": 1,
     *                                          "question": "Question One Update"
     *                                      },
     *                                      "option": {
     *                                          "id": 1,
     *                                          "title": "Option A",
     *                                      }
     *                                  }
     *                              }
     *                          }
     *                      }
     *                  }},
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="name", type="string"),
     *                      @OA\Property(property="email", type="email"),
     *                      @OA\Property(property="avatar", type="string"),
     *                      @OA\Property(property="number", type="integer"),
     *                      @OA\Property(
     *                          property="results",
     *                          type="array",
     *                          @OA\Items(
     *                              @OA\Property(property="id", type="integer"),
     *                              @OA\Property(property="user_id", type="integer"),
     *                              @OA\Property(property="quiz_id", type="integer"),
     *                              @OA\Property(property="score", type="integer"),
     *                              @OA\Property(property="created_at", type="string", format="date-time"),
     *                              @OA\Property(
     *                                  property="result_quizzes",
     *                                  type="array",
     *                                  @OA\Items(
     *                                      @OA\Property(property="id", type="integer"),
     *                                      @OA\Property(property="result_id", type="integer"),
     *                                      @OA\Property(property="question_id", type="integer"),
     *                                      @OA\Property(property="correct", type="integer"),
     *                                      @OA\Property(property="question", type="object"),
     *                                      @OA\Property(property="option", type="object"),
     *                                  ),
     *                              )
     *                          ),
     *                      ),
     *                  ),
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
    public function quizResultSubmitted($slug)
    {
        $quiz = Quiz::where('slug', $slug)->first();
        if (!$quiz) return $this->responseFailed('Data tidak ditemukan', '', 404);

        $data = User::select('id', 'name', 'email', 'avatar', 'number')
                        ->where('role', 'siswa')
                        ->whereHas('results', function($q) use($quiz) {
                            $q->where('quiz_id', $quiz->id);
                        })
                        ->with(['results' => function($q) use($quiz) {
                           $q->select('id', 'user_id', 'quiz_id', 'score', 'created_at')->where('quiz_id', $quiz->id);
                        },
                        'results.result_quizzes' => function($q) {
                            $q->select('id', 'result_id', 'question_id', 'option_id', 'correct');
                        },
                        'results.result_quizzes.question:id,question',
                        'results.result_quizzes.option:id,title'
                        ])
                        ->get();
        
        return $this->responseSuccess('Data', $data);
    }

    /**
     * @OA\Get(
     *      path="/api/guru/result/{slug}/essay",
     *      operationId="getEssayResult",
     *      tags={"Results"},
     *      summary="Get List Essay Result",
     *      description="Get List Essay Result",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="Slug of Quizzes",
     *          required=true,
     *          example="first-essay-630ee82c7a9da",
     *          @OA\Schema(type="string")
     *      ),
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
     *                      "name": "siswa",
     *                      "email": "siswa@gmail.com",
     *                      "avatar": "1727520681.jpg",
     *                      "number": null,
     *                      "results": {
     *                          {
     *                              "id": 1,
     *                              "user_id": 1,
     *                              "quiz_id": 1,
     *                              "score": 10,
     *                              "created_at": "2022-08-31T09:14:18.000000Z",
     *                              "result_essays": {
     *                                  {
     *                                      "id": 1,
     *                                      "result_id": 1,
     *                                      "question_id": 1,
     *                                      "comment": null,
     *                                      "file": "2016581590.pdf",
     *                                      "question": {
     *                                          "id": 1,
     *                                          "question": "Question One Update"
     *                                      },
     *                                  }
     *                              }
     *                          }
     *                      }
     *                  }},
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="name", type="string"),
     *                      @OA\Property(property="email", type="email"),
     *                      @OA\Property(property="avatar", type="string"),
     *                      @OA\Property(property="number", type="integer"),
     *                      @OA\Property(
     *                          property="results",
     *                          type="array",
     *                          @OA\Items(
     *                              @OA\Property(property="id", type="integer"),
     *                              @OA\Property(property="user_id", type="integer"),
     *                              @OA\Property(property="quiz_id", type="integer"),
     *                              @OA\Property(property="score", type="integer"),
     *                              @OA\Property(property="created_at", type="string", format="date-time"),
     *                              @OA\Property(
     *                                  property="result_essays",
     *                                  type="array",
     *                                  @OA\Items(
     *                                      @OA\Property(property="id", type="integer"),
     *                                      @OA\Property(property="result_id", type="integer"),
     *                                      @OA\Property(property="question_id", type="integer"),
     *                                      @OA\Property(property="comment", type="string"),
     *                                      @OA\Property(property="file", type="string"),
     *                                      @OA\Property(property="question", type="object"),
     *                                  ),
     *                              )
     *                          ),
     *                      ),
     *                  ),
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
    public function essayResultSubmitted($slug)
    {
        $quiz = Quiz::where('slug', $slug)->first();
        if (!$quiz) return $this->responseFailed('Data tidak ditemukan', '', 404);

        $data = User::select('id', 'name', 'email', 'avatar', 'number')
                        ->where('role', 'siswa')
                        ->whereHas('results', function($q) use($quiz) {
                            $q->where('quiz_id', $quiz->id);
                        })
                        ->with(['results' => function($q) use($quiz) {
                           $q->select('id', 'user_id', 'quiz_id', 'score', 'created_at')->where('quiz_id', $quiz->id);
                        },
                        'results.result_essays' => function($q) {
                            $q->select('id', 'result_id', 'question_id', 'comment', 'file');
                        },
                        'results.result_essays.question:id,question',
                        ])
                        ->get();
        
        return $this->responseSuccess('Data', $data);
    }

    /**
     * @OA\Put(
     *      path="/api/guru/result/{id}",
     *      operationId="storeEssayResultScore",
     *      tags={"Results"},
     *      summary="Store Essay Result Score in DB",
     *      description="Store Essay Result Score in DB",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="Id of Result",
     *          required=true,
     *          example=1,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  type="object",
     *                  required={"score"},
     *                  @OA\Property(property="score", type="integer", example=100),
     *              ),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Score berhasil dibuat"),
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
    public function createScoreEssay($resultId, Request $request)
    {
        $result = Result::where('id', $resultId)->first();
        if (!$result) return $this->responseFailed('Data tidak ditemukan', '', 404);

        $input = $request->only('score');
        $validator = Validator::make($input, [
            'score' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return $this->responseFailed('Validasi error', $validator->errors(), 400);
        }

        $result->update(['score' => $input['score']]);

        return $this->responseSuccess('Score berhasil dibuat');
    }

    /**
     * @OA\Get(
     *      path="/api/siswa/result",
     *      operationId="geStudentResult",
     *      tags={"Results"},
     *      summary="Get List Student Result",
     *      description="Get List Student Result",
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
     *                  example={
     *                  {
     *                      "id": 1,
     *                      "user_id": 1,
     *                      "quiz_id": 1,
     *                      "score": 10,
     *                      "created_at": "2021-09-15T08:55:53.000000Z",
     *                      "updated_at": "2021-09-15T08:55:53.000000Z",
     *                      "quiz": {
     *                          "id": 1,
     *                          "title": "First Quiz Update",
     *                          "slug": "first-quiz-630ee5b27b98e",
     *                          "type": "quiz",
     *                          "banner": null
     *                      },
     *                      "result_quizzes": {
     *                          {
     *                              "id": 1,
     *                              "result_id": 1,
     *                              "question_id": 1,
     *                              "option_id": 1,
     *                              "correct": 1,
     *                              "question": {
     *                                  "id": 1,
     *                                  "question": "Question One Update"
     *                              },
     *                              "option": {
     *                                  "id": 1,
     *                                  "title": "Option A",
     *                              }
     *                          }
     *                      },
     *                      "result_essays": {}
     *                  },
     *                  {
     *                      "id": 4,
     *                      "user_id": 1,
     *                      "quiz_id": 2,
     *                      "score": 100,
     *                      "created_at": "2021-09-15T08:55:53.000000Z",
     *                      "updated_at": "2021-09-15T08:55:53.000000Z",
     *                      "quiz": {
     *                          "id": 2,
     *                          "title": "First Essay",
     *                          "slug": "first-essay-630ee82c7a9da",
     *                          "type": "essay",
     *                          "banner": null
     *                      },
     *                      "result_quizzes": {},
     *                      "result_essays": {
     *                          {
     *                              "id": 3,
     *                              "result_id": 4,
     *                              "question_id": 3,
     *                              "comment": "Has been submitted",
     *                              "file": "2016581590.pdf",
     *                              "question": {
     *                                  "id": 3,
     *                                  "question": "Question One"
     *                              },
     *                          }
     *                      }
     *                  }
     *                  },
     *                  @OA\Items(type="string"),
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
    public function studentResultSubmitted()
    {
        $data = Result::where('user_id', auth()->user()->id)
                        ->with([
                        'quiz:id,title,slug,type,banner',
                        'result_quizzes' => function($q) {
                            $q->select('id', 'result_id', 'question_id', 'option_id', 'correct');
                        },
                        'result_quizzes.question:id,question',
                        'result_quizzes.option:id,title',
                        'result_essays' => function($q) {
                            $q->select('id', 'result_id', 'question_id', 'comment', 'file');
                        },
                        'result_essays.question:id,question',
                        ])
                        ->get();
        
        return $this->responseSuccess('Data', $data);
    }
}

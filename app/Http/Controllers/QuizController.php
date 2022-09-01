<?php

namespace App\Http\Controllers;

use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Result;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class QuizController extends Controller
{

    /**
     * @OA\Get(
     *      path="/api/quizzes",
     *      operationId="getQuizList",
     *      tags={"Quizzes"},
     *      summary="Get List of Quizzes",
     *      description="Get List of Quizzes",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="type",
     *          description="Type of quizzes",
     *          in="query",
     *          required=true,
     *          @OA\Schema(
     *              type="string", default="quiz", enum={"quiz", "essay"}
     *          )
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
     *                      "title": "First Quiz",
     *                      "slug": "first-quiz-615dbbd9b550c",
     *                      "type": "quiz",
     *                      "deadline": "2021-10-11 22:08:09",
     *                      "banner": "1585923272.png",
     *                      "created_at": "2021-10-06T15:08:09.000000Z",
     *                      "updated_at": "2021-10-06T15:08:09.000000Z",
     *                      "questions": {
     *                          {
     *                              "id": 1,
     *                              "quiz_id": 1,
     *                              "question": "Question One",
     *                              "file": "173709104.jpg",
     *                              "options": {
     *                                  {
     *                                      "id": 1,
     *                                      "question_id": 1,
     *                                      "title": "Option A",
     *                                  },
     *                                  {
     *                                      "id": 2,
     *                                      "question_id": 1,
     *                                      "title": "Option B",
     *                                  },
     *                                  {
     *                                      "id": 3,
     *                                      "question_id": 1,
     *                                      "title": "Option C",
     *                                  },
     *                              }
     *                          }
     *                      }
     *                  }},
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="title", type="string"),
     *                      @OA\Property(property="slug", type="string"),
     *                      @OA\Property(property="type", type="string"),
     *                      @OA\Property(property="deadline", type="string", format="date-time"),
     *                      @OA\Property(property="banner", type="string"),
     *                      @OA\Property(property="created_at", type="string", format="date-time"),
     *                      @OA\Property(property="updated_at", type="string", format="date-time"),
     *                      @OA\Property(
     *                          property="questions",
     *                          type="array",
     *                          @OA\Items(
     *                              @OA\Property(property="id", type="integer"),
     *                              @OA\Property(property="quiz_id", type="integer"),
     *                              @OA\Property(property="question", type="string"),
     *                              @OA\Property(property="file", type="string"),
     *                              @OA\Property(
     *                                  property="options",
     *                                  type="array",
     *                                  @OA\Items(
     *                                      @OA\Property(property="id", type="integer"),
     *                                      @OA\Property(property="question_id", type="integer"),
     *                                      @OA\Property(property="title", type="string"),
     *                                  ),
     *                              ),
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
     *          response=500,
     *          description="Internal server error",
     *      ),
     * )
     */
    public function index()
    {
        if (request()->type == 'quiz') {
            $data = Quiz::where('type', 'quiz')->with(['questions' => function ($q) {
                $q->select('id', 'quiz_id', 'question', 'file');
            }, 'questions.options' => function ($q) {
                $q->select('id', 'question_id', 'title');
            }])->get();
        } else {
            $data = Quiz::where('type', 'essay')->with(['questions' => function ($q) {
                $q->select('id', 'quiz_id', 'question', 'file');
            }])->get();
        }

        return $this->responseSuccess('Data', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * @OA\Post(
     *      path="/api/guru/quizzes",
     *      operationId="storeQuiz",
     *      tags={"Quizzes"},
     *      summary="Store Quiz in DB",
     *      description="Store Quiz in DB",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  type="object",
     *                  required={"title", "type", "deadline", "questions"},
     *                  nullable={"banner"},
     *                  @OA\Property(property="title", type="string", example="First Quiz"),
     *                  @OA\Property(property="type", type="string", enum={"quiz", "essay"}, example="quiz",
     *                      description="If type `quiz` options field inside questions are required. Else, don't attach options property in request.",
     *                  ),
     *                  @OA\Property(property="deadline", type="string", format="date-time", example="2022-10-11 22:08:09"),
     *                  @OA\Property(property="banner", type="file", description="Image or `null`.", example=null),
     *                  @OA\Property(
     *                      property="questions",
     *                      type="array",
     *                      required={"question"},
     *                      nullable={"file", "options"},
     *                      example={
     *                          {
     *                              "question": "Question One",
     *                              "file": null,
     *                              "options": {
     *                                 {
     *                                   "title": "Option A",
     *                                   "correct": 1
     *                                 },
     *                                 {
     *                                   "title": "Option B",
     *                                   "correct": 0
     *                                 },
     *                                 {
     *                                   "title": "Option C",
     *                                   "correct": 0
     *                                 },
     *                              }
     *                          },
     *                          {
     *                              "question": "Question Two",
     *                              "file": null,
     *                              "options": {
     *                                 {
     *                                   "title": "Option A",
     *                                   "correct": 1
     *                                 },
     *                                 {
     *                                   "title": "Option B",
     *                                   "correct": 0
     *                                 },
     *                                 {
     *                                   "title": "Option C",
     *                                   "correct": 0
     *                                 },
     *                              }
     *                          }
     *                      },
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="question", type="string", example="Question One"),
     *                          @OA\Property(property="file", type="file", description="File or `null`.", example=null),
     *                          @OA\Property(
     *                              property="options",
     *                              description="Required if type `quiz`.",
     *                              type="array",
     *                              required={"title", "correct"},
     *                              @OA\Items(
     *                                  type="object",
     *                                  @OA\Property(property="title", type="string", example="Option A"),
     *                                  @OA\Property(property="correct", type="integer", enum={1,0}, example=1),
     *                              ),
     *                          ),
     *                      ),
     *                  ),
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
     *                      "id": 1,
     *                      "title": "First Quiz",
     *                      "slug": "first-quiz-615dbbd9b550c",
     *                      "type": "quiz",
     *                      "deadline": "2021-10-11 22:08:09",
     *                      "banner": "1585923272.png",
     *                      "created_at": "2021-10-06T15:08:09.000000Z",
     *                      "updated_at": "2021-10-06T15:08:09.000000Z",
     *                      "questions": {
     *                          {
     *                              "id": 1,
     *                              "quiz_id": 1,
     *                              "question": "Question One",
     *                              "file": "173709104.jpg",
     *                              "options": {
     *                                  {
     *                                      "id": 1,
     *                                      "question_id": 1,
     *                                      "title": "Option A",
     *                                      "correct": 0
     *                                  },
     *                                  {
     *                                      "id": 2,
     *                                      "question_id": 1,
     *                                      "title": "Option B",
     *                                      "correct": 0
     *                                  },
     *                                  {
     *                                      "id": 3,
     *                                      "question_id": 1,
     *                                      "title": "Option C",
     *                                      "correct": 1
     *                                  },
     *                              }
     *                          }
     *                      }
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
            'type' => 'required|string',
            'title' => 'required|string',
            'deadline' => 'required|date',
            'banner' => 'nullable|mimes:jpeg,png,jpg',
            'questions' => 'required|array|between:1,10',
            'questions.*.question' => 'required|string',
            'questions.*.file' => 'nullable|mimes:jpeg,png,jpg,doc,docx,pdf',
            'questions.*.options' => 'sometimes|array|between:1,5',
            'questions.*.options.*.title' => 'required|string',
            'questions.*.options.*.correct' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->responseFailed('Validasi error', $validator->errors(), 400);
        }

        try {
            DB::beginTransaction();

            $input['banner'] = null;
            if ($request->hasFile('banner')) {
                $input['banner'] = rand() . '.' . request()->banner->getClientOriginalExtension();
    
                request()->banner->move(public_path('storage/images/quiz/'), $input['banner']);
            }
            
            $quiz = Quiz::create([
                'title' => $input['title'],
                'slug' =>  Str::slug($input['title']).'-'.uniqid(),
                'type' => $input['type'],
                'deadline' => $input['deadline'],
                'banner' => $input['banner']
            ]);

            foreach ($input['questions'] as $key => $questionValue) {
                $questionValue['file'] = null;
                if ($request->hasFile('questions.' . $key . '.file')) {
                    $questionValue['file'] = rand().'.'.$request->questions[$key]['file']->getClientOriginalExtension();

                    $request->questions[$key]['file']->move(public_path('storage/files/quiz/'), $questionValue['file']);
                }

                $question = Question::create([
                    'quiz_id' => $quiz->id,
                    'question' => $questionValue['question'],
                    'file' => $questionValue['file']
                ]);

                if ($quiz->type == 'quiz') {
                    foreach ($questionValue['options'] as $optionValue) {
                        Option::create([
                            'question_id' => $question->id,
                            'title' => $optionValue['title'],
                            'correct' => +$optionValue['correct']
                        ]);
                    }
                }
            }

            DB::commit();

            if ($quiz->type == 'quiz') {
                $data = Quiz::where('slug', $quiz->slug)->with(['questions' => function ($q) {
                    $q->select('id', 'quiz_id', 'question', 'file');
                }, 'questions.options' => function ($q) {
                    $q->select('id', 'question_id', 'title', 'correct');
                }])->first();
            } else {
                $data = Quiz::where('slug', $quiz->slug)->with(['questions' => function ($q) {
                    $q->select('id', 'quiz_id', 'question', 'file');
                }])->first();
            }

            return $this->responseSuccess('Data berhasil dibuat', $data, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->responseFailed('Data gagal dibuat');
        }
    }

    /**
     * @OA\Get(
     *      path="/api/quizzes/{slug}",
     *      operationId="showQuizzes",
     *      tags={"Quizzes"},
     *      summary="Get Quizzes Detail",
     *      description="Get Quizzes Detail",
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
     *                      "title": "First Quiz",
     *                      "slug": "first-quiz-615dbbd9b550c",
     *                      "type": "quiz",
     *                      "deadline": "2021-10-11 22:08:09",
     *                      "banner": "1585923272.png",
     *                      "created_at": "2021-10-06T15:08:09.000000Z",
     *                      "updated_at": "2021-10-06T15:08:09.000000Z",
     *                      "questions": {
     *                          {
     *                              "id": 1,
     *                              "quiz_id": 1,
     *                              "question": "Question One",
     *                              "file": "173709104.jpg",
     *                              "options": {
     *                                  {
     *                                      "id": 1,
     *                                      "question_id": 1,
     *                                      "title": "Option A",
     *                                      "correct": 1
     *                                  },
     *                                  {
     *                                      "id": 2,
     *                                      "question_id": 1,
     *                                      "title": "Option B",
     *                                      "correct": 0
     *                                  },
     *                                  {
     *                                      "id": 3,
     *                                      "question_id": 1,
     *                                      "title": "Option C",
     *                                      "correct": 0
     *                                  },
     *                              }
     *                          }
     *                      }
     *                  }
     *              ),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Already submitted this quizzes",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Gagal"),
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
     * )
     */
    public function show($slug)
    {
        $quiz = Quiz::where('slug', $slug)->first();
        if (!$quiz) return $this->responseFailed('Data tidak ditemukan', '', 404);

        if ($quiz->type == 'quiz') {
            $isResult = Result::where([
                            'quiz_id' => $quiz->id,
                            'user_id' => auth()->user()->id
                        ])->first();
            if(isset($isResult)) {
                return $this->responseFailed('Gagal', 'User sudah mengerjakan quiz ini', 400);
            }

            $data = Quiz::where('slug', $quiz->slug)->with(['questions' => function ($q) {
                $q->select('id', 'quiz_id', 'question', 'file');
            }, 'questions.options' => function ($q) {
                if (auth()->user()->role == 'siswa') {
                    $q->select('id', 'question_id', 'title');
                } else {
                    $q->select('id', 'question_id', 'title', 'correct');
                }
            }])->first();
        } else {
            $data = Quiz::where('slug', $quiz->slug)->with(['questions' => function ($q) {
                $q->select('id', 'quiz_id', 'question', 'file');
            }])->first();
        }

        return $this->responseSuccess('Detail data', $data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * @OA\Post(
     *      path="/api/guru/quizzes/{slug}",
     *      operationId="updateQuiz",
     *      tags={"Quizzes"},
     *      summary="Update Quiz in DB",
     *      description="Update Quiz in DB",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="Slug of Materi",
     *          required=true,
     *          example="first-quiz-630ee5b27b98e",
     *          @OA\Schema(type="string")
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
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  type="object",
     *                  required={"title", "type", "deadline", "questions"},
     *                  nullable={"banner"},
     *                  @OA\Property(property="title", type="string", example="First Quiz Update"),
     *                  @OA\Property(property="type", type="string", enum={"quiz", "essay"}, example="quiz",
     *                      description="If type `quiz` options field inside questions are required. Else, don't attach options property in request.",
     *                  ),
     *                  @OA\Property(property="deadline", type="string", format="date-time", example="2022-10-11 22:08:09"),
     *                  @OA\Property(property="banner", type="file", description="Image or `null`.", example=null),
     *                  @OA\Property(
     *                      property="questions",
     *                      type="array",
     *                      required={"id", "question"},
     *                      nullable={"file", "options"},
     *                      example={
     *                          {
     *                              "id": 1,
     *                              "question": "Question One Update",
     *                              "file": null,
     *                              "options": {
     *                                 {
     *                                   "id": 1,
     *                                   "title": "Option A Update",
     *                                   "correct": 1
     *                                 },
     *                                 {
     *                                   "id": 2,
     *                                   "title": "Option B Update",
     *                                   "correct": 0
     *                                 },
     *                                 {
     *                                   "id": 3,
     *                                   "title": "Option C Update",
     *                                   "correct": 0
     *                                 },
     *                              }
     *                          },
     *                          {
     *                              "id": 2,
     *                              "question": "Question Two Update",
     *                              "file": null,
     *                              "options": {
     *                                 {
     *                                   "id": 4,
     *                                   "title": "Option A Update",
     *                                   "correct": 1
     *                                 },
     *                                 {
     *                                   "id": 5,
     *                                   "title": "Option B Update",
     *                                   "correct": 0
     *                                 },
     *                                 {
     *                                   "id": 6,
     *                                   "title": "Option C Update",
     *                                   "correct": 0
     *                                 },
     *                              }
     *                          }
     *                      },
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="id", type="integer", example="1", description="Set to `-1` to add new data"),
     *                          @OA\Property(property="question", type="string", example="Question One"),
     *                          @OA\Property(property="file", type="file", description="File or `null`.", example=null),
     *                          @OA\Property(
     *                              property="options",
     *                              description="Required if type `quiz`.",
     *                              type="array",
     *                              required={"id", "title", "correct"},
     *                              @OA\Items(
     *                                  type="object",
     *                                  @OA\Property(property="id", type="integer", example="1", description="Set to `-1` to add new data"),
     *                                  @OA\Property(property="title", type="string", example="Option A"),
     *                                  @OA\Property(property="correct", type="integer", enum={1,0}, example=1),
     *                              ),
     *                          ),
     *                      ),
     *                  ),
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
     *                      "id": 1,
     *                      "title": "First Quiz Update",
     *                      "slug": "first-quiz-615dbbd9b550c",
     *                      "type": "quiz",
     *                      "deadline": "2021-10-11 22:08:09",
     *                      "banner": "1585923272.png",
     *                      "created_at": "2021-10-06T15:08:09.000000Z",
     *                      "updated_at": "2021-10-06T15:08:09.000000Z",
     *                      "questions": {
     *                          {
     *                              "id": 1,
     *                              "quiz_id": 1,
     *                              "question": "Question One Update",
     *                              "file": "173709104.jpg",
     *                              "options": {
     *                                  {
     *                                      "id": 1,
     *                                      "question_id": 1,
     *                                      "title": "Option A Update",
     *                                      "correct": 0
     *                                  },
     *                                  {
     *                                      "id": 2,
     *                                      "question_id": 1,
     *                                      "title": "Option B Update",
     *                                      "correct": 0
     *                                  },
     *                                  {
     *                                      "id": 3,
     *                                      "question_id": 1,
     *                                      "title": "Option C Update",
     *                                      "correct": 1
     *                                  },
     *                              }
     *                          }
     *                      }
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
    public function update(Request $request, $slug)
    {
        $quiz = Quiz::where('slug', $slug)->with('questions')->first();
        if (!$quiz) return $this->responseFailed('Data tidak ditemukan', '', 404);
        if ($quiz->type == 'quiz') {
            $quiz = Quiz::where('slug', $slug)->with('questions.options')->first();
        }

        $input = $request->all();
        $validator = Validator::make($input, [
            'title' => 'required|string',
            'deadline' => 'required|date',
            'banner' => 'nullable|mimes:jpeg,png,jpg',
            'questions' => 'required|array|between:1,10',
            'questions.*.id' => 'required|numeric',
            'questions.*.question' => 'required|string',
            'questions.*.file' => 'nullable',
            'questions.*.options' => 'sometimes|array|between:1,5',
            'questions.*.options.*.id' => 'required|numeric',
            'questions.*.options.*.title' => 'required|string',
            'questions.*.options.*.correct' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->responseFailed('Validasi error', $validator->errors(), 400);
        }

        try {
            DB::beginTransaction();

            $oldBanner = $quiz->banner;
            if ($request->hasFile('banner')) {
                File::delete('storage/images/quiz/' . $oldBanner);
                $input['banner'] = rand() . '.' . request()->banner->getClientOriginalExtension();
    
                request()->banner->move(public_path('storage/images/quiz/'), $input['banner']);
            } else {
                $input['banner'] = $oldBanner;
            }

            $quiz->update([
                'title' => $input['title'],
                'deadline' => $input['deadline'],
                'banner' => $input['banner']
            ]);

            foreach ($input['questions'] as $key => $questionValue) {
                if ($questionValue['id'] == -1) {
                    $questionValue['file'] = null;
                    if ($request->hasFile('questions.' . $key . '.file')) {
                        $questionValue['file'] = rand().'.'.$request->questions[$key]['file']->getClientOriginalExtension();

                        $request->questions[$key]['file']->move(public_path('storage/files/quiz/'), $questionValue['file']);
                    }

                    $question = Question::create([
                        'quiz_id' => $quiz->id,
                        'question' => $questionValue['question'],
                        'file' => $questionValue['file']
                    ]);

                    if ($quiz->type == 'quiz') {
                        foreach ($questionValue['options'] as $optionValue) {
                            if ($optionValue['id'] == -1) {
                                Option::create([
                                    'question_id' => $question->id,
                                    'title' => $optionValue['title'],
                                    'correct' => +$optionValue['correct']
                                ]);
                            }
                        }
                    }
                } else {
                    $oldFile = $quiz->questions[$key]->file;
                    if ($request->hasFile('questions.' . $key . '.file')) {
                        File::delete('storage/files/quiz/' . $oldFile);
                        $questionValue['file'] = rand() . '.' . $request->questions[$key]['file']->getClientOriginalExtension();

                        $request->questions[$key]['file']->move(public_path('storage/files/quiz/'), $questionValue['file']);
                    } else {
                        $questionValue['file'] = $oldFile;
                    }

                    Question::where('id', $questionValue['id'])
                        ->update([
                            'question' => $questionValue['question'],
                            'file' => $questionValue['file']
                        ]);

                    if ($quiz->type == 'quiz') {
                        foreach ($questionValue['options'] as $key2 => $optionValue) {
                            if ($optionValue['id'] == -1) {
                                Option::create([
                                    'question_id' => $questionValue['id'],
                                    'title' => $optionValue['title'],
                                    'correct' => +$optionValue['correct']
                                ]);
                            } else {
                                Option::where('id', $optionValue['id'])
                                    ->update([
                                        'title' => $optionValue['title'],
                                        'correct' => +$optionValue['correct']
                                    ]);
                            }
                        }
                    }
                }
            }

            DB::commit();

            if ($quiz->type == 'quiz') {
                $data = Quiz::where('slug', $quiz->slug)->with(['questions' => function ($q) {
                    $q->select('id', 'quiz_id', 'question', 'file');
                }, 'questions.options' => function ($q) {
                    $q->select('id', 'question_id', 'title', 'correct');
                }])->first();
            } else {
                $data = Quiz::where('slug', $quiz->slug)->with(['questions' => function ($q) {
                    $q->select('id', 'quiz_id', 'question', 'file');
                }])->first();
            }

            return $this->responseSuccess('Data berhasil diubah', $data, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->responseFailed('Data gagal diubah');
        }
    }

    /**
     * @OA\Delete(
     *      path="/api/guru/quizzes/{slug}",
     *      operationId="destroyQuizzes",
     *      tags={"Quizzes"},
     *      summary="Delete Quizzes",
     *      description="Delete Quizzes",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="Slug of Materi",
     *          required=true,
     *          example="first-quiz-630ee5b27b98e",
     *          @OA\Schema(type="string")
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
    public function destroy($slug)
    {
        $quiz = Quiz::where('slug', $slug)->with('questions')->first();
        if (!$quiz) return $this->responseFailed('Data tidak ditemukan', '', 404);

        if ($quiz->banner) {
            File::delete('storage/images/quiz/' . $quiz->banner);
        }

        foreach ($quiz->questions as $questionValue) {
            if ($questionValue->file) {
                File::delete('storage/files/quiz/' . $questionValue->file);
            }
        }

        $quiz->delete();

        return $this->responseSuccess('Data berhasil dihapus');
    }

    /**
     * @OA\Delete(
     *      path="/api/guru/quizzes/questions/{id}/file",
     *      operationId="deleteFileQuestion",
     *      tags={"Quizzes"},
     *      summary="Delete Question File",
     *      description="Delete Question File",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="Id of Question",
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
     *              @OA\Property(property="message", type="string", example="File berhasil dihapus"),
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
    public function deleteQuestionFile($id)
    {
        $question = Question::find($id);
        if (!$question) return $this->responseFailed('Data tidak ditemukan', '', 404);
        if (!$question->file) return $this->responseFailed('File tidak ada', '', 400);

        File::delete('storage/files/quiz/' . $question->file);
        $question->update(['file' => null]);

        return $this->responseSuccess('File berhasil dihapus');
    }

    /**
     * @OA\Delete(
     *      path="/api/guru/quizzes/options/{id}",
     *      operationId="destroyOptions",
     *      tags={"Quizzes"},
     *      summary="Delete Options",
     *      description="Delete Options",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="Id of Options",
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
     *              @OA\Property(property="message", type="string", example="Pilihan berhasil dihapus"),
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
    public function deleteOption($id)
    {
        $option = Option::find($id);
        if (!$option) return $this->responseFailed('Data tidak ditemukan', '', 404);

        $option->delete();

        return $this->responseSuccess('Pilihan berhasil dihapus');
    }
}

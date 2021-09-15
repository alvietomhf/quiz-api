<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\Result;
use App\Models\ResultEssay;
use App\Models\ResultQuiz;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ResultController extends Controller
{
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

            $input = json_decode($inputRaw['data']);

            $data = [
                'user_id' => auth()->user()->id,
                'quiz_id' => $input[0]->quiz_id
            ];
            $result = Result::create($data);
            $score = 0;

            foreach ($input as $item) {
                foreach ($item->options as $option) {
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
                            'correct' => $option->correct == $option->selected ? true : false,
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
    
                request()->file->move(public_path('assets/files/quiz/'), $input['file']);
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

    public function resultNotSubmitted($slug)
    {
        $quiz = Quiz::where('slug', $slug)->first();
        if (!$quiz) return $this->responseFailed('Data tidak ditemukan', '', 404);

        $data = User::select('id', 'name', 'email', 'avatar')
                        ->where('role', 'siswa')
                        ->whereDoesntHave('results', function($q) use($quiz) {
                            $q->where('quiz_id', $quiz->id);
                        })->get();
        
        return $this->responseSuccess('Data', $data);
    }

    public function quizResultSubmitted($slug)
    {
        $quiz = Quiz::where('slug', $slug)->first();
        if (!$quiz) return $this->responseFailed('Data tidak ditemukan', '', 404);

        $data = User::select('id', 'name', 'email', 'avatar')
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

    public function essayResultSubmitted($slug)
    {
        $quiz = Quiz::where('slug', $slug)->first();
        if (!$quiz) return $this->responseFailed('Data tidak ditemukan', '', 404);

        $data = User::select('id', 'name', 'email', 'avatar')
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
}

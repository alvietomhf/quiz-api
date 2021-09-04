<?php

namespace App\Http\Controllers;

use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class QuizController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (request()->type == 'quiz') {
            $data = Quiz::where('type', 'quiz')->with(['questions' => function ($q) {
                $q->select('id', 'quiz_id', 'question', 'file');
            }, 'questions.options' => function ($q) {
                $q->select('id', 'question_id', 'title', 'correct');
            }])->get();
        } else {
            $data = Quiz::where('type', 'essay')->with(['questions' => function ($q) {
                $q->select('id', 'quiz_id', 'question', 'file');
            }])->get();
        }

        return $this->responseSuccess('Data', ($data ?? null));
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'type' => 'required|string',
            'title' => 'required|string',
            'deadline' => 'required|date',
            'questions' => 'required|array|between:1,10',
            'questions.*.question' => 'required|string',
            'questions.*.file' => 'nullable|mimes:jpeg,png,jpg,doc,docx,pdf',
            'questions.*.options.*.title' => 'required|string',
            'questions.*.options.*.correct' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->responseFailed('Validasi error', $validator->errors(), 400);
        }

        try {
            DB::beginTransaction();

            $quiz = Quiz::create([
                'title' => $input['title'],
                'slug' =>  Str::slug($input['title']),
                'type' => $input['type'],
                'deadline' => $input['deadline']
            ]);

            foreach ($input['questions'] as $key => $questionValue) {
                $questionValue['file'] = null;
                if ($request->hasFile('questions.' . $key . '.file')) {
                    $questionValue['file'] = rand().'.'.$request->questions[$key]['file']->getClientOriginalExtension();

                    $request->questions[$key]['file']->move(public_path('assets/files/quiz/'), $questionValue['file']);
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
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        $quiz = Quiz::where('slug', $slug)->first();
        if (!$quiz) return $this->responseFailed('Data tidak ditemukan', '', 404);

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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
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
            'questions' => 'required|array|between:1,10',
            'questions.*.question' => 'required|string',
            'questions.*.file' => 'nullable|mimes:jpeg,png,jpg,doc,docx,pdf',
            'questions.*.options.*.title' => 'required|string',
            'questions.*.options.*.correct' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->responseFailed('Validasi error', $validator->errors(), 400);
        }

        try {
            DB::beginTransaction();

            $quiz->update([
                'title' => $input['title'],
                'deadline' => $input['deadline']
            ]);

            foreach ($input['questions'] as $key => $questionValue) {
                $oldFile = $quiz->questions[$key]->file;
                if ($request->hasFile('questions.' . $key . '.file')) {
                    File::delete('assets/files/quiz/' . $oldFile);
                    $questionValue['file'] = rand() . '.' . $request->questions[$key]['file']->getClientOriginalExtension();

                    $request->questions[$key]['file']->move(public_path('assets/files/quiz/'), $questionValue['file']);
                } else {
                    $questionValue['file'] = $oldFile;
                }

                Question::where('id', $quiz->questions[$key]->id)
                    ->update([
                        'question' => $questionValue['question'],
                        'file' => $questionValue['file']
                    ]);

                if ($quiz->type == 'quiz') {
                    foreach ($questionValue['options'] as $key2 => $optionValue) {
                        Option::where('id', $quiz->questions[$key]->options[$key2]->id)
                            ->update([
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

            return $this->responseSuccess('Data berhasil diubah', $data, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->responseFailed('Data gagal diubah');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        $quiz = Quiz::where('slug', $slug)->with('questions')->first();
        if (!$quiz) return $this->responseFailed('Data tidak ditemukan', '', 404);

        foreach ($quiz->questions as $questionValue) {
            if ($questionValue->file) {
                File::delete('assets/files/quiz/' . $questionValue->file);
            }
        }

        $quiz->delete();

        return $this->responseSuccess('Data berhasil dihapus');
    }

    public function deleteQuestionFile($id)
    {
        $question = Question::find($id);
        if (!$question) return $this->responseFailed('Data tidak ditemukan', '', 404);
        if (!$question->file) return $this->responseFailed('File tidak ditemukan', '', 400);

        File::delete('assets/files/quiz/' . $question->file);
        $question->update(['file' => null]);

        return $this->responseSuccess('File berhasil dihapus');
    }
}

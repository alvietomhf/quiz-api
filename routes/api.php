<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\MateriController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\UserController;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public routes
Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);

// Protected routes
Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('quizzes', [QuizController::class, 'index']);
    Route::get('quizzes/{quizzes:slug}', [QuizController::class, 'show']);

    Route::post('feeds', [FeedController::class, 'feedStore']);
    Route::post('feeds/{feedId}/reply', [FeedController::class, 'replyStore']);
    Route::get('feeds', [FeedController::class, 'feedIndex']);

    Route::get('materi', [MateriController::class, 'index']);
    Route::get('materi/{id}', [MateriController::class, 'show']);

    Route::group(['prefix' => 'siswa', 'middleware' => ['siswa']], function () {
        Route::get('result', [ResultController::class, 'studentResultSubmitted']);
        Route::post('result/{slug}/quiz', [ResultController::class, 'quizStore']);
        Route::post('result/{slug}/essay', [ResultController::class, 'essayStore']);
    });

    Route::group(['prefix' => 'guru', 'middleware' => ['guru']], function () {
        Route::post('result/{slug}/quiz', [ResultController::class, 'quizStore']);
        Route::post('quizzes', [QuizController::class, 'store']);
        Route::put('quizzes/{quizzes:slug}', [QuizController::class, 'update']);
        Route::delete('quizzes/{quizzes:slug}', [QuizController::class, 'destroy']);
        Route::delete('quizzes/questions/{id}/file', [QuizController::class, 'deleteQuestionFile']);
        Route::delete('quizzes/options/{id}', [QuizController::class, 'deleteOption']);

        Route::get('result/{slug}/notsubmitted', [ResultController::class, 'resultNotSubmitted']);
        Route::get('result/{slug}/quiz', [ResultController::class, 'quizResultSubmitted']);
        Route::get('result/{slug}/essay', [ResultController::class, 'essayResultSubmitted']);
        Route::put('result/{id}', [ResultController::class, 'createScoreEssay']);

        Route::post('materi', [MateriController::class, 'store']);
        Route::put('materi/{id}', [MateriController::class, 'update']);
        Route::delete('materi/{id}', [MateriController::class, 'destroy']);
        Route::delete('materi/{id}/image', [MateriController::class, 'deleteMateriImage']);
    });

    Route::get('users', [UserController::class, 'index']);
    Route::get('users/status', [UserController::class, 'status']);
    Route::get('users/{id}', [UserController::class, 'show']);
    Route::get('students', [UserController::class, 'studentIndex']);
    Route::get('teachers', [UserController::class, 'teacherIndex']);

    Route::post('logout', [AuthController::class, 'logout']);
});

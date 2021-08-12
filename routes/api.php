<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\QuizController;
use App\Models\Quiz;
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
Route::post('register/student', [AuthController::class, 'studentRegister']);
Route::post('register/teacher', [AuthController::class, 'teacherRegister']);

// Protected routes
Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('quizzes', [QuizController::class, 'index']);
    Route::get('quizzes/{quizzes:slug}', [QuizController::class, 'show']);

    Route::group(['prefix' => 'siswa', 'middleware' => ['siswa']], function () {

    });

    Route::group(['prefix' => 'guru', 'middleware' => ['guru']], function () {
        Route::post('quizzes', [QuizController::class, 'store']);
        Route::put('quizzes/{quizzes:slug}', [QuizController::class, 'update']);
        Route::delete('quizzes/{quizzes:slug}', [QuizController::class, 'destroy']);
    });

    Route::post('logout', [AuthController::class, 'logout']);
});

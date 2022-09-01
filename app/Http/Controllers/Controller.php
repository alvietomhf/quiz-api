<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     title="QuizApp API Documentation",
 *     version="1",
 *     @OA\Contact(
 *         email="haykalcs@gmail.com"
 *     ),
 * ),
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer"
 * )
 */

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function responseSuccess($msg, $arr = null, $status = 200)
    {
        $res = [
            'status' => true,
            'message' => ($msg == "") ? "Sukses" : $msg,
        ];

        if($arr) {
            $res['data'] = $arr;
        }

        return response()->json($res, $status);
    }

    protected function responseFailed($msg = null, $arr = null, $status = 500)
    {
        $res = [
            'status' => false,
            'message' => (!$msg) ? "Gagal" : $msg,
        ];

        if($arr) {
            $res['data'] = $arr;
        }

        return response()->json($res, $status);
    }

}

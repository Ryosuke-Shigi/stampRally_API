<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

abstract class BaseController extends Controller
{
    protected function _success($data = [])
    {
        $success_data = array_merge(['status' => 0], $data);
        return response()->json($success_data);
    }

    protected function _error($code)
    {
        // エラーメッセージを定義する
        $msg[1] = 'パラメータ不備';
        $msg[10] = '';
        $msg[101] = '';


        return response()->json([
            'status' => -1,
            'code' => $code,
            'message' => $msg[$code]
        ]);
    }
}

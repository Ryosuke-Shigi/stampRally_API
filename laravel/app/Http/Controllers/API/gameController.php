<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Storage;

//　取扱各モデル
use App\models\outuser;
use App\models\route;
//use App\models\start;
use App\models\point;
use App\models\goal;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class gameController extends BaseController
{
    //

    //指定コースのポイント数をカウントして返す
    //connect_idとroute_codeが必要
    public function Point_total_num(REQUEST $request){
        //もしroute_codeがなければエラー
        if(!$request->has('route_code')){
                return $this->_error(1);
        }
        $point_total_num=DB::table('points')
            ->where('route_code','=',$request->route_code)
            ->count();
        return $this->_success(['point_total_num'=>$point_total_num]);
    }


    //全てのルートを返す（新規ラリー選択画面用）
    public function allRoutes(REQUEST $request){
        $table = DB::table('routes')
                ->where('published','=',0)
                ->get();
        return $this->_success(['table'=>$table]);
    }

    //キーワードで検索したルートを返す（非公開含めて）
    public function keySearchRoutes(REQUEST $request){
        $table = DB::table('routes')
                    ->where('keyword','LIKE','%'.$request->keyword.'%')
                    ->get();
        return $this->_success(['table'=>$table]);
    }
    public function allPoints(REQUEST $request){
        $table = DB::table('points')
                    ->get();
        return $this->_success(['table'=>$table]);
    }
}

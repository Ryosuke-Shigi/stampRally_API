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
use App\models\status;
use App\models\score;
use App\models\stamp;

use carbon\Carbon;

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


    //チェックするポイントをテーブルで返す
    //statusがなければ自動的に作成　スタンプラリーを開始する
    //スタートの時間はとるが、現状では使わない（時間が短いほどいい　をなくすため）
    public function callPoints(REQUEST $request){
        $data = array();

        $routeTable = DB::table('points')
                    ->where('route_code','=',$request->route_code)
                    ->get();
        $userStatus = DB::table('statuses')
                    ->where('connect_id','=',$request->connect_id)
                    ->where('route_code','=',$request->route_code)
                    ->first();
        //ユーザがまだはじめていないラリーであれば
        //statusesにデータを作成して　全てのデータを返す
        //もし存在していれば、まだチェックしていないポイントを返却する
        if($userStatus === null){
            //新規にstatusesにユーザのレコードを作成する
            DB::beginTransaction();
            try{
                //connect_id と route_code で status作成
                $status=new status;
                $status->connect_id=$request->connect_id;
                $status->route_code=$request->route_code;
                //$status->started_at=Carbon::now();    //作成日時つけるかどうか
                $status->save();
                DB::commit();
            }catch(Esception $exception){
                DB::RollBack();
                throw $exception;
                return $this->_error(1);
            }
            //routeの全てのポイントデータを送る
            foreach($routeTable as $route){
                array_push($data,array(
                    'point_no'=>$route->point_no,
                    'latitude'=>$route->latitude,
                    'longitude'=>$route->longitude,
                    'pict'=>$route->pict,
                    'text'=>$route->text
                ));
            }
        }else{
            //持っているstampに同じNOがないかチェック
            $stampFlg = false;
            //スタンプ（攻略済ポイント）を取得する
            $userStamp = DB::table('stamps')
            ->where('connect_id','=',$request->connect_id)
            ->where('route_code','=',$request->route_code)
            ->get();
            //チェックしていないポイントデータを送る
            foreach($routeTable as $route){
                foreach($userStamp as $stamp){
                    if($route->point_no === $stamp->point_no){
                        $stampFlg = true;
                        continue;
                    }
                }
                if($stampFlg == false){
                    array_push($data,array(
                        'point_no'=>$route->point_no,
                        'latitude'=>$route->latitude,
                        'longitude'=>$route->longitude,
                        'pict'=>$route->pict,
                        'text'=>$route->text
                    ));
                }
                $stampFlg=false;
            }
        }
        return $this->_success(['table'=>$data]);
    }


}

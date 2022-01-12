<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Storage;

//　取扱各モデル
use App\models\outuser;
use App\models\route;
use App\models\point;
use App\models\goal;
use App\models\score;

use carbon\Carbon;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class createController extends BaseController
{

        //画像をＡＷＳに保存
    //スタート・ポイント・ゴールともに作成段階で使用します
    //画像保存　こちらで保存したPATHを返す
    public function createPict(REQUEST $request){
        $image=$request->file('pict');
        $responce=Storage::disk('s3')->put('/pict',$image,'public-read');
        return $this->_success(['path'=>$responce]);
    }


    //アプリ側でユーザ登録した段階で一緒に作成
    //個人を特定するID connect_id を　UUID で生成する。
    //outusersに登録されます
    public function createUser(REQUEST $request){
        DB::beginTransaction();
        try{
            $table = new outuser;

            $table->user_id = $request->user_id;
            $table->email = $request->email;
            $table->connect_id = (string)Str::uuid();
            $table->save();

            DB::commit();

        }catch(Esception $exception){
            DB::RollBack();
            throw $exception;
            return $this->_error(1);
        }

        return $this->_success(['connect_id'=>$table->connect_id]);
    }
    //ルート登録（ここから start point goal を作成していきます)
    //route_codeを返します
    public function routeCreate(REQUEST $request){
        $table = new route;
        DB::beginTransaction();
        try{
            $table->connect_id = $request->connect_id;
            $table->route_code = uniqid("route_",false);
            $table->route_name = $request->name;
            $table->pict = $request->pict;
            $table->keyword = $request->keyword;
            $table->text = $request->text;
            //公開・非公開　現状は０で固定しておく
            $table->published=0;
            //ポイントを全て設定し終えたら後から値が入ります。
            $table->point_total_num = 0;
            $table->save();
            DB::commit();

        }catch(Esception $exception){
            DB::RollBack();
            throw $exception;
            return $this->_error(1);
        }
        return $this->_success(['route_code'=>$table->route_code]);
    }
    //ポイント登録
    //登録したroute_codeとpoint_noを返す
    public function pointCreate(REQUEST $request){
        $table = new point;
        DB::beginTransaction();
        try{
            $table->connect_id  = $request->connect_id;
            $table->route_code  = $request->route_code;
            $table->point_no    = $request->point_no;
            $table->latitude    = $request->latitude;
            $table->longitude   = $request->longitude;
            $table->pict        = $request->pict;
            $table->text        = $request->text;
            $table->save();
            DB::commit();

        }catch(Esception $exception){
            DB::RollBack();
            throw $exception;
            return $this->_error(1);
        }
        return $this->_success(['route_code'=>$table->route_code,'point_no'=>$table->point_no]);
    }
    //ゴール登録
    //routeにポイント数を保存後、ゴールを登録
    //登録したroute_codeを返却する
    public function goalCreate(REQUEST $request){
        $table = new goal;
        DB::beginTransaction();
        try{
            //point数を数えて route の point_total_num（初期値：０）を更新する
            //ポイントを数える
            $point_total_num=DB::table('points')
            ->where('route_code','=',$request->route_code)
            ->count();
            //routeをfirst()で取得
            $routeTable = route::where('connect_id','=',$request->connect_id)
                                ->where('route_code','=',$request->route_code)
                                ->first();
            $routeTable->point_total_num = $point_total_num;
            $routeTable->save();


            //ゴールをを保存する
            $table->connect_id  = $request->connect_id;
            $table->route_code  = $request->route_code;
            $table->pict        = $request->pict;
            $table->text        = $request->text;
            $table->save();

            DB::commit();

        }catch(Esception $exception){
            DB::RollBack();
            throw $exception;
            return $this->_error(1);
        }
        return $this->_success(['route_code'=>$table->route_code]);
    }

    //Connect_IDとroute_IDを受け取り、ルートを削除する
    public function routeDelete(REQUEST $request){
        //作成していたPOINTのデータを全て削除する
        $pointData=DB::table('points')
                ->where('connect_id','=',$request->connect_id)
                ->where('route_code','=',$request->route_code)
                ->get();
        foreach($pointData as $temp){
            if($temp->pict != NULL){
                Storage::disk('s3')->delete($temp->pict);
            }
        }

        //ルートから連なるデータ削除
        DB::table('routes')
            ->where('connect_id','=',$request->connect_id)
            ->where('route_code','=',$request->route_code)
            ->delete();
        return $this->_success();

    }



    //////////////////////////////////////////////////////
    //
    //  スコア関連
    //
    //////////////////////////////////////////////////////

    public function scoreCreate(REQUEST $request){

        $statusData = DB::table('statuses')
                    ->where('connect_id','=',$request->connect_id)
                    ->where('route_code','=',$request->route_code)
                    ->first();

        $table = new score;
        DB::beginTransaction();
        try{
            $table->connect_id = $request->connect_id;
            $table->route_code = $request->route_code;
            $table->name=$request->name;
            $table->text=$request->text;
            $table->started_at = $statusData->started_at;
            $table->compleated_at = Carbon::now();
            $table->save();
            DB::commit();

        }catch(Esception $exception){
            DB::RollBack();
            throw $exception;
            return $this->_error(1);
        }
        //クリアしたからステータス・スタンプを削除して、まっさらにする
        DB::table('statuses')
        ->where('connect_id','=',$request->connect_id)
        ->where('route_code','=',$request->route_code)
        ->delete();
        return $this->_success();
    }

}

<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\api\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Storage;

//　取扱各モデル
use App\Models\outuser; //ユーザ
use App\Models\route;   //ルート
use App\Models\point;   //ルートのポイント
use App\Models\goal;    //ルートのゴール
use App\Models\score;   //スコア

use Carbon\Carbon;      //時間用

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class createController extends BaseController
{

    //画像をＡＷＳに保存
    //ラリー・ポイント・ゴールともに作成段階で使用します
    //画像保存　こちらで保存したPATHを返す
    public function createPict(REQUEST $request){
        $image=$request->file('pict');
        $responce=Storage::disk('s3')->put('/pict',$image,'public');
        return $this->_success(['path'=>$responce]);
    }


    //アプリ側でユーザ登録した際に呼び出し
    //個人を特定するID（UUID）を作成し、アプリ側に返す。
    //user_idとe-mailが同一であれば、connect_idを返す形にする
    //まだ危険なのでつけない、一旦放置
    public function createUser(REQUEST $request){
/*         //user_id Email同一である場合の処置
        $tempUser=DB::table('users')
            ->where('email','=',$request->email)
            ->where('user_id','=',$request->user_id)
            ->first();
        //もし、同じuser_idかつEmailであれば存在するconnect_idを返す
        if($tempUser !== null){
            return $this->_success(['connect_id'=>$tempUser->connect_id]);
        } */

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


    //ポイントをテーブルで返す NowTravelで使用 2022 02-02 無駄を修正 テスト済
    //引数  connect_id
    //      route_code
    //statusがなければ自動的に作成　スタンプラリーを開始する
    //スタートの時間はとるが、現状では使わない（時間が短いほどいい　をなくすため）
    public function routePoints(REQUEST $request){
        $data = array();//返し値用テーブル
        $pointNum =0;//ポイント数を０で初期化
        //要求しているユーザとルートのコードで作成されているポイント全てを取得
        $routeTable = DB::table('points')
                    ->where('connect_id','=',$request->connect_id)
                    ->where('route_code','=',$request->route_code)
                    ->get();

        //もしデータがなければ ない時用のデータで返却
        if($routeTable==false){
            $data=-1;
            $pointNum=0;
        }else{
            //routeの全てのポイントデータを$dataへ
            foreach($routeTable as $route){
                array_push($data,array(
                    'point_no'=>$route->point_no,
                    'latitude'=>$route->latitude,
                    'longitude'=>$route->longitude,
                    'pict'=>$route->pict,
                    'text'=>$route->text
                ));
            }
            //ポイント数をいれる
            $pointNum=$routeTable->count();
        }

        return $this->_success(['table'=>$data,'pointNum'=>$pointNum]);
    }



    ////////////////////////////////////////////////////////////
    //
    //  ラリー作成
    //  画像の登録はアプリ側で別途よばれる画像保存用のAPIで処理
    //
    ////////////////////////////////////////////////////////////
    //ルート登録（ここから point goal を作成していきます)
    //route_codeを返します
    //引数にmodeが必要
    //mode 0 で通常の一括作成
    //mode 1 でnowTravel作成
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
            //通常作成とnowTravel分岐
            //０であればnowTravel それ以外なら通常作成
            if($request->mode != 0){
                //nowTravel
                $table->published=2;//２にして、作成中は他の人には見えない、また、現在nowTravelで作成中とする
            }else{
                //通常作成
                $table->published=-1;//-1にして作成中は他の人から見えないようにする
            }
            //point_total_numを０で初期化
            $table->point_total_num = 0;
            $table->save();
            DB::commit();

        }catch(Esception $exception){
            DB::RollBack();
            throw $exception;
            return $this->_error(1);
        }
        //ルートコードにして返却
        return $this->_success(['route_code'=>$table->route_code]);
    }
    //ポイント登録
    //connect_id と route_code で誰のどのルートであるか判断
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
        //ルートコードとポイントNOで返す
        return $this->_success(['route_code'=>$table->route_code,'point_no'=>$table->point_no]);
    }
    //ゴール登録
    //routeにポイント数を保存後、ゴールを登録
    //登録したroute_codeを返却する
    public function goalCreate(REQUEST $request){
        $table = new goal;
        DB::beginTransaction();
        try{


            //ポイントを数えて、routeへいれる
            $point_total_num=DB::table('points')
            ->where('route_code','=',$request->route_code)
            ->count();
            //routeをfirst()で取得どのルートかを取得
            $routeTable = route::where('connect_id','=',$request->connect_id)
                                ->where('route_code','=',$request->route_code)
                                ->first();
            //ポイント数を入れる
            $routeTable->point_total_num = $point_total_num;


            //公開状態にする 元がなんであれ公開にする
            $routeTable->published = 0;
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
        //ルートコードを返す
        return $this->_success(['route_code'=>$table->route_code]);
    }


    // ルートの削除
    //Connect_IDとroute_IDを受け取り、ルートを削除する
    public function routeDelete(REQUEST $request){
        //まずはS3に保存されている全ての画像を削除
        //ルートの画像を削除
        $routeData=DB::table('routes')
            ->where('connect_id','=',$request->connect_id)
            ->where('route_code','=',$request->route_code)
            ->first();
        if(isset($routeData->pict)){
            Storage::disk('s3')->delete($routeData->pict);
        }
        //ゴールの画像を削除
        $goalData=DB::table('goals')
            ->where('connect_id','=',$request->connect_id)
            ->where('route_code','=',$request->route_code)
            ->first();
        if(isset($goalData->pict)){
            Storage::disk('s3')->delete($goalData->pict);
        }
        //point全ての画像を削除
        $pointData=DB::table('points')
                ->where('connect_id','=',$request->connect_id)
                ->where('route_code','=',$request->route_code)
                ->get();
        foreach($pointData as $temp){
            if(isset($temp->pict)){
                Storage::disk('s3')->delete($temp->pict);
            }
        }

        //ルートから連なるデータ削除
        //外部制約より、ルートを削除するだけでＤＢが自動的に削除します
        DB::table('routes')
            ->where('connect_id','=',$request->connect_id)
            ->where('route_code','=',$request->route_code)
            ->delete();
        return $this->_success();

    }




    //////////////////////////////////////////////////////
    //
    //  nowTravel   随時ポイント登録タイプのルート作成
    //
    //////////////////////////////////////////////////////

    //作成中のものがあるかどうかを返す
    // connect_id が必要
    public function reasonNowTravelRoute(REQUEST $request){
        //変数初期化
        $reason=false;  //存在フラグ
        $pointNum = 0;  //ポイント数
        $route_code=""; //ルートコード
        $route_name=""; //ルートの名前


        //routesからnowTravelで作成中のものを探す publishedが２のもの
        $now=DB::table('routes')
            ->where('connect_id','=',$request->connect_id)
            ->where('published','=',2)
            ->first();

        //作成中ルートがなければ
        if($now == null){
            $reason=false;
            $pointNum=0;
            $route_code="";
            $route_name="";
        }else{  //存在していれば
            $reason=true;
            $pointNum=DB::table('points')
                ->where('connect_id','=',$request->connect_id)
                ->where('route_code','=',$now->route_code)
                ->count();
            $route_code=$now->route_code;
            $route_name=$now->route_name;
        }

        //reason：存在しているか
        //あればroute_codeと、ポイント数を返す
        //なければreason：false route_codeは""
        return $this->_success([
            'reason'=>$reason,
            'route_code'=>$route_code,
            'route_name'=>$route_name,
            'pointNum'=>$pointNum,
        ]);
    }






    //////////////////////////////////////////////////////
    //
    //  スコア関連
    //
    //////////////////////////////////////////////////////

    //ゴールした時 スコアを作成して取得スタンプをクリアする
    public function scoreCreate(REQUEST $request){
        //変数初期化
        $name="";   //名前
        $text="";   //コメント

        //クリアしたユーザのステータスを検出
        //開始時間をとるため
        $statusData = DB::table('statuses')
                    ->where('connect_id','=',$request->connect_id)
                    ->where('route_code','=',$request->route_code)
                    ->first();

        //名前・コメントがなければ
        //名前を john does コメントをno coment でそれぞれ初期化する
        if($request->name!=NULL){
            $name = $request->name;
        }else{
            $name = "John Does";
        }
        if($request->text!=NULL){
            $text = $request->text;
        }else{
            $text = "NO COMMENT";
        }

        //スコア作成
        $table = new score;
        DB::beginTransaction();
        try{
            $table->connect_id = $request->connect_id;
            $table->route_code = $request->route_code;
            $table->name=$name;
            $table->text=$text;
            $table->started_at = $statusData->started_at;
            $table->compleated_at = Carbon::now();
            $table->save();
            DB::commit();

        }catch(Esception $exception){
            DB::RollBack();
            throw $exception;
            return $this->_error(1);
        }

        //クリア後は、取得したポイントを削除
        DB::table('statuses')
        ->where('connect_id','=',$request->connect_id)
        ->where('route_code','=',$request->route_code)
        ->delete();
        return $this->_success();
    }

}

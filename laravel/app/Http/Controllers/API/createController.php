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

/*     //スタート登録
    public function startCreate(REQUEST $request){
        $table = new start;
        DB::beginTransaction();
        try{
            $table->connect_id  = $request->connect_id;
            $table->route_code  = $request->route_code;
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
        return $this->_success(['route_code'=>$table->route_code]);
    } */
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
            dump($table);
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
/*         //スタートテーブルで設定されている画像を削除
        $startData=DB::table('starts')
            ->where('connect_id','=',$request->connect_id)
            ->where('route_code','=',$request->route_code)
            ->first();
        if($startData->pict != NULL){
            Storage::disk('s3')->delete($startData->pict);
        } */
        //ルートから連なるデータ削除
        $routeData = DB::table('routes')
        ->where('connect_id','=',$request->connect_id)
        ->where('route_code','=',$request->route_code)
        ->delete();
        return $this->_success();

    }


    //ヒュベニ
    //メートルで産出
    private function reDistance($lat1,$lng1,$lat2,$lng2){
    //ヒュベニ
/*         $GRS80_A = 6371008;
        $GRS80_E2 = 0.00669438002301188;
        $GRS80_MNUM = 6356752.314245;

        $mu_y = deg2rad($lat1 + $lat2)/2;
        $W = sqrt(1-$GRS80_E2*pow(sin($mu_y),2));
        $W3 = $W*$W*$W;
        $M = $GRS80_MNUM/$W3;
        $N = $GRS80_A/$W;
        $dx = deg2rad($lng1 - $lng2);
        $dy = deg2rad($lat1 - $lat2);

        // 距離をmで算出
        $dist = sqrt(pow($dy*$M,2) + pow($dx*$N*cos($mu_y),2));
 */


//ヒュベニ２
/*     $radLat1 = $lat1 * M_PI / 180.0; // 緯度１
    $radLon1 = $lon1 * M_PI / 180.0; // 経度１
    $radLat2 = $lat2 * M_PI / 180.0; // 緯度２
    $radLon2 = $lon2 * M_PI / 180.0; // 経度２

    // 平均緯度
    $radLatAve = ($radLat1 + $radLat2) / 2.0;

    // 緯度差
    $radLatDiff = abs($radLat1 - $radLat2);

    // 経度差算
    $radLonDiff = abs($radLon1 - $radLon2);

    $sinLat = sin($radLatAve);
    $mode=true;
    if( $mode) {
    // $mode引数がtrueなら世界測地系で計算（デフォルト）
    $temp = 1.0 - 0.00669438 * ($sinLat*$sinLat);
    $meridianRad = 6356752.314245 / sqrt($temp*$temp*$temp); // 子午線曲率半径
    $dvrad = 6378137.0 / sqrt($temp); // 卯酉線曲率半径
    } else {
    // $mode引数がfalseなら日本測地系で計算
    $temp = 1.0 - 0.00667478 * ($sinLat*$sinLat);
    $meridianRad = 6334834.0 / sqrt($temp*$temp*$temp); // 子午線曲率半径
    $dvrad = 6377397.155 / sqrt($temp); // 卯酉線曲率半径
    }

    $t1 = $meridianRad * $radLatDiff;
    $t2 = $dvrad * Cos($radLatAve) * $radLonDiff;
    $dist = sqrt(($t1*$t1) + ($t2*$t2));
 */

    //測地線航海算法
    // 緯度経度をラジアンに変換
    $radLat1 = deg2rad($lat1); // 緯度１
    $radLon1 = deg2rad($lng1); // 経度１
    $radLat2 = deg2rad($lat2); // 緯度２
    $radLon2 = deg2rad($lng2); // 経度２

    $A = 6378137.0; // 赤道半径
    $B = 6356752.314140356; // 極半径
    // $F = ($A - $B) / $A;
    $F = 0.003352858356825; // 扁平率

    $BdivA = 0.99664714164317; // $B/$A
    $P1 = atan($BdivA * tan($radLat1));
    $P2 = atan($BdivA * tan($radLat2));

    $sd = acos(sin($P1)*sin($P2) + cos($P1) * cos($P2) * cos($radLon1 - $radLon2));

    $cos_sd = cos($sd/2);
    $sin_sd = sin($sd/2);
    $c = (sin($sd) - $sd) * pow(sin($P1)+sin($P2),2) / $cos_sd / $cos_sd;
    $s = (sin($sd) + $sd) * pow(sin($P1)-sin($P2),2) / $sin_sd / $sin_sd;
    $delta = $F / 8.0 * ($c - $s);

    return $A * ($sd + $delta);

    //球面三角法
    // 緯度経度をラジアンに変換
/*     $radLat1 = deg2rad($lat1); // 緯度１
    $radLon1 = deg2rad($lon1); // 経度１
    $radLat2 = deg2rad($lat2); // 緯度２
    $radLon2 = deg2rad($lon2); // 経度２

    $r = 6378137.0; // 赤道半径

    $averageLat = ($radLat1 - $radLat2) / 2;
    $averageLon = ($radLon1 - $radLon2) / 2;
    return $r * 2 * asin(sqrt(pow(sin($averageLat), 2) + cos($radLat1) * cos($radLat2) * pow(sin($averageLon), 2)));
 */
    }







}

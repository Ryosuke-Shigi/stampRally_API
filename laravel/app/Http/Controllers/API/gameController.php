<?php

/*gitの大文字小文字勝手に変換をけした
git config core.ignorecase false
*/

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\api\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Storage;

//　取扱各モデル
use App\Models\outuser;
use App\Models\route;
use App\Models\point;
use App\Models\goal;
use App\Models\status;
use App\Models\score;
use App\Models\stamp;

use Carbon\Carbon;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class gameController extends BaseController
{

    //指定コースのポイント数をカウントして返す
    //connect_idとroute_codeが必要
    public function Point_total_num(REQUEST $request){
        //もしroute_codeがなければエラー
        if(!$request->has('route_code')){
                return $this->_error(1);
        }
        //ポイント数を数える
        $point_total_num=DB::table('points')
            ->where('route_code','=',$request->route_code)
            ->count();
        return $this->_success(['point_total_num'=>$point_total_num]);
    }




    //自身が作成したルートを返す（デリート選択に使用）
    public function myRoutes(REQUEST $request){
        $table = DB::table('routes')
                ->where('connect_id','=',$request->connect_id)
                ->latest('updated_at')
                ->get();
        return $this->_success(['table'=>$table]);
    }

    //全てのルートを返す（新規ラリー選択画面用）
    //publishedが０（公開）のものを返す これを利用して　ゴールまで完成していなかったら publishedはー１のまま　とかで使えるように
    //新しいものから順番に
    public function allRoutes(REQUEST $request){
        $table = DB::table('routes')
                ->where('published','=',0)
                ->latest('updated_at')
                ->get();
        return $this->_success(['table'=>$table]);
    }

    //キーワードで検索したルートを返す
    public function keySearchRoutes(REQUEST $request){

        $table = DB::table('routes')
                    ->where('keyword','LIKE','%'.$request->keyword.'%')
                    ->where('published','=',0)
                    ->latest()->get();
        return $this->_success(['table'=>$table]);
    }

    //進行中のルートを返す 引数はconnect_id 降順
    public function progressRoutes(REQUEST $request){
        //まず進行中データ（status）を取得する
        $data = array();
        $status = DB::table('statuses')
                    ->where('connect_id','=',$request->connect_id)
                    ->latest('started_at')->get();
        //進行中ルート分、まわす
        foreach($status as $temp){
            //ステータスから進行中ルートのIDをとり、そのルートのrouteデータを抽出
            $route = DB::table('routes')
                    ->where('route_code','=',$temp->route_code)
                    ->first();
            //そのテーブルデータを$dataに追加する
            array_push($data,$route);
        }
        return  $this->_success(['table'=>$data]);
    }

    //ユーザの全てのスコアデータを降順で返す 引数はconnect_id
    public function showScore(REQUEST $request){
        //変数初期化
        $data = array();//テーブルデータ返し値用

        //ユーザのスコアデータを全て取得する
        $table = DB::table('scores')
                    ->where('connect_id','=',$request->connect_id)
                    ->latest('compleated_at')->get();

        //取得したユーザのスコアデータ分、繰り返す
        //もし、そのルートコードが存在しない（ルートが削除されている場合）
        //削除済コースという名前で返す
        foreach($table as $temp){
            //変数初期化
            $route_name="";//ルート名
            $addData=array();//テーブルデータ追加用配列

            //ルートデータを取得
            $route = DB::table('routes')
                    ->where('route_code','=',$temp->route_code)
                    ->first();

            //ルートが削除されていれば、削除済みコースとして返す
            if($route == NULL){
                $route_name="削除済コース";
            }else{
                $route_name=$route->route_name;
            }

            //テーブルデータをまとめて返すため、一度配列にまとめる
            //この時、削除済みであれば名前を削除済コースにしている
            $addData+=array(
                'route_name'=>$route_name,
                'name'=>$temp->name,
                'text'=>$temp->text,
                'started_at'=>$temp->started_at,
                'compleated_at'=>$temp->compleated_at,
            );
            array_push($data,$addData);
        }
        return $this->_success(['table'=>$data]);
    }

    //各コースのスコアを、降順で返す
    public function showRouteScore(REQUEST $request){
        //変数初期化
        $data = array();//返し値用配列

        //コースのスコアデータを取得 降順
        $table = DB::table('scores')
                    ->where('route_code','=',$request->route_code)
                    ->latest('compleated_at')->get();

        //取得したデータで削除済みのルートがないか検出
        foreach($table as $temp){
            $route_name="";
            $route = DB::table('routes')
                    ->where('route_code','=',$temp->route_code)
                    ->first();
            //route_codeから名前をとれなかったら 削除済コード
            if($route == NULL){
                $route_name="削除済コース";
            }else{
                $route_name=$route->route_name;
            }
            $addData=array();
            $addData+=array(
                'route_name'=>$route_name,
                'name'=>$temp->name,
                'text'=>$temp->text,
                'started_at'=>$temp->started_at,
                'compleated_at'=>$temp->compleated_at,
            );
            array_push($data,$addData);
        }
        return $this->_success(['table'=>$data]);
    }



    //ゴールのデータを返す
    //引数 route_code
    public function callGoal(REQUEST $request){
        $table = DB::table('goals')
                    ->where('route_code',"=",$request->route_code)
                    ->first();
        return $this->_success(['table'=>$table]);
    }



    //ポイントをテーブルで返す
    //引数  connect_id
    //      route_code
    //statusがなければ自動的に作成　スタンプラリーを開始する
    //スタートの時間はとるが、現状では使わない（時間が短いほどいい　をなくすため）
    public function callPoints(REQUEST $request){
        $data = array();

        //ルートのポイントデータを全て取得
        $routeTable = DB::table('points')
                    ->where('route_code','=',$request->route_code)
                    ->get();
        //ユーザのルートへの状態を取得
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
                $status->started_at=Carbon::now();
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





    /*

            ポイントチェック
            クリックしたポイントをチェックして　その結果を表示する
            $requestで送られてくるもの
            connect_id
            point_no        ポイントNO
            route_code      ルートコード
            latitude        緯度
            longitude       経度
            nowTime         押した時間


            送られてきた座標が
            route_codeとpoint_noで取り出すポイントと近ければ
            connect_id route_code point_noを使って
            stampテーブルにレコードを追加（ポイントをチェック）
            result->true remainPoint->残りポイント数　を返す
            離れていれば
            result->false remainPoint->残りポイント数で返す

    */
    public function pointJudge(REQUEST $request){
        //返し値初期化
        $result = 0;
        $remainPoint = 0;
        //TEST
        //とりあえず来た　connect_id route_code point_noを使って
        //stampテーブルにレコードをつっこむ
        //距離関係なしでOKをだす

        //ポイント数取得
        $pointNum=DB::table('points')
            ->where('route_code','=',$request->route_code)
            ->count();

        //比較ポイントを取得する
        //緯度経度を取得して
        //比較・判断させるため
        $pointTable=DB::table('points')
            ->where('route_code','=',$request->route_code)
            ->where('point_no','=',$request->point_no)
            ->first();

        //距離をM単位で返す はず
        $distance = $this->reDistance($request->latitude,
                                        $request->longitude,
                                        $pointTable->latitude,
                                        $pointTable->longitude);

        //距離判断
        if($distance <= 50){
            //stampレコードを作成する
            $result = 0;
            $table = new stamp;
            DB::beginTransaction();
            try{
                //stampテーブルのレコードを作成
                $table->connect_id  = $request->connect_id;
                $table->route_code  = $request->route_code;
                $table->point_no    = $request->point_no;
                $table->save();
                DB::commit();

            }catch(Esception $exception){
                DB::RollBack();
                throw $exception;
                return $this->_error(1);
            }
        }else{//距離外であれば
            $result=-1;
        }
        //処理後、stampの数を数える
        $stampNum=DB::table('stamps')
            ->where('connect_id','=',$request->connect_id)
            ->where('route_code','=',$request->route_code)
            ->count();
        //残りポイント数を返す
        $remainPoint = $pointNum - $stampNum;

        return $this->_success(['result'=>$result,'remainPoint'=>$remainPoint]);
    }


    //残りポイント数を返す（ルート選択後の判断をするためのもの）
    //途中で処理を分断された時の対応用
    // connect_idとroute_codeが必要
    public function remainPoint(REQUEST $request){
        //ポイント数取得
        $pointNum=DB::table('points')
            ->where('route_code','=',$request->route_code)
            ->count();

        $stampNum=DB::table('stamps')
        ->where('connect_id','=',$request->connect_id)
        ->where('route_code','=',$request->route_code)
        ->count();
        //残りポイント数を返す
        $remainPoint = $pointNum - $stampNum;
        return $this->_success(['result'=>0,'remainPoint'=>$remainPoint]);
    }


    //二点の経度緯度で距離をメートルで返す
    //測地線航海算法で算出（２０２２　１　９）
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


        //測地線航海算法（２０２２年１月１１日　適応中）
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

<?php

use Illuminate\Http\Request;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

//ルート作成・削除
Route::group(['prefix'=>'/route','as'=>'route.'],function(){
    //スタートポイント登録
    Route::post('/create','api\createController@routeCreate')->name('create');
    //スタートポイント削除
    Route::get('/delete','api\createController@routeDelete')->name('delete');

    //ゲーム部分
    //ルート選択
    //全てのルートの値を返す
    Route::post('/allRoutes','api\gameController@allRoutes')->name('allRoutes');
    //keywordで検索したルートを返す
    Route::post('/keySearchRoutes','api\gameController@keySearchRoutes')->name('keySearchRoutes');
    //進行中データを返す
    Route::post('/progressRoutes','api\gameController@progressRoutes')->name('progressRoutes');
});
//ポイント作成・削除
Route::group(['prefix'=>'/point','as'=>'point.'],function(){
    //ポイント登録
    Route::post('/create','api\createController@pointCreate')->name('create');
    //ポイント削除
    Route::get('/delete','api\createController@pointDelete')->name('delete');
    //point_total_num カウント
    Route::get('/point_total_num','api\gameController@point_total_num')->name('point_total_num');
    //チェックポイントを返す
    Route::post('/callPoints','api\gameController@callPoints')->name('callPoints');
});
//ゴール作成・削除
Route::group(['prefix'=>'/goal','as'=>'goal.'],function(){
    //ゴール設定
    Route::post('/create','api\createController@goalCreate')->name('create');
    //ゴール削除（必要かどうか）
    Route::get('/delete','api\createController@goalDelete')->name('delete');

    //ゴールのデータを返す　route_code　が必要
    Route::post('/callGoal','api\gameController@callGoal')->name('callGoal');
});
//スコアの作成・返却
Route::group(['prefix'=>'/score','as'=>'score.'],function(){
    //ゴール設定
    Route::post('/create','api\createController@scoreCreate')->name('create');
    //スコア表示用データ
    Route::post('/showScore','api\gameController@showScore')->name('showScore');
    //各ルートスコア表示用データ
    Route::post('/showRouteScore','api\gameController@showRouteScore')->name('showRouteScore');


});

//stamp関連
Route::group(['prefix'=>'/game','as'=>'game.'],function(){

    //pointをチェックする　条件を満たしていればstampレコードを作成する
    Route::post('/pointJudge','api\gameController@pointJudge')->name('pointJudge');

});




//ユーザを作成して、コンタクト用 connect_id の値を返す
Route::post('/createUser','api\createController@createUser')->name('createUser');
//画像を保存して、PATHを返す
Route::post('/createPict','api\createController@createPict')->name('createPict');

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

});
/* //スタート作成・削除(２０２１　１　１現在　削除はルート削除だけでいい)
Route::group(['prefix'=>'/start','as'=>'start.'],function(){
    //スタート登録
    Route::post('/create','api\createController@startCreate')->name('create');
    //スタート削除
    Route::get('/delete','api\createController@startDelete')->name('delete');
}); */
//ポイント作成・削除
Route::group(['prefix'=>'/point','as'=>'start.'],function(){
    //ポイント登録
    Route::post('/create','api\createController@pointCreate')->name('create');
    //ポイント削除
    Route::get('/delete','api\createController@pointDelete')->name('delete');
    //point_total_num カウント
    Route::get('/point_total_num','api\gameController@point_total_num')->name('point_total_num');

    //全てのポイントを返す（ほぼテスト用）
    Route::post('/allPoints','api\gameController@allPoints')->name('allPoints');

});
//ゴール作成・削除
Route::group(['prefix'=>'/goal','as'=>'start.'],function(){
    //ゴール設定
    Route::post('/create','api\createController@goalCreate')->name('create');
    //ゴール削除（必要かどうか）
    Route::get('/delete','api\createController@goalDelete')->name('delete');
});

//ユーザを作成して、コンタクト用 connect_id の値を返す
Route::post('/createUser','api\createController@createUser')->name('createUser');
//画像を保存して、PATHを返す
Route::post('/createPict','api\createController@createPict')->name('createPict');

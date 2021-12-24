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

Route::group(['prefix'=>'/start','as'=>'start.'],function(){
    //スタートポイント登録
    Route::post('/create','api\createController@startCreate')->name('create');
    //スタートポイント削除
    Route::get('/delete','api\createController@startDelete')->name('delete');

});

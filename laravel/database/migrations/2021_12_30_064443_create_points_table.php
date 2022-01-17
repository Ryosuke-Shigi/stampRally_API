<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePointsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('points', function (Blueprint $table) {
            $table->Increments('id');

            //外部接続用ID
            $table->uuid('connect_id')->comment('外部APIアクセス用ID');
            //ルートのコード
            $table->string('route_code',191)->nullable(false);
            //緯度　１２．３４５６７８９　まで保存できる　誤差１ｃｍ
            $table->double('latitude',9,7)->nullable(false);
            //経度　１２３．４５６７８９Ａ　まで保存できる　誤差１ｃｍ
            $table->double('longitude',10,7)->nullable(false);
            //pointナンバー
            $table->integer('point_no')->nullable(false);
            //画像
            $table->string("pict")->nullable(true);
            //紹介文
            $table->string("text")->nullable(true);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['route_code','point_no']);

            //outusersと外部制約
            $table->foreign('connect_id')->references('connect_id')->on('outusers')->onDelete('cascade')->onUpdate('cascade');
            //routesと外部制約
            $table->foreign('route_code')->references('route_code')->on('routes')->onDelete('cascade')->onUpdate('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('points');
    }
}

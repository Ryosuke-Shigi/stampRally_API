<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     *
     *
     *
     *
     */

     /* 存在していれば　ルート進行中　開始時間を保存 */
     /* リセットする際、これを削除するだけで関連するポイントも消せる */
    public function up()
    {
        Schema::create('statuses', function (Blueprint $table) {
            $table->Increments('id');

            //外部接続用ID
            $table->uuid('connect_id')->comment('外部APIアクセス用ID');
            //ルートのコード
            $table->string('route_code')->nullable(false);
            //開始日時が登録される
            $table->datetime('started_at')->nullable()->change();

            $table->softDeletes();
            $table->timestamps();

            //同じコースで作成はできないようにする
            $table->unique(['route_code']);

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
        Schema::dropIfExists('statuses');
    }
}

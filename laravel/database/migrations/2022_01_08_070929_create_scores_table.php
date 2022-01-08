<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    // 攻略情報　名前（攻略後に自分でつける）　コメント（攻略後につける）
    // ユーザが消えても残るが　ルートが消えると削除される
    public function up()
    {
        Schema::create('scores', function (Blueprint $table) {
            $table->Increments('id');

            //外部接続用ID(これを参照して個人を特定できる)
            $table->uuid('connect_id')->comment('外部APIアクセス用ID');
            //ルートのコード
            $table->string('route_code')->nullable(false);
            //クリア者　名前
            $table->string('name')->nullable(true);
            //クリア者コメント
            $table->string('text')->nullable(true);
            //開始日時
            $table->datetime('started_at')->nullable()->change();
            //ラリー終了日時
            $table->datetime('compleated_at')->nullable()->change();


            $table->softDeletes();
            $table->timestamps();

            //routesと外部制約　ルートが消えるとスコアも消える
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
        Schema::dropIfExists('scores');
    }
}

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
            $table->string('route_code',191)->nullable(false);
            //クリア者　名前
            $table->string('name')->nullable(true);
            //クリア者コメント
            $table->string('text')->nullable(true);
            //開始日時
            $table->datetime('started_at')->nullable(false);
            //ラリー終了日時
            $table->datetime('compleated_at')->nullable(false);


            $table->softDeletes();
            $table->timestamps();

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

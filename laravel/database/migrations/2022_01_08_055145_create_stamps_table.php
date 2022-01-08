<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStampsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stamps', function (Blueprint $table) {
            $table->Increments('id');

            //外部接続用ID
            $table->uuid('connect_id')->comment('外部APIアクセス用ID');
            //ルートのコード
            $table->string('route_code')->nullable(false);
            //取得したポイントのナンバー
            $table->integer('point_no')->nullable(false);


            $table->softDeletes();
            $table->timestamps();

            //outusersと外部制約
            $table->foreign('connect_id')->references('connect_id')->on('outusers')->onDelete('cascade')->onUpdate('cascade');
            //statusesと外部制約
            $table->foreign('route_code')->references('route_code')->on('statuses')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stamps');
    }
}

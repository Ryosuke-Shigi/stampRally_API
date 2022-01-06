<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGoalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('goals', function (Blueprint $table) {
            $table->Increments('id');

            //外部接続用ID
            $table->uuid('connect_id')->comment('外部APIアクセス用ID');
            //ルートのコード
            $table->string('route_code')->nullable(false);
            //画像
            $table->string("pict")->nullable(true);
            //紹介文
            $table->string("text")->nullable(true);
            $table->softDeletes();
            $table->timestamps();

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
        Schema::dropIfExists('goals');
    }
}

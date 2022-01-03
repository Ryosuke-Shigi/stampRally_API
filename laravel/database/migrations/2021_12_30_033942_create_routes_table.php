<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoutesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('routes', function (Blueprint $table) {
            $table->Increments('id');

            //外部接続用ID
            $table->uuid('connect_id')->comment('外部APIアクセス用ID');
            //ルートのコード
            $table->string('route_code')->nullable(false);
            //名前　stringはvarchar(255)に自動変換される
            $table->string('route_name')->nullable(false);
            //pointのトータル数
            $table->integer('point_total_num')->default(0);
/*             //ルートの種類
            $table->integer('kind')->default(0);
            //公開・非公開
            $table->integer('published')->default(0); */
            //deleted_atが追加される
            $table->softDeletes();
            $table->timestamps();


            //route_codeはunique
            //routesテーブルのみ
            $table->unique('route_code');
            //outusersと外部制約
            $table->foreign('connect_id')->references('connect_id')->on('outusers')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('routes');
    }
}

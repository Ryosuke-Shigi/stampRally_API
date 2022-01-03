<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOutusersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('outusers', function (Blueprint $table) {
            $table->Increments('id');
            //UUID（Universally Unique Identifier）を使って一意のキーを作成する
            $table->uuid('connect_id')->unique()->comment('外部APIアクセス用ID');
            $table->string('user_id');
            $table->string('email');
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
        Schema::dropIfExists('outusers');
    }
}

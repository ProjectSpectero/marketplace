<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTablePartialAuth extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('partial_auth', function (Blueprint $table) {
            $table->increments('id');
            $table->string('user_id');
            $table->string('two_factor_token');
            $table->mediumText('data');
            $table->timestamp('expires');
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
        Schema::dropIfExists('partial_auth');
    }
}

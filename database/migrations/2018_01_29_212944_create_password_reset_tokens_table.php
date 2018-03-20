<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePasswordResetTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('password_reset_tokens', function (Blueprint $table)
        {
            $table->increments('id');
            $table->string('token');
            $table->integer('user_id');
            $table->string('ip');
            $table->timestamp('expires');

            $table->unique('token', 'unique_token_index');
            $table->index('user_id', 'user_id_index');
            
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
        Schema::dropIfExists('password_reset_tokens');
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table)
        {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('status'); // Do not set without the constants array
            $table->string('node_key');
            $table->string('password');

            $table->decimal('credit', 13, 4)
                ->default(0);

            $table->string('credit_currency')
                ->default(\App\Constants\Currency::USD); // Set with the Currency constant, defaults to USD.

            $table->softDeletes();
            $table->timestamps();

            $table->index('node_key', 'node_key_index');
            $table->index([ 'email', 'status' ], 'email_status_index'); // Passport query
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}

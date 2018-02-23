<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('services', function (Blueprint $table)
        {
            $table->increments('id');

            $table->integer('node_id');
            $table->index('node_id', 'node_id_index');

            $table->string('type');

            $table->json('config');

            $table->json('access_reference');
            $table->mediumText('access_config');
            $table->string('access_credentials');

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
        Schema::dropIfExists('services');
    }
}

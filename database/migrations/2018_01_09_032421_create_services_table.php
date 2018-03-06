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

            // One node may only have one instance of a specific service type
            $table->unique([ 'node_id', 'type' ], 'node_id_type_index');

            $table->json('config');
            $table->json('connection_resource');

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

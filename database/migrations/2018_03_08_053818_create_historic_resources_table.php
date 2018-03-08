<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHistoricResourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('historic_resources', function (Blueprint $table)
        {
            $table->increments('id');

            $table->integer('user_id') // Only set if possible, don't count on it always being there.
                ->nullable();

            $table->string('model'); // Class (model) name of the resource

            $table->json('resource'); // JSON representation of the FULLY expanded model (with ALL relations).

            $table->timestamps(); // When did it get created?
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('historic_resources');
    }
}

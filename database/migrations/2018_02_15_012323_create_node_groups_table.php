<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNodeGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('node_groups', function (Blueprint $table)
        {
            $table->increments('id');
            $table->string('friendly_name');
            $table->string('status');
            $table->integer('user_id');
            $table->string('market_model'); // Do not set this without the constants array
            $table->decimal('price', 13, 4);
            $table->timestamps();

            $table->index('user_id', 'user_id_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('node_groups');
    }
}

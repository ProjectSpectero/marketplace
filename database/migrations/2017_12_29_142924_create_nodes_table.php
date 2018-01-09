<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('nodes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ip');
            $table->integer('port');
            $table->string('protocol');
            $table->string('access_token');
            $table->integer('install_id');
            $table->boolean('active');
            $table->integer('user_id');
            $table->string('market_model'); // Do not set this without the constants array
            $table->timestamps();
            $table->softDeletes();

            $table->unique([ 'ip', 'port' ], "unique_ip_port_index");
            $table->unique('install_id', 'unique_install_id_index');
            $table->unique([ 'access_token', 'install_id' ], 'unique_token_install_id_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('nodes');
    }
}

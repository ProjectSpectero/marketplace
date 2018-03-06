<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateServiceIpAddressTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('node_ip_addresses', function (Blueprint $table)
        {
            $table->increments('id');
            $table->string('ip');
            $table->integer('node_id');
            $table->unique(['ip', 'node_id'], 'unique_ip_node_id_index');
            $table->index('ip', 'ip_index');
            $table->index('node_id', 'node_id_index');
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
        Schema::dropIfExists('service_ip_address');
    }
}

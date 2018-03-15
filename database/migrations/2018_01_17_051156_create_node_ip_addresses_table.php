<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNodeIpAddressesTable extends Migration
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
            $table->string('city');
            $table->string('cc');
            $table->integer('asn');
            $table->integer('node_id');

            $table->unique('ip', 'unique_ip_index');

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
        Schema::dropIfExists('node_ip_addresses');
    }
}

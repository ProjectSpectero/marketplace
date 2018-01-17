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
        Schema::create('service_ip_address', function (Blueprint $table)
        {
            $table->increments('id');
            $table->string('ip');
            $table->string('type');
            $table->integer('service_id');
            $table->unique(['ip', 'service_id'], 'unique_ip_service_id_index');
            $table->index('ip', 'ip_index');
            $table->index('service_id', 'service_id_index');
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

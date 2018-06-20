<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableEnterpriseResources extends Migration
{
    private $table = "enterprise_resources";

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->table, function (Blueprint $table)
        {
            $table->increments('id');

            $table->integer('ip_id');
            $table->integer('port');

            $table->integer('order_line_item_id');

            // Not really used as it is now, for future expansion.
            $table->string('outgoing_ip_id');


            $table->index('ip_id', 'ip_id_index');
            $table->index('order_line_item_id', 'order_line_item_id_index');
            $table->index('outgoing_ip_id');

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
        Schema::dropIfExists($this->table);
    }
}

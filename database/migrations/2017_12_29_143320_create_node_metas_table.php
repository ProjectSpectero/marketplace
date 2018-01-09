<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNodeMetasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('node_metas', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('node_id');
            $table->string('meta_key');
            $table->string('value_type');
            $table->string('meta_value');
            $table->unique(['node_id', 'meta_key'], 'nodeid_metakey_unique_index');
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
        Schema::dropIfExists('node_metas');
    }
}

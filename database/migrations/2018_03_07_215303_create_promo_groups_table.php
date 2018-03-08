<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromoGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promo_groups', function (Blueprint $table)
        {
            $table->increments('id');
            $table->string('name');
            $table->integer('usage_limit'); // This is per user. interpretation: An user may only use this many codes belonging to this group on their account. Idea is to prevent things like first time joining bonus codes from being abused.
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
        Schema::dropIfExists('promo_groups');
    }
}

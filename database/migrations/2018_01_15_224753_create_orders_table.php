<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table)
        {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('status');
            $table->string('subscription_reference');
            $table->string('subscription_provider'); // Do not set this without the PaymentProcessor class
            $table->timestamps();

            $table->unique([ 'subscription_reference', 'subscription_provider' ], 'unique_reference_provider_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}

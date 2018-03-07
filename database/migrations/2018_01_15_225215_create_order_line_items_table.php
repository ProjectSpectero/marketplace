<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderLineItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_line_items', function (Blueprint $table)
        {
            $table->increments('id');
            $table->string('description', 512);
            $table->integer('order_id');
            $table->string('type');
            $table->string('resource');
            $table->integer('quantity');

            $table->string('status'); // Set this with the OrderStatus constants

            $table->string('sync_status'); // Set this with the OrderSyncStatus constants

            $table->timestamp('sync_timestamp')
                ->nullable();

            $table->decimal('amount', 13, 4);
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
        Schema::dropIfExists('order_line_items');
    }
}

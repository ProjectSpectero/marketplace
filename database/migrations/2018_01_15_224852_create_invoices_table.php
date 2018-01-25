<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table)
        {
            $table->increments('id');
            $table->integer('order_id');
            $table->integer('user_id'); // Can be derived from order_id, but this allows for { orderless invoices | authorization based on user }
            $table->decimal('amount', 13, 4);
            $table->decimal('tax', 13, 4);

            $table->string('currency')
                ->default(\App\Constants\Currency::USD);

            $table->string('status');
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
        Schema::dropIfExists('invoices');
    }
}

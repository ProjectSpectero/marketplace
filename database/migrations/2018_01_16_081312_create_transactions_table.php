<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('invoice_id');
            $table->string('payment_processor');
            $table->string('reference');
            $table->string('type');
            $table->string('payment_type');
            $table->decimal('amount', 13, 4);
            $table->decimal('fee', 13, 4);
            $table->string('currency');
            $table->timestamps();

            $table->unique('reference', 'unique_transaction_reference_index');
            $table->index('invoice_id', 'invoice_id_index');
            $table->index('payment_processor', 'payment_processor_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}

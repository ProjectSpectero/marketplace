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
        Schema::create('transactions', function (Blueprint $table)
        {
            $table->increments('id');

            $table->integer('user_id')
                ->default(0); // To allow for processing invoiceless payments if ever needed. Use 0 to indicate a payment without an user

            $table->integer('invoice_id')
                ->default(0); // To allow for processing invoiceless payments if ever needed.

            $table->string('payment_processor');
            $table->string('reference');
            $table->string('type');
            $table->string('payment_type');
            $table->decimal('amount', 13, 4);

            $table->decimal('fee', 13, 4)
                ->default(0); // Set to 0 if not given

            $table->string('currency')
                ->default(\App\Constants\Currency::USD);

            $table->timestamps();

            $table->unique([ 'reference', 'payment_processor' ], 'unique_reference_provider_index');
            $table->index('invoice_id', 'invoice_id_index');
            $table->index('payment_processor', 'payment_processor_index');
            $table->index('user_id', 'user_id_index');
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

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromoCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code');
            $table->integer('group_id');

            $table->integer('usage_limit'); // Code may be used this many times across all users, it's decremented once per activation.

            $table->timestamp('expires'); // Activation will be denied if attempted after this date.

            $table->boolean('enabled') // Allows for disabling a specific code
                ->default(true);

            $table->decimal('amount', 13, 4); // The amount the code credits into an user's account on activation, internal values ALWAYS denominated in USD.
            $table->timestamps();

            $table->unique('code', 'promo_code_unique_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('promo_codes');
    }
}

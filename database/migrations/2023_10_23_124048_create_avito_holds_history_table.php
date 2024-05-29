<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAvitoHoldsHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('avito_holds_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('avito_order_id')->constrained();
            $table->foreignId('old_order_id')->constrained('orders');
            $table->foreignId('new_order_id')->constrained('orders');
            $table->integer('hold')->default(0);
            $table->tinyInteger('type')->default(1);
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
        Schema::dropIfExists('avito_holds_histories');
    }
}

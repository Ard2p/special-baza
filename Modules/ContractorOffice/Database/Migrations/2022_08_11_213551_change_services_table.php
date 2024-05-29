<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('custom_services', function (Blueprint $table) {
            $table->unsignedInteger('unit_id')->nullable();
            $table->foreign('unit_id')
                ->references('id')
                ->on('units')
                ->onDelete('set null');

            $table->unsignedBigInteger('price_cashless')->nullable();
            $table->unsignedBigInteger('price_cashless_vat')->nullable();
            $table->unsignedBigInteger('marketplace_markup')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}

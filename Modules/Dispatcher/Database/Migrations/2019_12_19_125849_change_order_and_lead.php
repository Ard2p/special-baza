<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeOrderAndLead extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['date_to',  'shifts_count', 'regional_representative_commission', 'regional_representative_id']);
        });

        Schema::table('vehicles_order', function (Blueprint $table) {
            $table->enum('order_type', ['hour', 'shift'])->default('shift');
            $table->unsignedInteger('order_duration')->default(1);
            $table->unsignedInteger('regional_representative_commission')->default(0);
            $table->unsignedInteger('regional_representative_id')->nullable();
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

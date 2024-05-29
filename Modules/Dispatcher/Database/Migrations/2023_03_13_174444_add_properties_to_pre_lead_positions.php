<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPropertiesToPreLeadPositions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table((new \Modules\Dispatcher\Entities\PreLeadPosition())->getTable(), function (Blueprint $table) {
            $table->date('date_from')->nullable();
            $table->time('time_from')->nullable();
            $table->string('order_type')->nullable();
            $table->unsignedInteger('order_duration')->nullable();
        });

        Schema::table((new \Modules\Dispatcher\Entities\PreLead())->getTable(), function (Blueprint $table) {
            $table->string('object_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('', function (Blueprint $table) {

        });
    }
}

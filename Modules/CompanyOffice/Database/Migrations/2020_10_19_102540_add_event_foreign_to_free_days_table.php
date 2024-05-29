<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEventForeignToFreeDaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('free_days', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_task_id')->nullable();
        });
        Schema::table('free_days', function (Blueprint $table) {

            $table->foreign('employee_task_id')
                ->references('id')
                ->on('employee_tasks')
                ->onDelete('cascade');
        });

        Schema::table('dispatcher_leads', function (Blueprint $table) {

            $table->string('source')->nullable();

        });

        Schema::table('orders', function (Blueprint $table) {

            $table->string('source')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('free_days', function (Blueprint $table) {

        });
    }
}

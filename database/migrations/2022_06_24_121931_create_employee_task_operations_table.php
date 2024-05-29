<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeTaskOperationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_task_operations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('employee_task_id');
            $table->tinyInteger('old_status')->nullable();
            $table->tinyInteger('new_status')->nullable();
            $table->unsignedInteger('created_by_id');
            $table->timestamps();

            $table->foreign('employee_task_id')->references('id')->on('employee_tasks')->onDelete('CASCADE');
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_task_operations');
    }
}

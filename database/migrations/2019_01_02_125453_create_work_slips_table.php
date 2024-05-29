<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWorkSlipsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('work_slips', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('hours')->default(0);
            $table->integer('user_id')->default(0);
            $table->integer('task_id')->default(0);
            $table->integer('work_type')->default(0);
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
        Schema::dropIfExists('work_slips');
    }
}

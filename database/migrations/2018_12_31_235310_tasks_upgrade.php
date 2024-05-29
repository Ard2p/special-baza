<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TasksUpgrade extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('task_manager', function (Blueprint $table) {
          $table->integer('priority')->default(1);
          $table->integer('role')->default(0);
          $table->integer('system_module_id')->default(0);
          $table->integer('system_function_id')->default(0);
          $table->string('name')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('task_manager', function (Blueprint $table) {
            $table->dropColumn([
                'priority',
                'role',
                'system_module_id',
                'system_function_id',
                'name',
            ]);
        });
    }
}

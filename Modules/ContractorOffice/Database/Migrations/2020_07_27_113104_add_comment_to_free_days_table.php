<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCommentToFreeDaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('free_days', function (Blueprint $table) {
            $table->text('comment')->nullable();
            $table->unsignedInteger('creator_id')->nullable();
        });

        Schema::table('free_days', function (Blueprint $table) {

            $table->foreign('creator_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
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

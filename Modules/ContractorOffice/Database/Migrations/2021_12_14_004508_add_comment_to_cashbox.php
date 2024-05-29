<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCommentToCashbox extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('company_cash_registers', function (Blueprint $table) {
            $table->unsignedBigInteger('machinery_base_id')->nullable();
            $table->text('comment')->nullable();
            $table->foreign('machinery_base_id')
                ->references('id')
                ->on('machinery_bases')
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
        Schema::table('', function (Blueprint $table) {

        });
    }
}

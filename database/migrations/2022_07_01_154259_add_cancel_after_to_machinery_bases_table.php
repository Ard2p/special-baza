<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCancelAfterToMachineryBasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('machinery_bases', function (Blueprint $table) {
            $table->integer('cancel_after')->nullable();
            $table->integer('payment_percent')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('machinery_bases', function (Blueprint $table) {
            $table->dropColumn('cancel_after');
            $table->dropColumn('payment_percent');
        });
    }
}

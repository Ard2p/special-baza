<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFormatToScoringsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('scorings', function (Blueprint $table) {
            $table->string('format')->default('Cache');
            $table->boolean('found')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('scorings', function (Blueprint $table) {
            $table->dropColumn('format');
            $table->dropColumn('found');
        });
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNativeCityRegion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('native_region_id')->default(0);
            $table->integer('native_city_id')->default(0);
        });

        Schema::table('proposals', function (Blueprint $table) {
            $table->integer('city_id')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('native_region_id');
            $table->dropColumn('native_city_id');
        });

        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn('city_id');
        });
    }
}

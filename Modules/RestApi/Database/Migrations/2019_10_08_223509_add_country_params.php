<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCountryParams extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('site_feedback', function (Blueprint $table) {
            $table->unsignedInteger('country_id')->default(1);
        });

        Schema::table('rp_contacts', function (Blueprint $table) {
            $table->unsignedInteger('country_id')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('site_feedback', function (Blueprint $table) {

        });
    }
}

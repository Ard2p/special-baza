<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndividualFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('individual_requisites', function (Blueprint $table) {
            $table->string('signatory_name')->nullable();
            $table->string('signatory_short')->nullable();
            $table->string('signatory_genitive')->nullable();
            $table->string('signatory_position')->nullable();
            $table->string('full_name')->nullable();
            $table->string('short_name')->nullable();
            $table->string('bank')->nullable()->change();
            $table->string('bik')->nullable()->change();
            $table->string('ks')->nullable()->change();
            $table->string('rs')->nullable()->change();
        });

        Schema::table('entity_requisites', function (Blueprint $table) {
            $table->string('charter')->nullable();
            $table->string('director_position')->nullable();
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

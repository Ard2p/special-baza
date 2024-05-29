<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddValuesToOptionalFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('optional_attributes', function (Blueprint $table) {
             $table->unsignedInteger('interval')->default(1);
             $table->integer('min')->default(1);
             $table->integer('max')->default(50);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('optional_attributes', function (Blueprint $table) {

        });
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAvitoId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table((new \App\Machinery)->getTable(), function (Blueprint $table) {
            $table->unsignedBigInteger('avito_id')->nullable()->index();
        });
        Schema::table((new \App\User)->getTable(), function (Blueprint $table) {
            $table->boolean('sms_notify')->default(false);
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

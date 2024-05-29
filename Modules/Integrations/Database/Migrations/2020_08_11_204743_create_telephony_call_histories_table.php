<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTelephonyCallHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('telephony_call_histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('phone');

            $table->text('link')->nullable();

            $table->string('owner_type');
            $table->unsignedBigInteger('owner_id');

            $table->string('call_id')->nullable();
            $table->string('status')->nullable();

            $table->text('raw_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('telpehony\_telephony_call_histories');
    }
}

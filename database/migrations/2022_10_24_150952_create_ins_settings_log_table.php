<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInsSettingsLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ins_setting_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ins_setting_id')->constrained();
            $table->foreignId('ins_tariff_id')->constrained();
            $table->timestamp('date_from');
            $table->timestamp('date_to');
            $table->double('price')->default(0);
            $table->tinyInteger('type');
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
        Schema::dropIfExists('ins_setting_logs');
    }
}

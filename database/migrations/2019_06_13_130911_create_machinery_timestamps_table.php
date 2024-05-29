<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMachineryTimestampsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('machinery_timestamps', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('machinery_id');
            $table->unsignedInteger('proposal_id');
            $table->unsignedInteger('step');
            $table->text('coordinates')->nullable();
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
        Schema::dropIfExists('machinery_timestamps');
    }
}

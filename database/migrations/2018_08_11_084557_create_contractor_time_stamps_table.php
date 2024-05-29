<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContractorTimestampsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contractor_timestamps', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('winner_steps')->default(0);
            $table->integer('proposal_id')->default(0);
            $table->timestamp('machinery_ready')->nullable();
            $table->timestamp('machinery_on_site')->nullable();
            $table->timestamp('end_of_work')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contractor_time_stamps');
    }
}

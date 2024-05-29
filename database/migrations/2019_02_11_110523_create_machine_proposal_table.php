<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMachineProposalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
 /*       Schema::create('machine_proposal', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('machinery_id');
            $table->unsignedInteger('proposal_id');
            $table->integer('sum')->default(0);
            $table->longText('machinery_stamp')->nullable();
            $table->timestamps();
        });*/

        Schema::create('machine_offer', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('machinery_id');
            $table->unsignedInteger('offer_id');
            $table->integer('sum')->default(0);
            $table->longText('machinery_stamp')->nullable();
            $table->timestamps();
        });

        Schema::table('adverts', function (Blueprint $table) {
               $table->boolean('global_show')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('machine_proposal');
    }
}

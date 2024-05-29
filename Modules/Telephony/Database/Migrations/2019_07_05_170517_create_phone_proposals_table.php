<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePhoneProposalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('phone_proposals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('proposal_id');
            $table->unsignedInteger('user_id');
            $table->string('phone');
            $table->timestamps();
        });

        Schema::table('phone_proposals', function (Blueprint $table) {
            $table->foreign('proposal_id')
                ->references('id')
                ->on('proposals')
                ->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('phone_proposals');
    }
}

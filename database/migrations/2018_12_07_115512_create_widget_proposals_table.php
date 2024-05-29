<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWidgetProposalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('widget_proposals', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('proposal_id');
            $table->string('name')->nullable();
            $table->string('promo')->nullable();
            $table->integer('widget_id');
            $table->boolean('new_user')->default(0);
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
        Schema::dropIfExists('widget_proposals');
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddModelToLeadPositionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lead_positions', function (Blueprint $table) {
            $table->unsignedBigInteger('machinery_model_id')->nullable();
        });

        Schema::table('lead_positions', function (Blueprint $table) {

            $table->foreign('machinery_model_id')
                ->references('id')
                ->on('machinery_models')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lead_positions', function (Blueprint $table) {

        });
    }
}

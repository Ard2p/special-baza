<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddProposalFireAttribute extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->integer('is_fire')->default(0);
        });
        Schema::table('machineries', function (Blueprint $table) {
            $table->integer('regional_representative_id')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn('is_fire');
        });
        Schema::table('machineries', function (Blueprint $table) {
            $table->dropColumn('regional_representative_id');
        });
    }
}

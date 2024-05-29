<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ClearProposalSystem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('proposals', function (Blueprint $table){
           $table->dropColumn('type');
            $table->dropColumn('brand_id');
           $table->dropColumn('machine_id');
        });
        Schema::table('proposal_need_type', function (Blueprint $table){
            $table->increments('id');
            $table->unsignedInteger('type_id');
            $table->unsignedInteger('proposal_id');
            $table->unsignedInteger('brand_id')->default(0);
        });

        Schema::table('offers', function (Blueprint $table){
            $table->dropColumn('machine_id');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('proposals', function (Blueprint $table){
            $table->integer('type');
            $table->integer('brand_id')->defaut(0);
        });
        Schema::dropIfExists('proposal_need_type');

        Schema::table('offers', function (Blueprint $table){
            $table->integer('machine_id');

        });
    }
}

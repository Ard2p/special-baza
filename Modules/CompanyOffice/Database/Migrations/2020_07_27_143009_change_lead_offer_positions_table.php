<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeLeadOfferPositionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lead_offer_positions', function (Blueprint $table){
            $table->dropForeign(['user_id']);
            $table->unsignedInteger('user_id')->nullable()->change();
        });

       Schema::table('lead_offer_positions', function (Blueprint $table) {

           $table->renameColumn('user_id', 'creator_id');
           $table->unsignedBigInteger('company_branch_id');
       });

        Schema::table('lead_offer_positions', function (Blueprint $table) {

            $table->foreign('creator_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            $table->foreign('company_branch_id')
                ->references('id')
                ->on('company_branches')
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
        //
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeLeadOfferTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lead_offers', function (Blueprint $table){
            $table->dropForeign(['user_id']);
        });
        Schema::rename('lead_offers','dispatcher_lead_offers');

        DB::table('dispatcher_lead_offers')->delete();

        Schema::table('dispatcher_lead_offers', function (Blueprint $table){


            $table->renameColumn('user_id', 'creator_id');
            $table->unsignedBigInteger('company_branch_id');

        });
        Schema::table('dispatcher_lead_offers', function (Blueprint $table){
            $table->unsignedInteger('creator_id')->nullable()->change();
        });

        Schema::table('dispatcher_lead_offers', function (Blueprint $table) {

            $table->foreign('creator_id')
                ->references('id')
                ->on('users')
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
        //
    }
}

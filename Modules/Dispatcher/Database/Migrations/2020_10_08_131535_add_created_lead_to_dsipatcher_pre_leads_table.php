<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCreatedLeadToDsipatcherPreLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dispatcher_pre_leads', function (Blueprint $table) {
            $table->unsignedBigInteger('lead_id')->nullable();
        });

        Schema::table('dispatcher_pre_leads_positions', function (Blueprint $table) {
            $table->unsignedInteger('brand_id')->nullable();
        });


        Schema::table('dispatcher_pre_leads_positions', function (Blueprint $table) {

            $table->foreign('brand_id')
                ->references('id')
                ->on('brands')
                ->onDelete('set null');
        });

        Schema::table('dispatcher_pre_leads', function (Blueprint $table) {

            $table->foreign('lead_id')
                ->references('id')
                ->on('dispatcher_leads')
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
        Schema::table('dsipatcher_pre_leads', function (Blueprint $table) {

        });
    }
}

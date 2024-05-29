<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeDispatcherPreLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::getDoctrineSchemaManager()
            ->getDatabasePlatform()
            ->registerDoctrineTypeMapping('point', 'string');

        Schema::table('dispatcher_pre_leads', function (Blueprint $table) {
            $table->string('contact_person')->nullable()->change();
        });

        Schema::create('dispatcher_pre_leads_attributes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('pre_lead_position_id');
            $table->unsignedInteger('optional_attribute_id');
            $table->string('value')->nullable();

        });

        Schema::table('dispatcher_pre_leads_attributes', function (Blueprint $table) {

            $table->foreign('pre_lead_position_id')
                ->references('id')
                ->on('dispatcher_pre_leads_positions')
                ->onDelete('cascade');

            $table->foreign('optional_attribute_id')
                ->references('id')
                ->on('optional_attributes')
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

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAmoLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('amo_leads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('amo_id');
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->jsonb('data');
            $table->string('status')->default(\Modules\Integrations\Entities\Amo\AmoLead::STATUS_UNPROCESSED);
            $table->timestamps();
        });

        Schema::table('amo_leads', function (Blueprint $table) {
            $table->foreign('lead_id')
                ->references('id')
                ->on('dispatcher_leads')
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
        Schema::dropIfExists('amo_leads');
    }
}

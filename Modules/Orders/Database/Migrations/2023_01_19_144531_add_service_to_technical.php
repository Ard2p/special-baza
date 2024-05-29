<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddServiceToTechnical extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table((new \Modules\ContractorOffice\Entities\Vehicle\TechnicalWork())->getTable(), function (Blueprint $table) {
            $table->unsignedBigInteger('service_center_id')->nullable();
            $table->foreign('service_center_id')
                ->references('id')
                ->on('service_centers')
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
        Schema::table('', function (Blueprint $table) {

        });
    }
}

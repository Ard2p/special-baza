<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateServiceCenterCustomServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('service_center_custom_services', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('custom_service_id');
            $table->unsignedBigInteger('service_center_id');
            $table->unsignedBigInteger('price');
            $table->unsignedInteger('count');
        });

        Schema::table('service_center_custom_services', function (Blueprint $table) {

            $table->foreign('custom_service_id')
                ->references('id')
                ->on((new \Modules\ContractorOffice\Entities\Services\CustomService())->getTable())
                ->onDelete('cascade');

            $table->foreign('service_center_id')
                ->references('id')
                ->on((new \Modules\Orders\Entities\Service\ServiceCenter())->getTable())
                ->onDelete('cascade');
        });

        Schema::table((new \Modules\ContractorOffice\Entities\Services\CustomService())->getTable(), function (Blueprint $table) {
            $table->boolean('is_for_service')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('service_center_custom_services');
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWithStampDocuments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table((new \Modules\CompanyOffice\Entities\Company\DocumentsPack())->getTable(), function (Blueprint $table) {
            $table->string('default_application_url_with_stamp')->nullable();
            $table->string('default_disagreement_url_with_stamp')->nullable();
            $table->string('default_return_act_url_with_stamp')->nullable();
            $table->string('default_acceptance_act_url_with_stamp')->nullable();
            $table->string('default_single_act_url_with_stamp')->nullable();
            $table->string('default_return_single_act_url_with_stamp')->nullable();
            $table->string('default_single_act_services_url_with_stamp')->nullable();
            $table->string('default_single_application_url_with_stamp')->nullable();
            $table->string('default_service_return_act_with_stamp')->nullable();
            $table->string('default_service_services_act_with_stamp')->nullable();
            $table->string('default_worker_result_url_with_stamp')->nullable();
            $table->string('default_service_act_url_with_stamp')->nullable();
            $table->string('default_set_application_url_with_stamp')->nullable();
            $table->string('default_set_act_url_with_stamp')->nullable();
            $table->string('default_return_set_act_url_with_stamp')->nullable();
            $table->string('default_upd_url_with_stamp')->nullable();
            $table->string('default_parts_sale_invoice_with_stamp')->nullable();
            $table->string('default_service_center_invoice_with_stamp')->nullable();
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

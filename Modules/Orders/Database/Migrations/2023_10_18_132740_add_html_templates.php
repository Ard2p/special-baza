<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\CompanyOffice\Entities\Company\DocumentsPack;

class AddHtmlTemplates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table((new DocumentsPack())->getTable(), function (Blueprint $table) {
            $table->longText('default_contract_url_html')->nullable();
            $table->longText('default_service_contract_url_html')->nullable();
            $table->longText('default_application_url_html')->nullable();
            $table->longText('default_disagreement_url_html')->nullable();
            $table->longText('default_return_act_url_html')->nullable();
            $table->longText('default_acceptance_act_url_html')->nullable();
            $table->longText('default_worker_result_url_html')->nullable();
            $table->longText('default_service_act_url_html')->nullable();
            $table->longText('default_single_act_url_html')->nullable();
            $table->longText('default_return_single_act_url_html')->nullable();
            $table->longText('default_single_act_services_url_html')->nullable();
            $table->longText('default_single_application_url_html')->nullable();
            $table->longText('default_service_services_act_html')->nullable();
            $table->longText('default_service_return_act_html')->nullable();
            $table->longText('default_set_application_url_html')->nullable();
            $table->longText('default_set_act_url_html')->nullable();
            $table->longText('default_return_set_act_url_html')->nullable();
            $table->longText('default_upd_url_html')->nullable();
            $table->longText('default_cash_order_html')->nullable();
            $table->longText('default_cash_order_stamp_html')->nullable();
            $table->longText('default_application_url_with_stamp_html')->nullable();
            $table->longText('default_disagreement_url_with_stamp_html')->nullable();
            $table->longText('default_return_act_url_with_stamp_html')->nullable();
            $table->longText('default_acceptance_act_url_with_stamp_html')->nullable();
            $table->longText('default_single_act_url_with_stamp_html')->nullable();
            $table->longText('default_return_single_act_url_with_stamp_html')->nullable();
            $table->longText('default_single_act_services_url_with_stamp_html')->nullable();
            $table->longText('default_single_application_url_with_stamp_html')->nullable();
            $table->longText('default_service_return_act_with_stamp_html')->nullable();
            $table->longText('default_service_services_act_with_stamp_html')->nullable();
            $table->longText('default_worker_result_url_with_stamp_html')->nullable();
            $table->longText('default_service_act_url_with_stamp_html')->nullable();
            $table->longText('default_set_application_url_with_stamp_html')->nullable();
            $table->longText('default_set_act_url_with_stamp_html')->nullable();
            $table->longText('default_return_set_act_url_with_stamp_html')->nullable();
            $table->longText('default_upd_url_with_stamp_html')->nullable();
            $table->longText('default_single_contract_url_html')->nullable();
            $table->longText('default_single_contract_url_with_stamp_html')->nullable();
            $table->longText('default_contract_url_with_stamp_html')->nullable();
            $table->longText('default_avito_return_act_html')->nullable();
            $table->longText('default_avito_upd_html')->nullable();
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

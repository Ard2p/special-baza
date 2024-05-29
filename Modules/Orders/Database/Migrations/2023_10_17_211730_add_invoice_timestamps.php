<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\CompanyOffice\Entities\Company\DocumentsPack;

class AddInvoiceTimestamps extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table((new DocumentsPack)->getTable(), function (Blueprint $table) {
            $table->longText('default_invoice_stamp_url_html')->nullable();
            $table->longText('default_invoice_url_html')->nullable();
            $table->longText('default_parts_sale_invoice_html')->nullable();
            $table->longText('default_service_center_invoice_html')->nullable();
            $table->longText('default_parts_sale_invoice_with_stamp_html')->nullable();
            $table->longText('default_service_center_invoice_with_stamp_html')->nullable();
            $table->longText('default_invoice_contract_url_html')->nullable();
            $table->longText('default_invoice_contract_url_with_stamp_html')->nullable();
            $table->longText('default_pledge_invoice_html')->nullable();
            $table->longText('default_avito_invoice_url_html')->nullable();
            $table->longText('default_pledge_invoice_with_stamp_html')->nullable();
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

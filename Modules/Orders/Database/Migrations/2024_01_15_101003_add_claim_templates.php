<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\CompanyOffice\Entities\Company\CompanyBranchSettings;
use Modules\CompanyOffice\Entities\Company\DocumentsPack;

class AddClaimTemplates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table((new DocumentsPack())->getTable(), function (Blueprint $table) {
            $table->string('default_order_claims_url')->nullable();
            $table->longText('default_order_claims_url_html')->nullable();
            $table->string('default_order_claims_url_with_stamp')->nullable();
            $table->longText('default_order_claims_url_with_stamp_html')->nullable();
            $table->string('default_service_claims_url')->nullable();
            $table->longText('default_service_claims_url_html')->nullable();
            $table->string('default_service_claims_url_with_stamp')->nullable();
            $table->longText('default_service_claims_url_with_stamp_html')->nullable();
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

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\ContractorOffice\Entities\Sets\MachinerySet;

class AddOrderSetFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->jsonb('set_prices')->nullable();
            $table->unsignedBigInteger('machinery_set_id')->nullable();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('machinery_set_id')->references('id')
                ->on((new MachinerySet())->getTable())
                ->onDelete('set null');
        });
        Schema::table('company_documents_packs', function (Blueprint $table) {
            $table->string('default_set_application_url')->nullable();
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

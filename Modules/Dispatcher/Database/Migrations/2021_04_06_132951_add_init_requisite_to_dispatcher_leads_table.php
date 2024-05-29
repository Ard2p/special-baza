<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddInitRequisiteToDispatcherLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('documents_pack_id')->nullable();
        });

        Schema::table('dispatcher_leads', function (Blueprint $table) {
            $table->unsignedBigInteger('documents_pack_id')->nullable();
            $table->string('contractor_requisite_type')->nullable();
            $table->unsignedBigInteger('contractor_requisite_id')->nullable();
        });
        Schema::table('dispatcher_pre_leads', function (Blueprint $table) {
            $table->unsignedBigInteger('documents_pack_id')->nullable();
            $table->string('contractor_requisite_type')->nullable();
            $table->unsignedBigInteger('contractor_requisite_id')->nullable();
        });

        Schema::table('orders', function (Blueprint $table) {

            $table->foreign('documents_pack_id')
                ->references('id')
                ->on('company_documents_packs')
                ->onDelete('set null');
        });
        Schema::table('dispatcher_leads', function (Blueprint $table) {

            $table->foreign('documents_pack_id')
                ->references('id')
                ->on('company_documents_packs')
                ->onDelete('set null');
        });

        Schema::table('dispatcher_pre_leads', function (Blueprint $table) {

            $table->foreign('documents_pack_id')
                ->references('id')
                ->on('company_documents_packs')
                ->onDelete('set null');
        });

        Schema::table('entity_requisites', function (Blueprint $table) {

            $table->string('vat_system')->nullable()->default('cashless_without_vat');
        });
        Schema::table('international_legal_details', function (Blueprint $table) {

            $table->string('vat_system')->nullable()->default('cashless_without_vat');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dispatcher_leads', function (Blueprint $table) {

        });
    }
}

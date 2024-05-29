<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAliasToContractorServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contractor_services', function (Blueprint $table) {
           $table->string('alias')->nullable();
        });

        Schema::table('attribute_contractor_services', function (Blueprint $table) {
            $table->renameColumn('service_optional_fields_id', 'service_optional_field_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contractor_services', function (Blueprint $table) {
            $table->dropColumn('alias');
        });
    }
}

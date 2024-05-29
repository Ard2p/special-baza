<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBalueAddedToServices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table((new \Modules\ContractorOffice\Entities\Services\CustomService())->getTable(), function (Blueprint $table) {
            $table->unsignedBigInteger('value_added')->default(0);
            $table->unsignedBigInteger('value_added_cashless')->default(0);
            $table->unsignedBigInteger('value_added_cashless_vat')->default(0);
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

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\ContractorOffice\Entities\System\TariffGrid;
use Modules\ContractorOffice\Entities\System\TariffUnitCompare;
use Modules\Dispatcher\Entities\LeadPosition;
use Modules\Orders\Entities\OrderComponent;

class AddMonthCondition extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table((new TariffUnitCompare)->getTable(), function (Blueprint $table) {
            $table->boolean('is_month')->default(false);
        });
        Schema::table((new LeadPosition)->getTable(), function (Blueprint $table) {
            $table->boolean('is_month')->default(false);
            $table->unsignedInteger('month_duration')->default(0);
        });

        Schema::table((new OrderComponent)->getTable(), function (Blueprint $table) {
            $table->boolean('is_month')->default(false);
            $table->unsignedInteger('month_duration')->default(0);
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

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLeadRejectReasonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lead_reject_reasons', function (Blueprint $table) {
            $table->string('key')->primary();
        });

        \Illuminate\Support\Facades\DB::table('lead_reject_reasons')->insert(
            [
                [
                    'key' => \App\Directories\LeadRejectReason::REASON_NO_MACHINERIES,
                ],
                [
                    'key' => \App\Directories\LeadRejectReason::REASON_NO_FREE_MACHINERIES,
                ],
                [
                    'key' => \App\Directories\LeadRejectReason::REASON_WRONG_REGION,
                ],
                [
                    'key' => \App\Directories\LeadRejectReason::REASON_OTHER,
                ],
            ]
        );

        Schema::table('dispatcher_pre_leads', function (Blueprint $table) {
            $table->string('reject_type')->nullable();

        });
        Schema::table('dispatcher_leads', function (Blueprint $table) {
            $table->string('reject_type')->nullable();
            $table->string('rejected')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lead_reject_reasons');
    }
}

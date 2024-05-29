<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeTelephonyCallHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('telephony_call_histories', function (Blueprint $table) {

            $table->unsignedBigInteger('company_id')->nullable();
            $table->boolean('important')->default(false);
            $table->boolean('listened')->default(false);

            $table->string('bind_type')->nullable();
            $table->unsignedBigInteger('bind_id')->nullable();
        });

        Schema::table('telephony_call_histories', function (Blueprint $table) {
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
        });
        Schema::table('telephony_megafon_accounts', function (Blueprint $table) {
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}

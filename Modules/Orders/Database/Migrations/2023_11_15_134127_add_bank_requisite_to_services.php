<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\Orders\Entities\Service\ServiceCenter;

class AddBankRequisiteToServices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table((new ServiceCenter())->getTable(), function (Blueprint $table) {
            $table->unsignedBigInteger('bank_requisite_id')->nullable();
            $table->foreign('bank_requisite_id')->references('id')
                ->on('bank_requisites')
                ->onDelete('set null');
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

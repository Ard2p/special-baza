<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\Orders\Entities\Service\ServiceCenter;

class ChangeServiceCenterTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::table((new ServiceCenter)->getTable(), function (Blueprint $table) {
           $table->string('status_tmp')->nullable();
           $table->string('address')->nullable();
           $table->string('address_type')->nullable();
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

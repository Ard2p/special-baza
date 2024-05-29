<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangePrincipalInOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::table((new \Modules\Orders\Entities\Order())->getTable(), function (Blueprint $table) {
          $table->dropForeign(['principal_id']);
       });
        Schema::table((new \Modules\Orders\Entities\Order())->getTable(), function (Blueprint $table) {
            $table->foreign('principal_id')->references('id')->on((new \App\User\PrincipalDoc())->getTable())
            ->nullOnDelete();
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

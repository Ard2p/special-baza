<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeValueAddedOwner extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_workers_value_added', function (Blueprint $table) {

            $table->dropForeign(['owner_id']);
        });
        Schema::table('order_workers_value_added', function (Blueprint $table) {

            $table->unsignedBigInteger('owner_id')->change();
        });
        foreach (\Modules\Orders\Entities\ValueAdded::all() as $item) {
           $br = \Modules\CompanyOffice\Entities\Company\CompanyBranch::query()->where('creator_id', $item->owner_id)->first();

           if($br) {
               $item->update(['owner_id' => $br->id]);
           }

        }
        Schema::table('order_workers_value_added', function (Blueprint $table) {

            $table->foreign('owner_id')
                ->references('id')
                ->on('company_branches')
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

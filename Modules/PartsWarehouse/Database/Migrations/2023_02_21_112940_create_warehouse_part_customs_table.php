<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\PartsWarehouse\Entities\Warehouse\Part;
use Modules\PartsWarehouse\Entities\Warehouse\PartCustom;

class CreateWarehousePartCustomsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create((new PartCustom())->getTable(), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('vendor_code')->nullable();
            $table->unsignedBigInteger('part_id');
              $table->unsignedBigInteger('company_branch_id');
            $table->timestamps();
        });

        Schema::table((new PartCustom())->getTable(), function (Blueprint $table) {
            $table->foreign('part_id')->references('id')->on((new Part)->getTable())->cascadeOnDelete();
               $table->foreign('company_branch_id')
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
        Schema::dropIfExists('warehouse\_part_customs');
    }
}

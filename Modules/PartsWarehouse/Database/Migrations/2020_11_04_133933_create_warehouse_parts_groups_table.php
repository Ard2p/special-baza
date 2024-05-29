<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWarehousePartsGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('warehouse_parts_groups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->jsonb('images')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->timestamps();
        });

        Schema::table('warehouse_parts_groups', function (Blueprint $table) {
            $table->foreign('parent_id')
                ->references('id')
                ->on('warehouse_parts_groups')
                ->onDelete('set null');
        });

        Schema::table('warehouse_parts', function (Blueprint $table) {

            $table->foreign('group_id')
                ->references('id')
                ->on('warehouse_parts_groups')
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
        Schema::dropIfExists('warehouse\_parts_groups');
    }
}

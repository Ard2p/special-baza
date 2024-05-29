<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeShopPartsRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shop_parts_requests', function (Blueprint $table) {

            $table->string('status')->default('open');
            $table->string('reject_type')->nullable();
            $table->unsignedInteger('creator_id')->nullable();
        });

        Schema::table('shop_parts_requests', function (Blueprint $table) {

            $table->foreign('creator_id')
                ->references('id')
                ->on('users')
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
        //
    }
}

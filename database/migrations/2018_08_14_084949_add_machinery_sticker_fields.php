<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMachineryStickerFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('machineries', function (Blueprint $table) {
            $table->integer('has_sticker')->default(0);
            $table->string('sticker')->nullable();
            $table->string('sticker_promo_code')->nullable();
            $table->string('who_glued_sticker')->nullable();
            $table->text('characteristic')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('machineries', function (Blueprint $table) {
            $table->dropColumn('has_sticker');
            $table->dropColumn('sticker');
            $table->dropColumn('sticker_promo_code');
            $table->dropColumn('who_glued_sticker');
            $table->dropColumn('characteristic');
        });
    }
}

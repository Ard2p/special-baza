<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeCardType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /*        DB::getDoctrineSchemaManager()
                    ->getDatabasePlatform()
                    ->registerDoctrineTypeMapping('enum', 'string');*/

        Schema::table('dispatcher_contractor_pays', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('dispatcher_contractor_pays', function (Blueprint $table) {
            $table->enum('type', ['cash', 'cashless'])->default('cash');
        });


        Schema::table('invoice_pays', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('invoice_pays', function (Blueprint $table) {
            $table->enum('type', ['cash', 'cashless'])->default('cash');
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

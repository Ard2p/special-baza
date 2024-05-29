<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddConcreteMixerTariff extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::getDoctrineSchemaManager()
            ->getDatabasePlatform()
            ->registerDoctrineTypeMapping('point', 'string');
        DB::getDoctrineSchemaManager()
            ->getDatabasePlatform()
            ->registerDoctrineTypeMapping('enum', 'string');

        Schema::table('machineries', function (Blueprint $table) {
              $table->string('min_order_type')->default('shift')->change();
        });

        \Modules\ContractorOffice\Entities\System\Tariff::query()->insert([
            [

                'type' => \Modules\ContractorOffice\Entities\System\Tariff::CONCRETE_MIXER,
            ],
        ]);

        Schema::table('order_workers', function (Blueprint $table) {
            $table->jsonb('params')->nullable();
        });

        Schema::table('orders_need_type', function (Blueprint $table) {
            $table->jsonb('params')->nullable();
        });

        Schema::table('lead_positions', function (Blueprint $table) {
            $table->jsonb('params')->nullable();
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

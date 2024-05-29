<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\PartsWarehouse\Entities\Shop\Parts\PartsSale;

class AddPartsSalesFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table((new PartsSale())->getTable(), function (Blueprint $table) {
            $table->string('title')->nullable();
            $table->string('external_id')->nullable();
            $table->unsignedBigInteger('internal_number')->nullable();
            $table->unsignedBigInteger('documents_pack_id')->nullable();
            $table->nullableMorphs('contractor_requisite', 'idx_contractor_requisite');
            $table->foreign('documents_pack_id')
                ->references('id')
                ->on('company_documents_packs')
                ->onDelete('set null');
           $table->unsignedBigInteger('base_id')->nullable();
            $table->foreign('base_id')
                ->references('id')
                ->on('machinery_bases')
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

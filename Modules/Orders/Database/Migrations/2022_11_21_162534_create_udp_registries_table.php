<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\Orders\Entities\OrderDocument;

class CreateUdpRegistriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('udp_registries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignIdFor(CompanyBranch::class)
                ->constrained((new CompanyBranch)->getTable())
                ->cascadeOnDelete();
            $table->unsignedInteger('internal_number')->nullable();
            $table->morphs('parent');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('udp_registries');
    }
}

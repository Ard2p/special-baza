<?php

use App\Machines\Type;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\Orders\Entities\Service\ServiceCenter;

class CreateCategoryServiceSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table((new Type)->getTable(), function (Blueprint $table) {
            $table->string('service_plan_type')->default('rent_days');
            $table->unsignedBigInteger('amount_between_services')->default(0);
            $table->unsignedBigInteger('service_duration')->default(60);
            $table->unsignedBigInteger('amount_days_between_plan_services')->default(0);
        });

        Schema::table((new ServiceCenter)->getTable(), function (Blueprint $table) {
            $table->boolean('is_plan')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('');
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\Dispatcher\Entities\Lead;

class AddObjectNameToLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table((new Lead())->getTable(), function (Blueprint $table) {
            $table->string('object_name')->nullable();
            $table->boolean('tender')->default(false);
            $table->timestamp('kp_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('leads', function (Blueprint $table) {

        });
    }
}

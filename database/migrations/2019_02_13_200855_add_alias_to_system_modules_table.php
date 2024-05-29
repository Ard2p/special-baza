<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAliasToSystemModulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('system_modules', function (Blueprint $table) {
            $table->string('alias')->nullable();
            $table->boolean('is_publish')->default(1);
        });
        Schema::table('system_functions', function (Blueprint $table) {
            $table->string('alias')->nullable();
            $table->boolean('is_publish')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('system_modules', function (Blueprint $table) {
            $table->dropColumn('is_publish');
            $table->dropColumn('alias');
        });
        Schema::table('system_functions', function (Blueprint $table) {
            $table->dropColumn('alias');
            $table->dropColumn('is_publish');
        });
    }
}

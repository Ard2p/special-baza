<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeDispatcher extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::disableForeignKeyConstraints();
        foreach (\App\Machinery::all() as $machine) {
            $machine->generateSeoPhoto();
        }
        Schema::table('dispatcher_customers', function (Blueprint $table) {
             $table->string('company_name')->nullable();
             $table->text('address')->nullable();
             $table->unsignedInteger('region_id')->nullable();
             $table->unsignedInteger('city_id')->nullable();
             $table->unsignedInteger('user_id');
        });

        Schema::table('dispatcher_leads', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id');
        });

        Schema::table('dispatcher_leads', function (Blueprint $table){
            $table->foreign('customer_id')
                ->references('id')
                ->on('dispatcher_customers')
                ->onDelete('cascade');
        });

        Schema::table('yandex_phone_credentials', function (Blueprint $table) {
            $table->boolean('enable')->default(1);
        });


        Schema::table('dispatcher_customers', function (Blueprint $table){
            $table->foreign('region_id')
                ->references('id')
                ->on('regions')
                ->onDelete('set null');

            $table->foreign('city_id')
                ->references('id')
                ->on('cities')
                ->onDelete('set null');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
        Schema::enableForeignKeyConstraints();
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

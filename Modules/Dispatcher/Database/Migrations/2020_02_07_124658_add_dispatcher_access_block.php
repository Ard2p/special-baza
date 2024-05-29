<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDispatcherAccessBlock extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        \Illuminate\Support\Facades\DB::table('access_blocks')
            ->insert([
                [
                    'name' => 'Диспетчер',
                    'alias' => 'dispatcher'
                ],
            ]);

        \App\Role::create([
            'name' => 'Диспетчер',
            'alias' => 'dispatcher',
            'can_delete' => false,
        ]);

        Schema::table('dispatcher_leads', function (Blueprint $table){
           $table->enum('publish_type', [
               'my_proposals',
               'all_contractors',
               'for_companies',
           ])->default('my_proposals');
           $table->unsignedInteger('region_id')->nullable();
        });

        Schema::table('dispatcher_leads', function (Blueprint $table){
            $table->foreign('region_id')
                ->references('id')
                ->on('regions')
                ->onDelete('set null');
        });




        Schema::table('dispatcher_customers', function (Blueprint $table) {
            $table->string('email')->nullable();
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

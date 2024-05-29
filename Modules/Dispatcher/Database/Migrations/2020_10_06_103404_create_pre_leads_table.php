<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePreLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dispatcher_pre_leads', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('name')->nullable();
            $table->string('status')->default('open');

            $table->string('contact_person');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            $table->string('address')->nullable();
            $table->point('coordinates')->nullable();

            $table->timestamp('date_from')->nullable();
            $table->unsignedInteger('order_duration')->nullable();
            $table->string('order_type')->nullable();


            $table->text('comment')->nullable();
            $table->text('rejected')->nullable();

            $table->unsignedBigInteger('customer_id')->nullable();

            $table->unsignedInteger('creator_id')->nullable();
            $table->unsignedBigInteger('company_branch_id');
            $table->timestamps();
        });

        Schema::create('dispatcher_pre_leads_positions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('pre_lead_id');
            $table->unsignedInteger('category_id');
            $table->unsignedBigInteger('model_id')->nullable();
            $table->unsignedInteger('machinery_id')->nullable();
            $table->unsignedInteger('count')->default(1);
            $table->string('comment')->nullable();
        });

        Schema::table('dispatcher_pre_leads', function (Blueprint $table) {

            $table->foreign('creator_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('company_branch_id')
                ->references('id')
                ->on('company_branches')
                ->onDelete('cascade');
        });

        Schema::table('dispatcher_pre_leads_positions', function (Blueprint $table) {

            $table->foreign('pre_lead_id')
                ->references('id')
                ->on('dispatcher_pre_leads')
                ->onDelete('cascade');

            $table->foreign('category_id')
                ->references('id')
                ->on('types')
                ->onDelete('cascade');

            $table->foreign('model_id')
                ->references('id')
                ->on('machinery_models')
                ->onDelete('set null');

            $table->foreign('machinery_id')
                ->references('id')
                ->on('machineries')
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
        Schema::dropIfExists('pre_leads');
    }
}

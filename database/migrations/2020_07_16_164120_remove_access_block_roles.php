<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveAccessBlockRoles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('access_blocks');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles_access_blocks');
        Schema::dropIfExists('adverts');
        Schema::dropIfExists('advert_agents');
        Schema::dropIfExists('advert_black_lists');
        Schema::dropIfExists('advert_categories');
        Schema::dropIfExists('advert_contacts');
        Schema::dropIfExists('advert_offers');
        Schema::dropIfExists('advert_send_email');
        Schema::dropIfExists('advert_send_sms');
        Schema::dropIfExists('advert_view_user');
        Schema::dropIfExists('auctions');
        Schema::dropIfExists('auction_offers');
        Schema::dropIfExists('contests');
        Schema::dropIfExists('contests_roles');
        Schema::dropIfExists('contests_user_relations');
        Schema::dropIfExists('contests_voting');
        Schema::dropIfExists('contest_guest_voting');
        Schema::dropIfExists('email_links');
        Schema::dropIfExists('email_lists');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('fines');
        Schema::dropIfExists('employee_requests');
        Schema::dropIfExists('menu_article');
        Schema::dropIfExists('menu_article_section');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('lead_contacts');
        Schema::dropIfExists('lead_notes');
        Schema::dropIfExists('lead_notifications');
        Schema::dropIfExists('lead_status_histories');
        Schema::dropIfExists('list_names');
        Schema::dropIfExists('list_name_email');
        Schema::dropIfExists('list_name_mailing');
        Schema::dropIfExists('list_name_phone');
        Schema::dropIfExists('mailing_lists');
        Schema::dropIfExists('mailing_search_filters');
        Schema::dropIfExists('participants');
        Schema::dropIfExists('rewards');
        Schema::dropIfExists('sale_offers');
        Schema::dropIfExists('search_filters');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('simple_proposals');
        Schema::dropIfExists('simple_proposal_locales');
        Schema::dropIfExists('test_table');
        Schema::dropIfExists('work_slips');
        Schema::dropIfExists('task_manager');
        Schema::dropIfExists('task_status_stamps');
        Schema::dropIfExists('crm_companies');
        Schema::dropIfExists('crm_company_requisites');
        Schema::dropIfExists('crm_contracts');
        Schema::dropIfExists('crm_contract_statuses');
        Schema::dropIfExists('crm_projects');
        Schema::dropIfExists('mailing_templates_roles');
        Schema::dropIfExists('knowledge_base_faq_roles');

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

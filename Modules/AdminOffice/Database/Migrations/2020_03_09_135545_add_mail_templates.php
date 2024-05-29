<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMailTemplates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $templates = [
            [
                'name' => 'Регистрация пользователя',
                'alias' => 'register_user'
            ],
            [
                'name' => 'Подтверждение email',
                'alias' => 'confirm_email'
            ],
            [
                'name' => 'Восстановление пароля',
                'alias' => 'restore_password'
            ],
            [
                'name' => 'Новая заявка',
                'alias' => 'new_proposal'
            ],
            [
                'name' => 'Вы создали заявку',
                'alias' => 'new_proposal_owner'
            ],
            [
                'name' => 'Новое предложение к заявке',
                'alias' => 'new_proposal_offer'
            ],
            [
                'name' => 'Новая заявка в регионе',
                'alias' => 'new_proposal_contractors'
            ],
            [
                'name' => 'Регситрация в системе',
                'alias' => 'new_user_welcome'
            ],
            [
                'name' => 'Новый пользователь',
                'alias' => 'new_user'
            ],
            [
                'name' => 'Новый пользователь в регионе',
                'alias' => 'new_user_manager'
            ],
            [
                'name' => 'Новая техника',
                'alias' => 'new_vehicle'
            ],
            [
                'name' => 'Новый заказ в системе',
                'alias' => 'new_order_admin'
            ],
            [
                'name' => 'Вы создали заказ',
                'alias' => 'new_order_customer'
            ],
            [
                'name' => 'Вам назначен заказ',
                'alias' => 'new_order_contractor'
            ],
            [
                'name' => 'Новый заказ в регионе',
                'alias' => 'new_order_manager'
            ],

            [
                'name' => 'Новый заказ в системе',
                'alias' => 'done_order_admin'
            ],
            [
                'name' => 'Вы создали заказ',
                'alias' => 'done_order_customer'
            ],
            [
                'name' => 'Вам назначен заказ',
                'alias' => 'done_order_contractor'
            ],
            [
                'name' => 'Новый заказ в регионе',
                'alias' => 'done_order_manager'
            ],

        ];
        $items = [];
        foreach (\Modules\RestApi\Entities\Domain::all() as $domain) {
            foreach ($templates as $template) {
                $items[] = [
                    'name' => $template['name'],
                    'text' => '',
                    'domain_id' => $domain->id,
                    'can_delete' => false,
                    'system_alias' => $template['alias'],
                    'type' => 'email',
                ];
            }

        }
        DB::table('mailing_templates')
            ->insert($items);
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

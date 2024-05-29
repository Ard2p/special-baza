<?php

namespace Modules\AdminOffice\Entities\Marketing\Mailing;

use Illuminate\Database\Eloquent\Model;
use Modules\RestApi\Entities\Domain;
use Spatie\Permission\Traits\HasRoles;

/**
 * Class Template
 * @package Modules\AdminOffice\Entities\Marketing\Mailing
 */
class Template extends Model
{

    use HasRoles;
    /**
     * @var string
     */
    protected $table = 'mailing_templates';

    /**
     * @var array
     */
   // protected $with = ['domain', 'roles'];

    /**
     * @var array
     */
    protected $appends = ['domain_name', 'domain_alias'];

    /**
     *Регистрация пользователя
     */
    const TYPE_REGISTER_USER = 'register_user';
    /**
     *Подтверждение email
     */
    const TYPE_CONFIRM_EMAIL = 'confirm_email';
    /**
     *Восстановление пароля
     */
    const TYPE_RESTORE_PASSWORD = 'restore_password';
    /**
     *Новая заявка
     */
    const TYPE_NEW_PROPOSAL = 'new_proposal';
    /**
     *Вы создали заявку
     */
    const TYPE_NEW_PROPOSAL_OWNER = 'new_proposal_owner';
    /**
     *Новое предложение к заявке
     */
    const TYPE_NEW_PROPOSAL_OFFER = 'new_proposal_offer';
    /**
     *Новая заявка в регионе
     */
    const TYPE_NEW_PROPOSAL_CONTRACTORS = 'new_proposal_contractors';
    /**
     *Регситрация в системе
     */
    const TYPE_NEW_USER_WELCOME = 'new_user_welcome';
    /**
     *Новый пользователь
     */
    const TYPE_NEW_USER = 'new_user';
    /**
     * Новый пользователь в регионе
     */
    const TYPE_USER_MANAGER = 'new_user_manager';
    /**
     *Новая техника
     */
    const TYPE_NEW_VEHICLE = 'new_vehicle';
    /**
     *Новый заказ в системе
     */
    const TYPE_NEW_ORDER_ADMIN = 'new_order_admin';
    /**
     *Вы создали заказ
     */
    const TYPE_NEW_ORDER_CUSTOMER = 'new_order_customer';
    /**
     *Вам назначен заказ
     */
    const TYPE_NEW_ORDER_CONTRACTOR = 'new_order_contractor';
    /**
     *Новый заказ в регионе
     */
    const TYP_NEW_ORDER_MANAGER = 'new_order_manager';
    /**
     * Заказ выполнен (Письмо администратору)
     */
    const TYPE_DONE_ORDER_ADMIN = 'done_order_admin';
    /**
     *Заказ выполнен (Письмо заказчику)
     */
    const TYPE_DONE_ORDER_CUSTOMER = 'done_order_customer';
    /**
     *Исполнитель выполнил заказ
     */
    const TYPE_DONE_ORDER_CONTRACTOR = 'done_order_contractor';
    /**
     *Выполнен заказ в регионе
     */
    const TYPE_DONE_ORDER_MANAGER = 'done_order_manager';

    /**
     *Выполнен заказ в регионе
     */
    const TYPE_SEND_ORDER_DOCUMENT = 'send_order_document';

    /**
     *Приглашение сотрудника
     */
    const TYPE_INVITE_EMPLOYEE = 'invite_employee';

    const TYPE_INVITE_CUSTOMER_USER = 'invite_customer_user';


    /**
     * @var array
     */
    protected $fillable = [
        'name',
        'text',
        'domain_id',
        'can_delete',
        'system_alias',
        'type'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function domain()
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * @return string
     */
    function getDomainNameAttribute()
    {
        return $this->domain ? $this->domain->url : '';
    }

    /**
     * @return string
     */
    function getDomainAliasAttribute()
    {
        return $this->domain ? $this->domain->alias : '';
    }

    /**
     * @param $data
     * @return $this
     */
    public function parse($data)
    {
        $parsed = preg_replace_callback('/{{(.*?)}}/', function ($matches) use ($data) {
            list($shortCode, $index) = $matches;

            if (isset($data[$index])) {
                return $data[$index];
            } else {
//                throw new Exception("Shortcode {$shortCode} not found in template id {$this->id}", 1);
            }

        }, $this->text);
        $this->text = $parsed;
        return $this;
    }

    function domainLink($path)
    {
        return  "https://{$this->domain->url}/{$path}";
    }

    static function getTemplate($alias, $domain_id)
    {

        $template = self::query()->whereSystemAlias($alias)->whereDomainId($domain_id)->whereType('email')->first();

        if(!$template) {
            $domain_id = Domain::where('alias', 'au')->firstOrFail()->id;
            $template = self::query()->whereSystemAlias($alias)->whereDomainId($domain_id)->whereType('email')->firstOrFail();
        }

        return $template;
    }



}
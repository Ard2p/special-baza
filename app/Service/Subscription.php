<?php

namespace App\Service;

use App\Finance\FinanceTransaction;
use App\Helpers\RequestHelper;
use App\Machinery;
use App\Machines\OptionalAttribute;


use App\Option;
use App\Seo\RequestContractor;
use App\Support\SmsNotification;
use App\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Modules\AdminOffice\Entities\Marketing\Mailing\Template;
use Modules\Dispatcher\Entities\Lead;
use Modules\Dispatcher\Entities\LeadOffer;
use Modules\Orders\Entities\Order;
use Modules\RestApi\Emails\DBMail;

/**
 * Class Subscription
 * @package App\Service
 */
class Subscription
{
    /**
     * @var string
     */
    protected $email;
    /**
     * @var \Illuminate\Database\Eloquent\Collection|static[]
     */
    protected $options;

    /**
     * Subscription constructor.
     */
    function __construct()
    {
        $this->options = Config::get('global_options') ?: Option::all();
        $this->email = $this->options->where('key', 'subscription_email')->first()->value ?? 'info@trans-baza.ru';
    }

    private function addDRYFields(Order $order, MailMessage $message, $showCommission = true, $contractor_id = null)
    {
        $commission = $order->system_commission / 100;
        $message->line('Категория техники: ');
        foreach ($contractor_id ? $order->vehicles->where('user_id', $contractor_id)->all() : $order->vehicles as $machine) {
            $type = $machine->pivot->order_type === 'shift' ? 'Смен' : 'Часов';
            $sum = humanSumFormat($machine->pivot->amount);
            $message->line($machine->name)
                ->line("Кол-во {$type}: {$machine->pivot->order_duration}")
                ->line("Стоимость: {$sum} " . ($showCommission ? "(Комиссия {$commission}%)" : ''));
        }


        $message->line("Адрес: {$order->address}")
            ->line("Дата: {$order->date_from->format('d.m.Y H:i')}");

        return $message;
    }

    private function addNewProposalDRYFields(Lead $proposal, MailMessage $message)
    {
        $message->line($proposal->comment);
        $message->line('Категория техники: ');
        ///    ->line($proposal->winner_offer->machine->name)
        foreach ($proposal->categories as $category) {
            $message->line("{$category->name} {$category->pivot->count} ед.");
        }
        $region = $proposal->region->name ?? '';
        $city = $proposal->city->name ?? '';
        $message->line("Адрес:  {$region},  {$city} {$proposal->address}")
            ->line("Дата: {$proposal->start_date}");
        return $message;
    }

    private function addMachineDRYFields(Machinery $machinery, MailMessage $message)
    {
        $message->line('Категория техники: ' . $machinery->type_name)
            ->line($machinery->name);

        foreach ($machinery->optional_attributes as $option) {

            $val = $option->pivot->value;
            $message->line("{$option->current_locale_name}: {$val}");

        }
        $message->line("Гос номер: {$machinery->number}")
            ->line("Стоимость в час: {$machinery->sum_hour_format}")
            ->line("Длительность смены: {$machinery->change_hour}")
            ->line("Стоимость за смену: {$machinery->sum_day_format}");
        return $message;
    }

    private function addNewOfferDRYFields(Proposal $proposal, MailMessage $message, Offer $offer)
    {
        $message->line('Категория техники: ');
        foreach ($offer->machines as $machine) {
            $message->line($machine->name);
        }
        $message->line("Адрес: {$proposal->full_address}")
            ->line("Стоимость: {$proposal->sum_format}")
            ->line("Дата: {$proposal->date->format('d.m.Y H:i')}")
            ->line("Кол-во смен: {$proposal->days}");
        return $message;
    }

    function newProposalCustomerNotification(Lead $proposal, $domain)
    {

      //  $proposal->user->sendEmailNotification(new \App\Mail\Subscription($message, 'Новая заявка.'));
        $template = Template::getTemplate(Template::TYPE_NEW_PROPOSAL, $domain);
        $proposal->company_branch->sendEmailNotification(new DBMail($template, [
            'lead_id' => $proposal->id,
            'company_name' => $proposal->customer->company_name,
            'link' => $proposal->company_branch->getUrl("/leads/{$proposal->id}/info")
        ]));

        // $proposal->user->addNotificationHistory('new_proposal', 'email');
        return $this;

    }

    function newProposalAdminNotification(Lead $proposal)
    {

        $message = (new MailMessage())
            ->subject('Новая заявка.')
            ->line("Пользователь #{$proposal->user->id} создал новую заявку #{$proposal->id}")
            ->line("Email: {$proposal->user->email}")
            ->line("Телефон: +{$proposal->user->phone}");

        $this->addNewProposalDRYFields($proposal, $message);

        Mail::to($this->email)->queue(new \App\Mail\Subscription($message, 'Новая заявка.'));

        return $this;

    }

    function siteMapGetenrate()
    {

        $message = (new MailMessage())
            ->subject('Генерация карты завершена.')
            ->line("Карта сайта успешно сгенерирована.");

        Mail::to($this->email)->queue(new \App\Mail\Subscription($message, 'Генерация карты завершена.'));

        return $this;

    }

    function newProposalWidgetAdminNotification(Proposal $proposal)
    {

        $message = (new MailMessage())
            ->subject('Новая заявка из виджета.')
            ->line("Пользователь #{$proposal->user->id} создал новую заявку #{$proposal->id}")
            ->line("Email: {$proposal->user->email}")
            ->line("Телефон: +{$proposal->user->phone}");

        $this->addNewProposalDRYFields($proposal, $message);

        Mail::to($this->email)->queue(new \App\Mail\Subscription($message, 'Новая заявка из виджета.'));

        return $this;

    }

    /**
     * @param User $user
     */
    function newUserAdminNotification(User $user)
    {

        $role_name = !$user->hasRole('customer') ? 'Исполнитель' : 'Заказчик';
        $message = (new MailMessage())
            ->subject('Новый пользователь.')
            ->line('В системе зарегистрировался новый пользователь #' . $user->id);
        if ($user->spammer) {
            $message->line('ВОЗМОЖНО СПАМЕР!');
        }
        $message->line('Роль: ' . $role_name)
            ->line('Email: ' . $user->email)
            ->line('Телефон: ' . $user->phone);
        /* if (!$user->hasOnlyWidgetRole()) {
             $message->line("Регион и город: {$user->region_name}, {$user->city_name}");
         }*/
        $message->action('Панель управления', url('https://office.trans-baza.ru/users'));

        Mail::to($this->email)->queue(new \App\Mail\Subscription($message, 'Новый пользователь.'));
    }

    function newUserRegionalNotification(User $user, $regional)
    {

        $message = (new MailMessage())
            ->subject('Новый пользователь.')
            ->line('В Вашем регионе зарегистрировался новый пользователь #' . $user->id)
            ->line('Email: ' . $user->email)
            ->line('Телефон: ' . $user->phone)
            ->line("Регион и город: {$user->region_name}, {$user->city_name}");

        $regional->sendEmailNotification(new \App\Mail\Subscription($message, 'Новый пользователь.'));

        $regional->addNotificationHistory('new_user', 'email');
    }

    /**
     * @param Lead $proposal
     * @param LeadOffer $offer
     * @return Subscription
     */
    function newOfferCustomerNotification(Lead $proposal, LeadOffer $offer)
    {

        $message = (new MailMessage())
            ->subject('TRANSBAZA Новое предложение к заявке.')
            ->line("Исполнитель #{$offer->company_branch_id} подтвердил готовность к выполнению заявки #{$proposal->id} ");

        $proposal->company_branch->sendEmailNotification(new \App\Mail\Subscription($message, 'TRANSBAZA Новое предложение к заявке.'));


        // $proposal->user->addNotificationHistory('new_proposal', 'email');
        return $this;
    }

    function newOfferContractorNotification(Proposal $proposal, Offer $offer)
    {

        $message = (new MailMessage())
            ->subject('Новое предложение к заявке.')
            ->line("Вы приняли заявку #{$proposal->id} от заказчика  #{$proposal->user->id}");

        $this->addNewOfferDRYFields($proposal, $message, $offer);

        $offer->user->sendEmailNotification(new \App\Mail\Subscription($message, 'Новое предложение к заявке.'));

        $offer->user->addNotificationHistory('new_proposal', 'email');

        return $this;
    }

    function newOfferRegionalNotification(Proposal $proposal, Offer $offer)
    {

        $message = (new MailMessage())
            ->subject('Новое предложение к заявке.')
            ->line("Исполнитель #{$offer->user->id} принял заявку #{$proposal->id} от заказчика #{$proposal->user->id}");

        $this->addNewOfferDRYFields($proposal, $message, $offer);

        $offer->machine->regional_representative->sendEmailNotification(new \App\Mail\Subscription($message, 'Новое предложение к заявке.'));

        return $this;
    }

    function newOfferAdminNotification(Proposal $proposal, Offer $offer)
    {

        $message = (new MailMessage())
            ->subject('Новое предложение к заявке.')
            ->line("Исполнитель #{$offer->user->id} принял заявку #{$proposal->id} от заказчика #{$proposal->user->id}")
            ->line('Email Заказчика: ' . $proposal->user->email)
            ->line('Телефон Заказчика: +' . $proposal->user->phone)
            ->line('Email Исполнителя: ' . $offer->user->email)
            ->line('Телефон Исполнителя: +' . $offer->user->phone);

        $this->addNewOfferDRYFields($proposal, $message, $offer);


        Mail::to($this->email)->queue(new \App\Mail\Subscription($message, 'Новое предложение к заявке.'));

        return $this;
    }


    /**
     * @param Machinery $machinery
     */
    function newMachineAdminNotification(Machinery $machinery)
    {
        $message = (new MailMessage())
            ->subject('Новая техника.')
            ->line("Пользователь #{$machinery->user?->id} добавил новую единицу техники в регионе {$machinery->full_address}");
        $this->addMachineDRYFields($machinery, $message);

        $message->action('Перейти к технике', url('https://office.trans-baza.ru/machineries/' . $machinery->id . '/edit'));

        Mail::to($this->email)->queue(new \App\Mail\Subscription($message, 'Новая техника.'));

        return $this;
    }

    function newMachineRegionalNotification(Machinery $machinery)
    {
        $message = (new MailMessage())
            ->subject('Новая техника.')
            ->line("Пользователь #{$machinery->user->id} добавил новую единицу техники в вашем регионе")
            ->line('Email: ' . $machinery->user->email)
            ->line('Телефон: +' . $machinery->user->phone);

        $this->addMachineDRYFields($machinery, $message);

        $machinery->regional_representative->sendEmailNotification(new \App\Mail\Subscription($message, 'Новая техника.'));

        $machinery->regional_representative->addNotificationHistory('new_machine', 'email');
        return $this;
    }

    function newMachineContractorNotification(Machinery $machinery)
    {
        $message = (new MailMessage())
            ->subject('Новая техника.')
            ->line("Вы добавили новую единицу техники в онлайн-сервис быстрой аренды спецтехники TRANS-BAZA.RU.")
            ->line('В Вашем регионе есть Региональный представитель (РП), который поможет Вам в работе. ');

        if ($machinery->regional_representative) {
            $message->line("Email РП: {$machinery->regional_representative->email}");
            $message->line("Телефон РП: {$machinery->regional_representative->phone}");
        } else {
            $message->line("Email РП: info@trans-baza.ru");
            $message->line("Телефон РП: +79256070803");
        }


        $this->addMachineDRYFields($machinery, $message);

        $machinery->user->sendEmailNotification(new \App\Mail\Subscription($message, 'Новая техника.'));


        $machinery->user->addNotificationHistory('new_machine', 'email');
        return $this;
    }


    function newUserCustomerNotification(User $user, $regional_representative)
    {

        $template = Template::getTemplate(Template::TYPE_REGISTER_USER, RequestHelper::requestDomain()->id);

        if(!$template) {
            logger("no template " . Template::TYPE_REGISTER_USER);
            return $this;
        }

        $user->sendEmailNotification(new \Modules\RestApi\Emails\DBMail($template, ['link' => $template->domainLink('profile')]));


        return $this;
    }

    function newUserContractorNotification(User $user, $regional_representative)
    {
        $message = (new MailMessage())
            ->subject('Добро пожаловать.')
            ->line("Добро пожаловать в онлайн-сервис быстрой аренды спецтехники TRANS-BAZA.RU.  Заводите технику в систему и получайте заказы.")
            ->line("В Вашем регионе есть Региональный представитель (РП), который поможет Вам в работе. ");

        if ($regional_representative) {
            $message->line("Email РП: {$regional_representative->email}");
            $message->line("Телефон РП: {$regional_representative->phone}");
        } else {
            $message->line("Email РП: info@trans-baza.ru");
            $message->line("Телефон РП: +79256070803");
        }


        $message->action('Подписывайтесь на наш канал в Youtube и следите за новостями', url('https://www.youtube.com/channel/UCr5_rboPPqa3ii3Z5AAB_2g?view_as=subscriber'));


        $user->sendEmailNotification(new \App\Mail\Subscription($message, 'Добро пожаловать'));

        return $this;
    }


    /**
     * @param Proposal $proposal
     * @param bool $forContractor
     */
    function newProposalNotification(Lead $proposal, User $forContractor = null)
    {

        if ($forContractor) {
            $message = (new MailMessage())
                ->subject('TRANSBAZA Появилась новая заявка.')
                ->line("Компания #{$proposal->company_branch->id} создала новую заявку #{$proposal->id}");

            $this->addNewProposalDRYFields($proposal, $message);

            $message->action('Откликнуться', "https://trans-baza.ru/contractor/leads/{$proposal->id}/info");

            $forContractor->sendEmailNotification(new \App\Mail\Subscription($message, 'TRANSBAZA Появилась новая заявка.'));
        }
    }

    function newOrderCustomerNotification(Order $order)
    {
        /*----------------------------------------------*/

/*        $message = (new MailMessage())
            ->subject('Новый заказ.')
            ->line("Вы создали заказ #{$order->id}");

        $message->line('Email Исполнителя: ' . $order->contractor->email)
            ->line('Телефон Исполнителя: +' . $order->contractor->phone);


        $this->addDRYFields($order, $message, false);

        $order->user->sendEmailNotification(new \App\Mail\Subscription($message, 'Новый заказ.'));

        $order->user->addNotificationHistory('new_order', 'email');*/

        $template = Template::getTemplate(Template::TYPE_NEW_ORDER_CUSTOMER, RequestHelper::requestDomain()->id);

        $order->customer->sendEmailNotification(new DBMail($template, ['order_id' => $order->internal_number]), false);

        return $this;
    }

    function newOrderContractorNotification(Order $order)
    {

     /*   $message = (new MailMessage())
            ->subject('Новый заказ.')
            ->line("Вам назначен новый заказ #{$order->id} от заказчика #{$order->user->id}")
            ->line('Email Заказчика: ' . $order->user->email)
            ->line('Телефон Заказчика: +' . $order->user->phone);


        $current_message = clone $message;
        $this->addDRYFields($order, $current_message, true, $order->contractor->id);
        $order->contractor->sendEmailNotification(new \App\Mail\Subscription($current_message, 'Новый заказ.'));
        $order->contractor->addNotificationHistory('new_order', 'email');*/

        $template = Template::getTemplate(Template::TYPE_NEW_ORDER_CONTRACTOR, RequestHelper::requestDomain()->id);

        $order->company_branch->sendEmailNotification(new DBMail($template, ['order_id' => $order->internal_number]), false);

        return $this;

    }

    function newOrderRegionalNotification(Order $order)
    {

        $message = (new MailMessage())
            ->subject('Новый заказ.')
            ->line("Исполнителю #{$order->contractor->id} назначен новый заказ #{$order->id} от заказчика #{$order->user->id}")
            ->line('Email Заказчика: ' . $order->user->email)
            ->line('Телефон Заказчика: +' . $order->user->phone);

            $message->line("Email Исполнителя #{$order->contractor->id}: " . $order->contractor->email)
                ->line("Телефон Исполнителя #{$order->contractor->id}: +" . $order->contractor->phone);


        $this->addDRYFields($order, $message);


        $order->regional_representative->sendEmailNotification(new \App\Mail\Subscription($message, 'Новый заказ.'));

        $order->regional_representative->addNotificationHistory('new_order', 'email');
        return $this;
    }

    function newOrderAdminNotification(Order $order)
    {

        $message = (new MailMessage())
            ->subject('Новый заказ.')
            ->line("Исполнителю #{$order->contractor->id} назначен новый заказ #{$order->id} от заказчика #{$order->user->id}")
            ->line('Email Заказчика: ' . $order->user->email)
            ->line('Телефон Заказчика: +' . $order->user->phone);

            $message->line("Email Исполнителя #{$order->contractor->id}: " . $order->contractor->email)
                ->line("Телефон Исполнителя #{$order->contractor->id}: +" . $order->contractor->phone);


        $this->addDRYFields($order, $message);

        Mail::to($this->email)->queue(new \App\Mail\Subscription($message, 'Новый заказ.'));

        return $this;
    }

    /**
     * @param Proposal $proposal
     */
    function doneOrderCustomerNotification(Order $order, $user)
    {
        /*----------------------------------------------*/

        $message = (new MailMessage())
            ->subject('Выполненый заказ.')
            ->line("Ваш заказ #{$order->id} на исполнителя #{$user->id} выполнен. Ждём новых заказов")
            ->line('Email Исполнителя: ' . $user->email)
            ->line('Телефон Исполнителя: +' . $user->phone);

        $this->addDRYFields($order, $message, false, $user->id);

        $order->user->sendEmailNotification(new \App\Mail\Subscription($message, 'Выполненый заказ.'));
        $order->user->addNotificationHistory('order_complete', 'email');
        return $this;
    }

    function doneOrderRegionalNotification(Order $order, User $user)
    {

        $message = (new MailMessage())
            ->subject('Выполненый заказ.')
            ->line("Исполнитель #{$user->id} выполнил заказ #{$order->id} от заказчика #{$order->user->id}")
            ->line('Email Исполнителя: ' . $user->email)
            ->line('Телефон Исполнителя: +' . $user->phone);
        $this->addDRYFields($order, $message, true, $user->id);

        Mail::to($order->regional_representative->email)->queue(new \App\Mail\Subscription($message, 'Выполненый заказ.'));
        $order->regional_representative->addNotificationHistory('order_complete', 'email');
        return $this;
    }

    function doneOrderAdminNotification(Order $order, User $user)
    {


        $message = (new MailMessage())
            ->subject('Выполненый заказ.')
            ->line("Исполнитель #{$user->id} выполнил заказ #{$order->id} от заказчика #{$user->id}")
            ->line('Email Заказчика: ' . $order->user->email)
            ->line('Телефон Заказчика: +' . $order->user->phone)
            ->line('Email Исполнителя: ' . $user->email)
            ->line('Телефон Исполнителя: +' . $user->phone);
        $this->addDRYFields($order, $message, true, $user->id);

        Mail::to($this->email)->queue(new \App\Mail\Subscription($message, 'Выполненый заказ.'));

        return $this;
    }


    function doneOrderContractorNotification(Order $order, $user)
    {

        $message = (new MailMessage())
            ->subject('Выполненый заказ.')
            ->line("Вами выполнен заказ #{$order->id} от заказчика #{$order->user->id}. Ждём новых заказов")
            ->line('Email Заказчика: ' . $order->user->email)
            ->line('Телефон Заказчика: +' . $order->user->phone);
        $this->addDRYFields($order, $message, true, $user->id);


        $user->user->sendEmailNotification(new \App\Mail\Subscription($message, 'Выполненый заказ.'));


        $user->addNotificationHistory('order_complete', 'email');
        return $this;

    }

    /**
     * @param FinanceTransaction $transaction
     * @param string $email
     */
    function newPaymentNotification(FinanceTransaction $transaction, $email = 'nikolay.strafe@gmail.com')
    {
        $message = (new MailMessage())
            ->subject('Новая оплата.')
            ->line('Пользователь #' . $transaction->user->id . ' произвел оплату картой.')
            ->line('Email Заказчика: ' . $transaction->user->email)
            ->line('Телефон Заказчика: ' . $transaction->user->phone)
            ->line('Сумма: ' . SmsNotification::formatNumber($transaction->sum / 100));

        $requisite = $transaction->user->getActiveRequisite('customer');

        if ($requisite) {
            $message->line('Реквизиты:');
            if ($requisite instanceof User\EntityRequisite) {
                foreach (User\EntityRequisite::$attributesName as $key => $name) {
                    $message->line($name . ': ' . $requisite->$key);
                }
            }

            if ($requisite instanceof User\IndividualRequisite) {
                foreach (User\IndividualRequisite::$attributesName as $key => $name) {
                    $message->line($name . ': ' . $requisite->$key);
                }
            }
        }


        $message->action('Перейти к транзакциям', url('https://office.trans-baza.ru/finance_transactions'));

        Mail::to($transaction->email)->queue(new \App\Mail\Subscription($message, 'Новая оплата.'));

        $message = (new MailMessage())
            ->subject('Оплата картой.')
            ->line('Вы произвели оплату картой.')
            ->line('Сумма: ' . $transaction->sum / 100 . ' руб.')
            ->line('Актуальный баланс: ' . humanSumFormat($transaction->user->getBalance('customer')) . ' руб.')
            ->line('Дата: ' . $transaction->updated_at->format('d.m.Y H:i'));

        $message->action('Перейти к транзакциям', url('https://trans-baza.ru/customer/balance'));

        $transaction->user->sendEmailNotification(new \App\Mail\Subscription($message, 'Оплата картой.'));


        $transaction->user->addNotificationHistory('card_pay', 'email');
    }

    /**
     * @param FinanceTransaction $transaction
     * @param string $email
     */
    function newAccountPaymentNotification(FinanceTransaction $transaction, $email = 'nikolay.strafe@gmail.com')
    {
        $message = (new MailMessage())
            ->subject('Новая оплата.')
            ->line('Пользователь #' . $transaction->user->id . ' создал запрос на оплату.')
            ->line('Email: ' . $transaction->user->email)
            ->line('Телефон: ' . $transaction->user->phone)
            ->line('Сумма: ' . $transaction->sum / 100);

        $requisite = $transaction->user->getActiveRequisite('customer');

        if ($requisite) {
            $message->line('Реквизиты:');
            if ($requisite instanceof User\EntityRequisite) {
                foreach (User\EntityRequisite::$attributesName as $key => $name) {
                    $message->line($name . ': ' . $requisite->$key);
                }
            }

            if ($requisite instanceof User\IndividualRequisite) {
                foreach (User\IndividualRequisite::$attributesName as $key => $name) {
                    $message->line($name . ': ' . $requisite->$key);
                }
            }
        }


        $message->action('Перейти к транзакциям', url('https://office.trans-baza.ru/finance_transactions'));

        Mail::to($transaction->email)->queue(new \App\Mail\Subscription($message, 'Новый счет на оплату.'));
    }

    function depositingToAccount(User $user, $sum, $role, $date)
    {
        $format = SmsNotification::formatNumber($sum);

        $description = ($role === 'customer') ? 'Открывайте поиск Исполнителя и создавайте заказ. Успешной работы!' : 'Акутализируйте календарь доступности техники и получайте заказы. Успешной работы!';

        $role = ($role === 'customer') ? 'Заказчика' : 'Исполнителя';

        $message = (new MailMessage)
            ->subject('Пополнение баланса.')
            ->line("Ваш Баланс {$role} в онлайн системе быстрой аренды спецтехники TRANS-BAZA.RU пополнен.")
            ->line("Сумма: {$format}")
            ->line("Дата: {$date}")
            ->line($description);

        $user->sendEmailNotification(new \App\Mail\Subscription($message, 'Пополнение баланса.'));
        $user->addNotificationHistory('new_balance', 'email');
    }

    function newRequestContractor(RequestContractor $contractor)
    {
        $message = (new MailMessage())
            ->subject('Справочник - добавить исполнителя')
            ->line('Email: ' . $contractor->email)
            ->line('Телефон: ' . $contractor->phone)
            ->line('Имя: ' . $contractor->name)
            ->line("{$contractor->type->name} {$contractor->city->region->name} {$contractor->city->name}")
            ->line("Примечание: {$contractor->comment}");

        Mail::to($this->email)->queue(new \App\Mail\Subscription($message, 'Справочник - добавить исполнителя'));

        return $this;
    }


    function newUserFromForm(User $user, $password = null)
    {
        $message = (new MailMessage())
            ->subject('Добро пожаловать в TRANSBAZA!')
            ->line('TRANSBAZA - современный онлайн-сервис для быстрого поиска и аренды/продажи/покупки строительного оборудования, спецтехники и коммерческого транспорта.')
            ->line('Email: ' . $user->email)
            ->line('Телефон: ' . $user->phone);
        if ($password) {
            $message->line('Пароль для входа: ' . $password);
        }
        $message->line('Не забудьте сменить пароль после входа в систему!')
            ->action('Подтвердить email', origin("/confirm/{$user->confirm->token}"));

        $user->sendEmailNotification(new \App\Mail\Subscription($message, 'Добро пожаловать в TRANSBAZA!'), false);

        return $this;
    }

    function sendRegisterToken(User $user)
    {
        $template = Template::getTemplate(Template::TYPE_CONFIRM_EMAIL, RequestHelper::requestDomain()->id);

        $user->sendEmailNotification(new DBMail($template, ['link' => origin("/confirm/{$user->confirm->token}")]), false);

        return $this;
    }

    function newSubmitSimpleForm($simpleProposal, $is_new)
    {


        $text = $is_new
            ? "Создан Новый Заказчик: #{$simpleProposal->user_id}"
            : "Заявка Существующего Заказчика: #{$simpleProposal->user_id}";
        $url = route('submit-proposal.edit', $simpleProposal->id);
        $message = (new MailMessage())
            ->subject('Справочник - добавить исполнителя')
            ->line('Email: ' . $simpleProposal->email)
            ->line('Телефон: ' . $simpleProposal->phone);
        if ($simpleProposal->contractor_service) {
            $message->line('Сервис : ' . $simpleProposal->contractor_service->name);
        }

        if ($simpleProposal->service) {
            $message->line('Сервис : ' . $simpleProposal->service->title);
        }
        $message->line('Что нужно: ' . $simpleProposal->comment)
            ->line("Примечание: {$text}")
            ->line("На основании заявки с сайта ЗАЯВКА В СИСТЕМЕ МОЖЕТ БЫТЬ СОЗДАНА ТОЛЬКО ВРУЧНУЮ АДМИНИСТРАТОРОМ после уточнения параметров заявки !")
            ->action('Управление', $url);

        Mail::to($this->email)->queue(new \App\Mail\Subscription($message, 'Новая заявка с сайта'));

        return $this;
    }

    function resetPassword(User $user, $token)
    {

        $message = (new MailMessage)
            ->subject('Восстановление пароля')
            ->line('Вы получили этот емейл так как кто-то, возможно, Вы, направил запрос на восстановление пароля.')
            ->action('Восстановить пароль', url(config('app.url') . route('password.reset', $token, false)))
            ->line('Если Вы не направляли запрос на восстановление пароля, просто игнорируйте данное письмо.');


        $user->sendEmailNotification(new \App\Mail\Subscription($message, 'TRANS-BAZA.RU - Восстановление пароля'));

        return $this;
    }


}

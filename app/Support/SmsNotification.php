<?php

namespace App\Support;


use App\User;
use Carbon\Carbon;
use App\Overrides\Model;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\Orders\Entities\Order;

class SmsNotification extends Model
{
    protected $fillable = [
        'message',
        'user_id',
        'status',
        'phone'
    ];


    function user()
    {
        return $this->belongsTo(User::class);
    }


    static function formatNumber($sum)
    {
        $symbol = '₽'; //self::getCurrencySymbol('RUR', 'ru_RU');
        return number_format($sum / 100, 2, '.', ',') . " {$symbol}";
    }

    static function getCurrencySymbol($currencyCode, $locale = 'en_US')
    {
        $formatter = new \NumberFormatter($locale . '@currency=' . $currencyCode, \NumberFormatter::CURRENCY);
        return $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
    }

    /**
     * For All users
     * @param User $user
     * @param      $billing
     * @param      $sum
     * @return string
     */
    static function buildIncrementBalanceText(User $user, $billing, $sum)
    {
        $role = '';
        switch ($billing) {
            case 'customer':
                $role = 'Заказчик';
                break;
            case 'contractor':
                $role = 'Исполнитель';
                break;
            case 'widget':
                $role = 'Виджет';
                break;
        }

        return 'У роли ' . $role . ' был пополнен баланс на сумму ' . $sum / 100 . '. Новый баланс: '
            . self::formatNumber($user->getBalance($billing)) . '; ' . Carbon::now('Europe/Moscow')->format('d.m.Y H:i');
    }

    /**
     * For Contractor
     * @param User $user
     * @param      $billing
     * @param      $sum
     * @return string
     */
    static function buildDoneOrderForRegionalText(User $user, Order $order)
    {

        return "Исполнитель #{$user->id} выполнил {$order->id}. {$order->date_from->format('d.m.Y')}";
    }

    /**
     * For Contractor
     * @param Order $order
     * @param CompanyBranch $companyBranch
     * @return string
     */
    static function buildNewContracotorOrderText(Order $order, CompanyBranch $companyBranch)
    {

        $text = self::getCountText($order, $companyBranch);
        return trans('sms_notifications.new_contractor_order', [
            'order_id' => $order->id,
            'date' => $order->date_from->format('d.m.Y'),
            'address' => $order->address,
            'vehicles' => $text,
            'link' => $order->generateContractorLink(),
        ]);//"Вам назначен Заказ #{$order->id} на {$order->date_from->format('d.m.Y')}.  Адрес: {$order->address}. {$text} {$order->generateContractorLink()}";
    }




    /**
     * For Customer
     * @param Proposal $proposal
     * @return string
     */
    private static function getCountText($order, $companyBranch)
    {

        $vehicles = $order->vehicles->where('company_branch_id', $companyBranch->id)->all();
        $count = count($vehicles);
        if ($count === 1) {
            $m = trans('transbaza_machine_index.title');
            $text = "{$m}: {$vehicles[0]->name}.";
        } else {
            $m = trans('transbaza_spectehnika.machines_count');
            $text = "{$m}: {$count}.";
        }
        return $text;
    }

    static function buildRegionalNotificationAboutNewOrder(Order $order, $user)
    {
        $type = self::getCountTypesText($order);
        return "Исполнителю #{$user->id} назначен заказ #{$order->id} на {$order->date_from->format('d.m.Y H:i')}. {$type}";
    }

    private static function getCountTypesText($order)
    {
        $count = $order->types->count();
        if ($count === 1) {
            $m = trans('transbaza_spectehnika.machines_category');
            $text = "{$m}: {$order->types->first()->localization()->name}.";
        } else {
            $m = trans('transbaza_spectehnika.machines_count');
            $text = "{$m}: {$count}.";
        }
        return $text;
    }


    static function buildAcceptCustomerOrderText(Proposal $proposal)
    {
        $proposal->load('winner_offer');
       $text = self::getCountText($proposal);
        return "Исполнитель #{$proposal->winner_offer->user->id} принял вашу заявку #{$proposal->id}. {$text} {$proposal->generateLink()}";
    }

    static function buildOfferCustomerProposalText(Proposal $proposal, Offer $offer)
    {
        $text = self::getCountText($proposal, $offer);
        return "Исполнитель #{$offer->user->id} принял вашу заявку #{$proposal->id}. Адрес: {$proposal->full_address}, {$text} {$proposal->generateLink()}";
    }

    static function buildOfferRegionalProposalText(Proposal $proposal, Offer $offer)
    {
        $text = self::getCountText($proposal, $offer);
        return "Исполнитель #{$offer->user->id} принял заявку #{$proposal->id}. Адрес: {$proposal->full_address}, {$text} {$proposal->generateLink()}";
    }

    /**
     * For Customer
     * @param Proposal $proposal
     * @return string
     */
    static function buildNewCustomerProposalText(Proposal $proposal)
    {
        $type = self::getCountTypesText($proposal);
        return "Вы оформили заявку #{$proposal->id}, {$type}, на {$proposal->date->format('d.m.Y H:i')}. {$proposal->generateLink()}";
    }

    /**
     * For Customer
     * @param Order $order
     * @return string
     */
    static function buildNewCustomerOrderText(Order $order)
    {
        $type = self::getCountTypesText($order);
        return trans('sms_notifications.new_customer_order', [
            'order_id' => $order->id,
            'date' => $order->date_from->format('d.m.Y H:i'),
            'vehicles' =>  $type,
            'link' => $order->generateCustomerLink(),
        ]);//"Вы оформили заказ #{$order->id} на: {$order->date_from->format('d.m.Y H:i')}, {$type}, {$order->generateCustomerLink()}";
    }

    /**
     * For Customer
     * @param Order $order
     * @return string
     */
    static function buildNewDispatcherCustomerOrderText(Order $order)
    {
        $type = self::getCountTypesText($order);
        return trans('sms_notifications.new_dispatcher_customer_order', [
            'order_id' => $order->id,
            'date' => $order->date_from->format('d.m.Y H:i'),
            'vehicles' => $type,
            'phone' => $order->user->phone,
        ]);
        //"Для Вас оформлен заказ #{$order->id} на: {$order->date_from->format('d.m.Y H:i')}, {$type}, Телефон для связи +{$order->user->phone}";
    }


    /**
     * For Customer
     * @param Order $order
     * @return string
     */
    static function buildDoneOrderText(Order $order)
    {

        return trans('sms_notifications.done_order_text', [
            'order_id' => $order->id,
            'date' =>$order->date_from->format('d.m.Y'),
            'link' => $order->generateCustomerLink(),
        ]);//"Ваш заказ #{$order->id} на {$order->date_from->format('d.m.Y')} выполнен. {$order->generateCustomerLink()}";
    }

    /**
     * @param Proposal $proposal
     * @return string
     */
    static function buildNewProposalForContractorText(Proposal $proposal)
    {
        $type = self::getCountTypesText($proposal);
        return "Оформлена новая заявка #{$proposal->id}. {$type} {$proposal->generateLink()}";
    }

    static function buildNewProposalForRegionalText(Proposal $proposal)
    {
        $type = self::getCountTypesText($proposal);
        return "Оформлена новая заявка #{$proposal->id} на {$proposal->date->format('d.m.Y H:i')}. {$type} {$proposal->generateLink()}";
    }



    static function buildRegionalNotificationAboutNewProposal(Proposal $proposal)
    {
        $type = self::getCountTypesText($proposal);
        return "Исполнителю #{$proposal->winner_offer->user->id} назначен заказ #{$proposal->id} на {$proposal->date->format('d.m.Y H:i')}. {$type} {$proposal->generateLink()}";
    }
}

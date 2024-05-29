<?php

namespace App\Service;

use App\Jobs\SendNewProposalNotification;
use App\Machinery;
use App\Seo\RequestContractor;
use App\Support\SmsNotification;
use App\User;
use Carbon\Carbon;
use Modules\Orders\Entities\Order;
use Modules\RestApi\Jobs\SendSms;

class EventNotifications
{
    private $events = [];

    public $subscription;


    public function __construct()
    {

        $this->subscription = new Subscription();

    }

    function depositingToAccount(User $user, $account_type, $sum, $date = null)
    {
        $date = (is_null($date)) ? Carbon::now()->format('d.m.Y H:i') : $date;

        $user->sendSmsNotification(SmsNotification::buildIncrementBalanceText($user, $account_type, $sum), false, 'new_balance');

        $this->subscription->depositingToAccount($user, $sum, $account_type, $date);

        return $this;
    }

    function newOrder(Order $order)
    {
        $winningUser = $order->contractor;


        $winningUser->sendSmsNotification(SmsNotification::buildNewContracotorOrderText($order, $winningUser), false, 'new_order');


        $customer_text = SmsNotification::buildNewCustomerOrderText($order);
        if ($order->leads()->exists()) {

            $dispatcher_customer_text = SmsNotification::buildNewDispatcherCustomerOrderText($order);

            dispatch(new SendSms($order->lead->phone, $dispatcher_customer_text))->delay(now()->addSeconds(5));

        } else {
            $order->user->sendSmsNotification($customer_text, false, 'new_order');
        }


        $this->subscription
            ->newOrderCustomerNotification($order)
            ->newOrderContractorNotification($order)
            ->newOrderAdminNotification($order);

        if ($order->regional_representative) {


            $order->regional_representative
                ->sendSmsNotification(SmsNotification::buildRegionalNotificationAboutNewOrder($order, $winningUser), false, 'new_order');


            $this->subscription->newOrderRegionalNotification($order);
        }

        return $this;
    }

    function doneOrder(Order $order)
    {
        $winningUsers = $order->winningUsers();


        $order->user->sendSmsNotification(SmsNotification::buildDoneOrderText($order), false, 'order_complete');

        $this->subscription
            //->doneOrderCustomerNotification($order)
            //->doneOrderContractorNotification($order)
            ->doneOrderAdminNotification($order);
        foreach ($winningUsers as $user) {
            $this->subscription->doneOrderContractorNotification($order, $user);
        }

        return $this;
    }

    function contractorDoneOrder(Order $order, User $user)
    {
        $this->subscription->doneOrderCustomerNotification($order, $user)->doneOrderAdminNotification($order, $user);


        if ($order->regional_representative) {
            $order->regional_representative->sendSmsNotification(SmsNotification::buildDoneOrderForRegionalText($user, $order), false, 'order_complete');
            $this->subscription->doneOrderRegionalNotification($order);
        }
    }

    function newProposal(Proposal $proposal)
    {
        $proposal->refresh();
        $proposal->user->sendSmsNotification(SmsNotification::buildNewCustomerProposalText($proposal), false, 'new_proposal');


        dispatch(new SendNewProposalNotification($proposal));

        $this->subscription
            ->newProposalCustomerNotification($proposal)
            ->newProposalAdminNotification($proposal);

        return $this;
    }

    function newWidgetProposal(Proposal $proposal)
    {
        $proposal->refresh();
        $proposal->user->sendSmsNotification(SmsNotification::buildNewCustomerProposalText($proposal), false, 'new_proposal');


        dispatch(new SendNewProposalNotification($proposal));

        $this->subscription
            ->newProposalCustomerNotification($proposal)
            ->newProposalWidgetAdminNotification($proposal);

        return $this;
    }


    function newMachine(Machinery $machinery)
    {
        $machinery->refresh();
        $this->subscription->newMachineAdminNotification($machinery);

        // ->newMachineContractorNotification($machinery);
        if ($machinery->regional_representative) {
            $this->subscription->newMachineRegionalNotification($machinery);
        }
    }

    function newUser(User $user)
    {
        $user->refresh();
        $regional = User::where('native_region_id', $user->native_region_id)
            ->where('native_city_id', $user->native_city_id)
            ->where('native_region_id', '!=', 0)
            ->where('native_city_id', '!=', 0)
            ->where('is_regional_representative', 1)
            ->first();

        $this->subscription->newUserCustomerNotification($user, $regional);

        $this->subscription->newUserAdminNotification($user);
        if ($regional) {
            $this->subscription->newUserRegionalNotification($user, $regional);
        }

        return $this;
    }

    function newWidgetUser(User $user)
    {
        $user->refresh();
        $this->subscription->newUserAdminNotification($user);
        return $this;
    }

    function newRequestContractor(RequestContractor $contractor)
    {
        $this->subscription->newRequestContractor($contractor);
        return $this;
    }
}

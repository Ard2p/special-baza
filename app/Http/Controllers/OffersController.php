<?php

namespace App\Http\Controllers;

use App\City;
use App\Directories\TransactionType;
use App\Machinery;

use App\Option;

use App\Service\EventNotifications;
use App\Service\OrderService;
use App\Service\Subscription;
use App\Support\Region;
use App\Support\SmsNotification;
use App\User\BalanceHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OffersController extends Controller
{
    function getRequiredFields($merge = false)
    {
        $array = [
            'id' => 'required|integer',
            'proposal_id' => 'required|integer',
        ];
        if ($merge) {
            $fields = [
                'custom_sum' => 'required|numeric|min:1',
            ];
            $array = array_merge($array, $fields);
        }
        return $array;
    }

    function getValidatorMessages()
    {
        return [
            'custom_sum.numeric' => 'Сумма должна быть числом',
            'custom_sum.required' => 'Введите стоимость Вашего предложения.'
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $proposal = Proposal::with('offers')->where('id', $request->input('proposal', 0))->firstOrFail();

        $machines = [];

        $i = 0;
        foreach ($proposal->offers as $offer) {
            $machines[$i] = $offer->machines()->with('brand', '_type', 'region', 'freeDays', 'user')->first();
            $machines[$i]['sum'] = $offer->sum;
            $machines[$i]['offer_id'] = $offer->id;
            ++$i;
        }


        return response()->json(['data' => $machines]);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        if ($request->has('check_custom')) {
            $merge = true;
            $request->merge(['custom_sum' => str_replace(',', '.', $request->input('custom_sum'))]);
        }
        $errors = Validator::make($request->all(), [
            'data.another_sum' => 'numeric|min:0'
        ])
            ->errors()
            ->getMessages();
        if (Offer::where('user_id', Auth::user()->id)->where('proposal_id', $request->input('proposal_id'))->first()) {
            $errors['modals'] = ['Вы уже добавляли предложение к этому заказу.'];
        }

        if ($errors) return response()->json($errors, 419);

        $proposal_id = $request->input('proposal_id');
        $proposal = Proposal::checkAvailableOrFire()->findOrFail($proposal_id);


        $collected_data = collect($request->data);
        $collected_data = $collected_data->unique('id');
        $offer = Offer::addOffer($proposal, $collected_data, Auth::user(), $request->input('comment'));
        if(!$offer){
            return response()->json(['modals' => ['Невозможно добавить предложение т.к. техника уже не доступна.']], 419);
        }
        (new EventNotifications())->newOffer($proposal, $offer);



        return response()->json([
            'message' => 'Предложение успешно добавлено.'
        ]);

    }

    function checkFreeMachineDays($proposal, $machine_id)
    {

        $date_start = (clone $proposal->date)->startOfDay();
        $date_end = (clone $proposal->date)->addDays($proposal->days - 1)->startOfDay();

        $machine = Machinery::checkProposal($proposal->region_id, $proposal->type_ids)
            ->findOrFail($machine_id);


        if ($machine->isAvailable($date_start, $date_end)) {
            return $machine;
        }

        return false;
    }


    function accept(Request $request, $fromInvite = false)
    {
        $errors = [];
        $offer = Offer::findOrFail($request->input('offer_id'));

        $proposal = $offer->proposal()->checkAvailableOrFire()->firstOrFail();

        if (Auth::user()->getBalance('customer') < $offer->sum) {
            $errors[] = 'Недостаточный баланс для принятия этого предложения';
            $errors['hold'] = view('proposal.payment.hold', ['request' => $request, 'offer' => $offer, 'type' => 'proposal'])->render();
            return response()->json($errors, 419);
        }

        DB::beginTransaction();
        $service = new OrderService();
        $result = $service->setOffer($offer)->forUser(Auth::id())->acceptOffer()->getErrors();
        DB::commit();

      /*      if ($offer->sum !== $proposal->sum) {
                /*BalanceHistory::create([
                    'user_id' => Auth::user()->id,
                    'admin_id' => 0,
                    'old_sum' => Auth::user()->getBalance('customer'),
                    'new_sum' => Auth::user()->getBalance('customer') + $proposal->sum,
                    'type' => BalanceHistory::getTypeKey('return_reserve'),
                    #/*    'requisite_id' => Auth::user()->getActiveRequisite()->id,
                     #   'requisite_type' => Auth::user()->getActiveRequisiteType(),
                    'billing_type' => 'customer',
                    'sum' => $proposal->sum,
                    'reason' => TransactionType::getTypeLng('return_reserve') . ' Заказ #' . $proposal->id,
                ]);

                /*Auth::user()->incrementCustomerBalance($proposal->sum);

                BalanceHistory::create([
                    'user_id' => Auth::user()->id,
                    'admin_id' => 0,
                    'old_sum' => Auth::user()->getBalance('customer'),
                    'new_sum' => Auth::user()->getBalance('customer') - $offer->sum,
                    'type' => BalanceHistory::getTypeKey('reserve'),
                    /*           'requisite_id' => Auth::user()->getActiveRequisite()->id,
                               'requisite_type' => Auth::user()->getActiveRequisiteType(),
                    'billing_type' => 'customer',
                    'sum' => $offer->sum,
                    'reason' => TransactionType::getTypeLng('reserve') . ' Заявка #' . $proposal->id,
                ]);
                Auth::user()->decrementCustomerBalance($offer->sum);
            } else {
                BalanceHistory::create([
                    'user_id' => Auth::user()->id,
                    'admin_id' => 0,
                    'old_sum' => Auth::user()->getBalance('customer'),
                    'new_sum' => Auth::user()->getBalance('customer') - $proposal->sum,
                    'type' => BalanceHistory::getTypeKey('reserve'),
                    /*  'requisite_id' => Auth::user()->getActiveRequisite()->id,
                      'requisite_type' => Auth::user()->getActiveRequisiteType(),
                    'billing_type' => 'customer',
                    'sum' => $proposal->sum,
                    'reason' => TransactionType::getTypeLng('reserve') . ' Заявка #' . $proposal->id,
                ]);

                Auth::user()->decrementCustomerBalance($proposal->sum);
            }


            $contractor_timestamps = new Proposal\ContractorTimestamps([]);

            $proposal->contractor_timestamps()->save($contractor_timestamps);

            $machine->setOrderDates($proposal_startDate, $proposal_endDate, $proposal->id);
            $proposal->status = array_search('accept', Proposal::PROP_STATUS);
            $proposal->sum = $offer->sum;


            $proposal->regional_representative_id = $machine->regional_representative_id;
            $system_commission = $proposal->system_commission;
            $representative_commission = Option::find('representative_commission')->value ?? 0;
            $proposal->regional_representative_commission =
                ($machine->regional_representative && ($machine->regional_representative->commission->enable ?? false))
                    ? (($system_commission > $machine->regional_representative->commission->percent)
                    ? $machine->regional_representative->commission->percent
                    : $system_commission)
                    : (($representative_commission < $system_commission)
                    ? $representative_commission
                    : $system_commission);

            //   dd($proposal->regional_representative_commission);
            $proposal->save();

            $offer->is_win = 1;
            $offer->save();
            // $offer->user->notify(new NewOrder($proposal, $machine));
            if (!$fromInvite) {
                (new EventNotifications())->newOrder($proposal);
            }*/

        return response()->json(
            $result ? $result : [
                'message' => 'Предложение принято.'
            ]);

    }

    function acceptFire($request)
    {
        $proposal = Proposal::checkFire()->findOrFail($request->input('proposal_id'));

        $machine = Machinery::currentUser()->findOrFail($request->input('id'));

        if ($proposal->checkFreeDaysForMachine($machine->id)) {


            DB::transaction(function () use ($proposal, $machine) {
                $proposal_startDate = (clone $proposal->date)->startOfDay();

                $proposal_endDate = (clone $proposal->date)->startOfDay()->addDays($proposal->days - 1);

                Offer::create([
                    'user_id' => Auth::user()->id,
                    'proposal_id' => $proposal->id,
                    'machine_id' => $machine->id,
                    'sum' => $proposal->sum,
                    'is_win' => 1,
                    'comment' => '',
                ]);


                $machine->setOrderDates($proposal_startDate, $proposal_endDate, $proposal->id);
                $proposal->status = array_search('accept', Proposal::PROP_STATUS);

                $proposal->regional_representative_id = $machine->regional_representative_id;

                $system_commission = $proposal->system_commission;
                $representative_commission = Option::find('representative_commission')->value ?? 0;
                $proposal->regional_representative_commission =
                    ($machine->regional_representative && ($machine->regional_representative->commission->enable ?? false))
                        ? (($system_commission > $machine->regional_representative->commission->percent)
                        ? $machine->regional_representative->commission->percent
                        : $system_commission)
                        : (($representative_commission < $system_commission)
                        ? $representative_commission
                        : $system_commission);


                $proposal->save();
                $proposal->user->sendSmsNotification(SmsNotification::buildAcceptCustomerOrderText($proposal));
            });

            return true;
        } else {
            return false;
        }

    }

    function invite(Request $request)
    {
        $request_search = $request->input('request');

        if (!is_array($request_search)) {
            parse_str($request->input('request'), $request_search);
        }

        $request->merge($request_search);

        $service = new OrderService($request);


        $errors = $service->validateErrors()->getErrors();

        $sum = $service->search()->setNeedleUser($request->user_id)->getOrderSum();

        if ($sum > Auth::user()->getBalance('customer')) {
            $errors[] = 'Сумма вашего заказа ' . $sum / 100 . ' руб. Система зарезервирует 100% сумму. Ваш баланс ' . Auth::user()->getBalance('customer') / 100 . ' руб. Пополните Ваш баланс.';
            $errors['hold'] = view('proposal.payment.hold', ['request' => $request, 'type' => 'invite', 'sum' => $sum])->render();

        }

        if ($errors) return response()->json($errors, 419);


        //  $this->acceptInvite($request, $invite, $proposal);

        DB::beginTransaction();

        $proposal = $service->forUser(Auth::id())->createOrder()->created_proposal;

        DB::commit();

        return response()->json([
            'message' => 'Заказ создан. Сумма вашего заказа ' . $proposal->sum / 100 . ' руб. Система зарезервирует 100% сумму. Ваш баланс ' . Auth::user()->getBalance('customer') . ' руб'
        ]);
    }

    function acceptInvite(Request $request, $invite, $proposal)
    {


        $machine = $this->checkFreeMachineDays($proposal, $invite->machine_id);

        if ($proposal->status !== Proposal::status('open')) {
            return response()->json(['errors' => ['Заявка уже закрыта']], 419);
        }


        $offer = Offer::create([
            'user_id' => $machine->user->id,
            'proposal_id' => $proposal->id,
            'machine_id' => $machine->id,
            'sum' => $proposal->sum,
            'comment' => '',
        ]);
        $request->merge(['offer_id' => $offer->id, 'machine_id' => $machine->id]);
        $this->accept($request, true);


        return response()->json([
            'message' => 'Приглашение принято.'
        ]);
    }

    function deleteInvite($id)
    {
        Invite::currentUser()->findOrFail($id)->delete();
    }

}

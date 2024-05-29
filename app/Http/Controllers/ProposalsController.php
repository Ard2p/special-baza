<?php

namespace App\Http\Controllers;

use App\City;
use App\Directories\TransactionType;
use App\Feedback;
use App\Jobs\SendNewProposalNotification;
use App\Machinery;
use App\Machines\Brand;
use App\Machines\Type;

use App\Option;

use App\Service\EventNotifications;
use App\Service\OrderService;
use App\Service\SimpleFormService;
use App\Service\Subscription;
use App\Service\Widget;
use App\Support\Region;
use App\Support\SmsConfirmAction;
use App\Support\SmsNotification;
use App\SystemCashHistory;
use App\User;
use App\User\BalanceHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProposalsController extends Controller
{

    function getRequiredFields()
    {
        return [
            'region' => 'required|integer',
            'city_id' => 'required|integer',
            'date' => 'required|date|after:' . Carbon::now()->subDay(1)->format('Y-m-d'),
            'days' => 'required|integer|min:1|',
            'type' => 'required|integer',
            'address' => 'required|string|max:255',
        ];
    }

    function getRequiredCreateFields()
    {
        return [
            'region' => 'required|integer',
            'city_id' => 'required|integer',
            'date' => 'required|date|after:' . Carbon::now()->subDay(1)->format('Y-m-d'),
            'days' => 'required|integer|min:1|',
            'type' => 'required|integer',
            'address' => 'required|string|max:255',
            'comment' => 'required|string|max:500',
            'sum' => 'required|numeric|min:1',
        ];
    }

    static function getFieldsMessage()
    {
        return [
            'region.required' => trans('transbaza_proposal.validate_region'),
            'region.integer' => trans('transbaza_proposal.validate_region'),
            'city_id.integer' => trans('transbaza_proposal.validate_city'),
            'date.required' => trans('transbaza_proposal.validate_date'),
            'time.required' => trans('transbaza_proposal.validate_time'),
            'date.date' => trans('transbaza_proposal.validate_date_format'),
            'date.date_format' => trans('transbaza_proposal.validate_date_format'),
            'date.after' => trans('transbaza_proposal.validate_date_after'),
            'days.required' => trans('transbaza_proposal.validate_days'),
            'days.integer' => trans('transbaza_proposal.validate_days_int'),
            'days.min' => trans('transbaza_proposal.validate_days_min'),
            'type.integer|required' => trans('transbaza_proposal.validate_type'),
            'address.required' => trans('transbaza_proposal.validate_address'),
            'comment.required' => trans('transbaza_proposal.validate_comment'),
            'comment.max' => trans('transbaza_proposal.validate_comment_max'),
            'sum.min' => trans('transbaza_proposal.validate_sum_min'),
            'sum.numeric' => trans('transbaza_proposal.validate_sum_numeric'),
            'time_type.integer' => trans('transbaza_proposal.validate_time_type'),
            'sum.required' => trans('transbaza_proposal.validate_sum'),
            'phone.required' => trans('transbaza_proposal.validate_phone'),
            'phone.integer' => trans('transbaza_proposal.validate_phone'),
            'phone.min' => trans('transbaza_proposal.validate_phone'),
            'email.integer' => trans('transbaza_proposal.validate_email'),
            'email.email' => trans('transbaza_proposal.validate_email'),
            'city_id.required' => trans('transbaza_proposal.validate_city'),
            'type.integer' => trans('transbaza_proposal.validate_type'),
            'type.required' => trans('transbaza_proposal.validate_type'),
            'amount.min' => trans('transbaza_proposal.validate_amount'),
            'amount.integer' => '',
        ];
    }

    function getRentFields($request)
    {

        $rules = [
            'customer_type' => 'required|in:old,new',
            'machine_id' => 'required|exists:machineries,id',
            //  'date' => 'required|date|date_format:Y/m/d|after:' . Carbon::now()->subDay(1)->format('Y-m-d'),
            'time' => 'required|date_format:H:i',
            'address' => 'required|string|max:255',
            'comment' => 'required|string|max:500',
            'sum' => 'required|numeric|min:1',
            'amount' => 'integer|min:1',
            'time_type' => 'integer|min:0',
            'email' => 'required|email',
        ];
        $needle = [];
        if ($request->customer_type === 'new') {

            $needle = ['name' => 'required|string', 'phone' => 'required|int|min:11'];
        }

        return array_merge($rules, $needle);
    }


    function rentProposal(Request $request, $key = null)
    {
        $machine = Machinery::find($request->machine_id);
        if (!$machine) {
            return response()->json(['machine_id' => ['Выберите технику']], 419);
        }
        if (!$request->filled('customer_type')) {
            $cst_type = 'customer_type_' . $request->machine_id;
            $request->merge(
                [
                    'customer_type' => $request->$cst_type
                ]);
        } else {
            $cst_type = $request->customer_type;
        }

        $rules = $this->getRentFields($request);

        if(auth()->check()){
            unset($rules['email']);
            unset($rules['customer_type']);
            if (isset($rules['phone'])){
                unset($rules['phone']);
            }
        }

        $request->merge([
            'type' => $machine->type,
            'machine_type' => $machine->machine_type,
            'region' => $machine->region_id,
            'city_id' => $machine->city_id,
            'type_' => $machine->type,
            'customer_type' => $request->customer_type ?: $request->$cst_type
        ]);
        $service = new OrderService($request);


        $errors = $service->mergeRequest($rules)->setDaysByAmount()->validateErrors()->getErrors();

        if ($errors) return response()->json($errors, 419);


        $service->search();

        if (Carbon::now() > $service->getStartDate()) {
            $errors[] = ['date' => ['Дата не может быть раньше, чем сегодня.']];
        }


        if ($errors) return response()->json($errors, 419);

        $email = $request->email;
        $phone = $request->phone;

        $widget = Widget::whereAccessKey($request->widget_key)->first();

        DB::beginTransaction();

        if (\auth()->check()) {
            $user = Auth::user(); //User::whereEmail($email)->first();
         /*   if (!$user) {
                return response()->json(['email' => [['Пользователь не найден.']]], 419);
            }*/
        } else {
            $user = User::whereEmail($email)->first();
            $user_phone = User::wherePhone($phone)->first();
            if (!$user && !$user_phone) {
                $user = User::register($email, $phone);
            } else {
                return response()->json(['email' => [['Такой пользователь уже есть в системе. Авторизируйтесь в системе.']]], 419);
            }

        }
        $request = $request->all();

        if ($errors) return response()->json($errors, 419);

        $service->forUser($user->id)->createProposal('open', $request['sum']);


        $proposal = $service->created_proposal;
        $collected_data = collect([
            [
                'type' => $machine->_type->id,
                'id' => $machine->id,
                'another_price' => false,
            ]
        ]);
        $offer = Offer::addOffer($proposal, $collected_data, $machine->user);

        if($offer
            && ($offer->sum <= $user->getBalance('customer'))
            && (($machine->sum_day * $service->created_proposal->days) <= $service->created_proposal->sum))
        {
            $result = $service->setOffer($offer)->forUser($user->id)->acceptOffer()->getErrors();
        }
        DB::commit();
        if ($widget) {
            $widget_proposal = Proposal\WidgetProposal::create([
                'proposal_id' => $service->created_proposal->id,
                'name' => $request['name'],
                'promo' => $request['promo'] ?? '',
                'widget_id' => $widget->id,
                'new_user' => ($request['customer_type'] === 'old' ? 0 : 1),
                'commission' => Option::get('widget_commission'),
            ]);
        }


        (new EventNotifications())->newProposal($service->created_proposal);

        return response()->json([
            'status' => 'success',
            'id' => $service->created_proposal->id,
            'message' => 'Заявка отправлена!'
        ]);
    }


    function newProposalForm(Request $request)
    {


        $service = new OrderService($request);

        if ($service->validateErrors()->getErrors()) return response()->json($service->getErrors(), 419);


        $needleTypes = $service->search()->getSearchContainer()->pluck('id');
        $arr = collect([]);
        foreach ($needleTypes as $type) {
            $arr->push(Type::findOrFail($type));
        }
        $types = implode(' ', $arr->pluck('name')->toArray());

//        return view('proposal.index', ['request' => $request]);
        return view('user.customer.proposal.create', ['request' => $request, 'types' => $types, 'service' => $service]);
    }

    function store(Request $request)
    {

        $service = new OrderService($request);

        $errors = $service->validateErrors()->getErrors();

        if ($request->sum <= 0) {
            $errors['sum'] = [['Бюджет не может быть меньше нуля']];
        }

        if ($errors) return response()->json($errors, 419);
        DB::beginTransaction();

        $service->search()->setProposalSum($request->sum)->forUser(Auth::id())->createProposal('open');

        DB::commit();

        //  (new EventNotifications())->newProposal($service->created_proposal);

        return response()->json([
            'status' => 'success',
            'id' => $service->created_proposal->id
        ]);

    }


    function index(Request $request)
    {
        $proposals = Proposal::forPublic();

        if ($request->has('filters')) {
            $proposals->modifySearch($request);
        }

        $proposals = $proposals->orderBy('created_at', 'DESC')->paginate(20);
        $search_inputs = $request->all();
        $searches = $this->getSearches();
        return view('proposal.list', compact('proposals', 'search_inputs', 'searches'));
    }


    function fire_proposals(Request $request)
    {
        if ($request->has('filters')) {
            $proposals = Proposal::checkFire()
                ->modifySearch($request)
                ->orderBy('created_at', 'DESC')
                ->get();
        } else {
            $proposals = Proposal::checkFire()->orderBy('created_at', 'DESC')->get();
        }
        $search_inputs = $request->all();
        $searches = $this->getSearches();

        return view('proposal.list_fire', compact('proposals', 'search_inputs', 'searches'));
    }

    function index_orders(Request $request)
    {
        if ($request->has('filters')) {
            $proposals = Proposal::contractorOrders()
                ->modifySearch($request)
                ->orderBy('created_at', 'DESC')
                ->get();
        } else {
            $proposals = Proposal::contractorOrders()->orderBy('created_at', 'DESC')->get();
        }
        $search_inputs = $request->all();
        $searches = $this->getSearches();
        return view('proposal.list_orders', compact('proposals', 'search_inputs', 'searches'));
    }

    function customerProposals(Request $request)
    {
        if ($request->has('filters')) {
            $proposals = Proposal::currentUser()
                ->modifySearch($request)
                ->onlyProposals()
                ->orderBy('created_at', 'DESC')
                ->get();
        } else {
            $proposals = Proposal::currentUser()->onlyProposals()->orderBy('created_at', 'DESC')->get();
        }
        $search_inputs = $request->all();
        $searches = $this->getSearches();
        return view('proposal.customer_list', compact('proposals', 'search_inputs', 'searches'));
    }

    function getSearches()
    {
        return Auth::check() ? Proposal\ProposalSearchFilters::currentUser()->get() : [];
    }

    function customerOrder(Request $request)
    {
        if ($request->has('filters')) {
            $proposals = Proposal::currentUser()
                ->modifySearch($request)
                ->onlyOrders()
                ->orderBy('created_at', 'DESC')
                ->get();
        } else {
            $proposals = Proposal::currentUser()
                ->onlyOrders()
                ->orderBy('created_at', 'DESC')
                ->get();
        }
        $search_inputs = $request->all();
        $searches = $this->getSearches();
        return view('proposal.customer_order_list', compact('proposals', 'search_inputs', 'searches'));
    }

    function show($id)
    {
        $proposal = Proposal::with('offers')->findOrFail($id);

        $machines = Auth::check()
            ? Auth::user()
                ->machines()
                ->checkProposal($proposal->region_id, $proposal->type_ids)
                ->get()
            : [];


        foreach ($machines as $machine) {
            $current_sum = $machine->sum_day / 100 * $proposal->days;
            /* $rep_commission = ($machine->regional_representative && $machine->regional_representative->commission->enable ?? false)
                                 ? (($representative_commission > $machine->regional_representative->commission->percent)
                                     ? $machine->regional_representative->commission->percent
                                     : $representative_commission)
                                 : $representative_commission;*/
            $system_commission = $proposal->system_commission / 100 + $proposal->getWidgetCommission();
            $machine->contractor_sum = $current_sum - ($current_sum * $system_commission / 100);
            $machine->another_price = false;
            $machine->another_sum = $machine->sum_day / 100 * $proposal->days;
        }

        if ($proposal->status == Proposal::status('fire')) {

            return view('proposal.fire', compact('proposal', 'machines'));
        }

        return view($proposal->isAcceptOrDone() ? 'proposal.order' : 'proposal.show', [
            'proposal' => $proposal,
            'customerFeedback' => $proposal->getCustomerFeedback(),
            'performerFeedback' => $proposal->getPerformerFeedback(),
            'machines' => $machines,
            'system_commission' => (Option::find('system_commission')->value ?? 0) / 100,
            'representative_commission' => (Option::find('representative_commission')->value ?? 0) / 100,
            'invite' => Auth::check() ? $proposal->invites()->where('user_id', Auth::user()->id)->first() : false
        ]);

    }

    function done(Request $request)
    {
        $user = Auth::user();

        $errors = Validator::make($request->all(), Proposal::$doneFields,
            [
                'rate.required' => 'Поставьте оценку отзыву.',
                'comment.required' => 'Заполните отзыв.',
                'comment.min' => 'Минимальный размер комментария 10 символов.',
            ])
            ->errors()
            ->all();
        /*if (!$requisite = $user->getActiveRequisite()) {
            $errors[] = 'Заполните пожалуйста реквзиты!';
        }*/
        if ($errors) return response()->json($errors, 419);


        $proposal = $user->isContractor()
            ? Proposal::checkAcceptedOrDone()->whereHas('offers', function ($q) use ($user) {
                $q->where('user_id', $user->id);
                $q->where('is_win', 1);
            })->findOrFail($request->input('proposal_id'))

            : Proposal::where('status', Proposal::status('accept'))->currentUser()
                ->findOrFail($request->input('proposal_id'));

        if ($proposal->contractor_timestamps->winner_steps !== 3) {
            return response()->json(['modals' => ['Невозможно закрыть в данный момент.']], 419);
        }


        $customer = ($proposal->user_id === $user->id) ? true : false;

        if ($customer) {
            $winner = Offer::getWinner($proposal->id);

            DB::transaction(function () use ($user, $request, $winner, $proposal) {
                $real_balance = $proposal->user->hasRealBalance() ? true : false;
                Feedback::create([
                    'user_id' => $user->id,
                    'is_performer' => 0,
                    'proposal_id' => $proposal->id,
                    'rate' => $request->input('rate'),
                    'comment' => $request->input('comment'),
                ]);
                BalanceHistory::create([
                    'user_id' => $winner->id,
                    'admin_id' => 0,
                    'old_sum' => $winner->getBalance('contractor'),
                    'new_sum' => $winner->getBalance('contractor') + $proposal->calculateWinnerSum(),
                    'type' => BalanceHistory::getTypeKey('reward'),
                    'billing_type' => 'contractor',
                    'sum' => $proposal->calculateWinnerSum(),
                    'reason' => TransactionType::getTypeLng('reward') . ' #' . $proposal->id,
                ]);

                $current_system_cash = SystemCashHistory::getCurrentCash();
                SystemCashHistory::create([
                    'old_sum' => $current_system_cash,
                    'new_sum' => $current_system_cash + $proposal->calculateSystemCommission(),
                    'sum' => $proposal->calculateSystemCommission(),
                    'type' => 0,
                    'reason' => 'Выполненый заказ' . ' #' . $proposal->id,
                ]);
                SystemCashHistory::incrementCash($proposal->calculateSystemCommission());

                if ($proposal->regional_representative) {

                    $current_system_cash = SystemCashHistory::getCurrentCash();
                    SystemCashHistory::create([
                        'old_sum' => $current_system_cash,
                        'new_sum' => $current_system_cash - $proposal->calculateRepresentativePercent(),
                        'sum' => $proposal->calculateRepresentativePercent(),
                        'type' => 0,
                        'reason' => 'Начисление % РП за заказ' . ' #' . $proposal->id,
                    ]);
                    SystemCashHistory::decrementCash($proposal->calculateRepresentativePercent());

                    BalanceHistory::create([
                        'user_id' => $proposal->regional_representative->id,
                        'admin_id' => 0,
                        'old_sum' => $proposal->regional_representative->getBalance('contractor'),
                        'new_sum' => $proposal->regional_representative->getBalance('contractor') + $proposal->calculateRepresentativePercent(),
                        'type' => BalanceHistory::getTypeKey('reward'),
                        'billing_type' => 'contractor',
                        'sum' => $proposal->calculateRepresentativePercent(),
                        'reason' => TransactionType::getTypeLng('representative_commission') . ' #' . $proposal->id,
                    ]);

                    $proposal->regional_representative->incrementContractorBalance($proposal->calculateRepresentativePercent());
                    if ($real_balance) {
                        $proposal->regional_representative->setRealBalance('contractor');
                    }
                    $proposal->regional_representative->sendSmsNotification(SmsNotification::buildIncrementBalanceText($proposal->regional_representative, 'contractor', $proposal->calculateRepresentativePercent()));
                }

                if ($proposal->from_widget) {


                    BalanceHistory::create([
                        'user_id' => $proposal->from_widget
                            ->widget
                            ->user->id,
                        'admin_id' => 0,
                        'old_sum' => $proposal->regional_representative->getBalance('widget'),
                        'new_sum' => $proposal->regional_representative->getBalance('widget') + $proposal->calculateWidgetCommission(),
                        'type' => BalanceHistory::getTypeKey('reward'),
                        'billing_type' => 'widget',
                        'sum' => $proposal->calculateWidgetCommission(),
                        'reason' => 'Заявка' . ' #' . $proposal->id,
                    ]);

                    $proposal->from_widget
                        ->widget
                        ->user
                        ->incrementWidgetBalance($proposal->calculateWidgetCommission());
                    if ($real_balance) {
                        $proposal->from_widget
                            ->widget
                            ->user->setRealBalance('widget');
                    }
                }


                $winner->incrementContractorBalance($proposal->calculateWinnerSum());
                if ($real_balance) {
                    $winner->setRealBalance('contractor');
                }


                $proposal->status = Proposal::status('done');
                $proposal->save();


                (new EventNotifications())->doneOrder($proposal);


            });
        } elseif (!Feedback::checkUnique($user->id, $proposal->id)->first()) {

            Offer::checkWinner($proposal->id)->firstOrFail();

            Feedback::create([
                'user_id' => $user->id,
                'proposal_id' => $proposal->id,
                'is_performer' => 1,
                'rate' => $request->input('rate'),
                'comment' => $request->input('comment'),
            ]);
        }

        return response()->json(['message' => 'Отзыв добавлен.']);

    }


    public function destroy($id)
    {
        $proposal = Proposal::checkAvailableOrFire()->currentUser()->findOrFail($id);
        DB::transaction(function () use ($proposal) {
            if ($proposal->status == Proposal::status('fire')) {
                BalanceHistory::create([
                    'user_id' => Auth::user()->id,
                    'admin_id' => 0,
                    'old_sum' => Auth::user()->getBalance('customer'),
                    'new_sum' => Auth::user()->getBalance('customer') + $proposal->sum,
                    'type' => BalanceHistory::getTypeKey('return_reserve'),
                    'billing_type' => 'customer',
                    'sum' => $proposal->sum,
                    'reason' => TransactionType::getTypeLng('return_reserve') . ' Заказ #' . $proposal->id,
                ]);
                Auth::user()->incrementCustomerBalance($proposal->sum);
            }


            $proposal->invites()->delete();
            $proposal->offers()->delete();
            $proposal->types()->detach();
            $proposal->delete();
        });

        return response()->json(['message' => 'Успешно удалено']);
    }

    function refuseProposal(Request $request)
    {
        $id = $request->input('id');

        $proposal = Proposal::checkAccepted()
            ->winnerOrCustomer()->findOrFail($id);

        if(Auth::user()->phone_confirm){
            $sms = SmsConfirmAction::where('model', Proposal::class)->where('action', "delete_{$id}")->whereUserId(Auth::id())->first();

            if(!$sms){
                $sms = SmsConfirmAction::create([
                    'model' => Proposal::class,
                    'action' => "delete_{$id}",
                    'user_id' => \auth()->id(),
                    'code' => random_int(1000, 9999),
                ]);
            }

            if(!$request->filled('sms')){

                return response()->json(['needToken' => 'На ваш номер был отправлен код подтверждения.']);

            }else{
                if(!$sms->isCorrect($request->sms)){

                    return response()->json(['needToken' => 'Некорректный код. Попробуйте еще раз.']);
                }
            }
        }


        DB::beginTransaction();


        $proposal->refusing();

        if($sms){
            $sms->delete();
        }

        if ($proposal->hold_payment) {
            $proposal->hold_payment->refuse();
        }
        DB::commit();


        return response()->json(['message' => 'Вы отказались от заказа.']);
    }

    function changeContractorState(Request $request)
    {
     /*   $id = $request->input('id');
        $type = $request->input('type');

        $proposal = Proposal::checkAccepted()->findOrFail($id);

        if ($proposal->isWinnerCurrentUser()) {
            $contractor_status = $proposal->contractor_timestamps;
            $state = false;
            switch ($type) {
                case 'machinery_ready':
                    if ($contractor_status->winner_steps == 0) {
                        $contractor_status->machinery_ready = Carbon::now();
                        $contractor_status->winner_steps = 1;

                        $state = true;
                    }
                    break;
                case 'machinery_on_site':
                    if ($contractor_status->winner_steps == 1) {
                        $contractor_status->machinery_on_site = Carbon::now();
                        $contractor_status->winner_steps = 2;

                        $state = true;
                    }
                    break;
                case 'end_of_work':
                    if ($contractor_status->winner_steps == 2) {
                        $contractor_status->end_of_work = Carbon::now();
                        $contractor_status->winner_steps = 3;

                        $state = true;
                    }
                    break;
            }
            if ($state) {
                $contractor_status->save();
                return response()->json(['message' => 'Успешно']);
            }
            return response()->json(['message' => 'Ошибка'], 419);
        }*/
    }

    function createFromSimple(Request $request)
    {
        $service = new SimpleFormService($request);
        $errors = $service->acceptSimpleForm()->getErrors();
        if ($errors) return response()->json($errors, 419);

        if ($service->created_user) {
            (new Subscription())->newUserFromForm($service->created_user, $service->created_user_password);
        }

        (new Subscription())->newSubmitSimpleForm($service->getSubmitForm(), $service->created_user ? true : false);
        return response()->json(['message' => 'Заявка отправлена!']);
    }

    function machineryState(Request $request, $proposal_id, $machine_id)
    {
        $proposal = Proposal::checkAccepted()->findOrFail($proposal_id);

        if (!$proposal->isWinnerCurrentUser()) {
            return response()->json(['error'], 400);
        }

        $machine = $proposal->winner_offer->machines()->findOrFail($machine_id);

        $errors = Validator::make($request->all(), ['step' => 'required|in:1,2,3'])->errors()->getMessages();
      //  dd($errors);

        if ($errors){

            return redirect()->back();
        }

        $result = $proposal->addMachineryCoordinates($machine_id, $request->step);

        if(!$result){
            return redirect()->back();
        }

        return redirect()->back();
    }

}

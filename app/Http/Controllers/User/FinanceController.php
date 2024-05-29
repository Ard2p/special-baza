<?php

namespace App\Http\Controllers\User;

use App\Directories\TransactionType;
use App\Finance\FinanceTransaction;
use App\Finance\HoldPayment;
use App\Finance\Payment;
use App\Service\AlphaBank;
use App\Service\OrderService;
use App\Service\Subscription;
use App\User\BalanceHistory;
use App\User\IndividualRequisite;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FinanceController extends Controller
{


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    function index(Request $request)
    {

        if ($request->filled('orderId') && !$request->filled('failPayment')) {
            $payment = Payment::whereUserId(Auth::user()->id)
                ->whereOrderId($request->orderId)->first();

            if ($payment && $payment->status === 0) {
                \Session::flash('success_payment', 'Оплата в обработке. В течении нескольких минут Ваш счет будет пополнен.');
            }
        }
        if ($request->filled('failPayment')) {
            \Session::flash('fail_payment', 'Произшла ошибка при оплате.');
        }
        $balances = BalanceHistory::where('user_id', Auth::user()->id)
            ->where('billing_type', ($request->filled('__webwidget') ? 'widget' : Auth::user()->getCurrentRoleName()))
            ->orderBy('created_at', 'DESC')
            ->count();

        $balances_wait = FinanceTransaction::where('user_id', Auth::user()->id)
            ->where('balance_type', ($request->filled('__webwidget') ? 2 : Auth::user()->current_role))
            ->orderBy('created_at', 'DESC')
            ->count();

        return ($request->filled('__webwidget')
            ? view('widget.balance', compact('balances', 'balances_wait'))
            : view('user.customer.balance', compact('balances', 'balances_wait')));
    }

    function balanceHistoryTable(Request $request)
    {
        $balances = BalanceHistory::where('user_id', Auth::user()->id)
            ->where('billing_type', ($request->filled('__webwidget') ? 'widget' : Auth::user()->getCurrentRoleName()))
            ->withFilters($request)
            ->orderBy('created_at')
            ->get();
        $i = 0;
        $max = $balances->count();

        foreach ($balances as $balance) {
            ++$i;
            if ($i == 1) {
                $period_start = number_format($balance->old_sum / 100, 2, ',', ' ');
            }
            if ($i == $max) {
                $period_end = number_format($balance->new_sum / 100, 2, ',', ' ');
            }
            //   $balance->old_sum = $balance->old_sum / 100;

            //     $balance->sum = $balance->sum / 100;

            $balance->refill = ($balance->new_sum > $balance->old_sum) ? number_format($balance->sum / 100, 2, ',', ' ') : '';

            $balance->withdrawal = ($balance->new_sum < $balance->old_sum) ? number_format($balance->sum / 100, 2, ',', ' ') : '';


            if (!$balance->refill && !$balance->withdrawal) {

                $balances->forget($balance->id);
                //   $max = -1;
                continue;
            }

            $balance->billing_type = ($request->filled('__webwidget') ? 'Виджет' : (($balance->billing_type === 'customer') ? 'Заказчик' : 'Исполнитель'));
            $balance->admin = ($balance->admin) ? '#' . $balance->admin->id : 'Система';
            $balance->created_at;
        }

        return response()->json([
            'data' => $balances->values()->all(),
            'period_start' => $period_start ?? 0,
            'period_end' => $period_end ?? 0,
            'count' => $balances->count()
        ]);
    }

    function transactionsHistoryTable(Request $request)
    {
        $is_widget = $request->filled('__webwidget');
        $balances_wait = FinanceTransaction::where('user_id', Auth::user()->id)
            ->where('balance_type',  $is_widget ? 2 : Auth::user()->current_role)
            ->withFilters($request)
            ->orderBy('created_at', 'DESC')
            ->get();

        foreach ($balances_wait as $balance) {
            $balance->type = $balance->type ? 'Вывод денег со счета' : 'Пополнение счета';
            $balance->balance_type =  $is_widget
                ? 'Виджет #' . Auth::user()->id
                :  (Auth::user()->current_role
                    ? 'Исполнитель #' . Auth::user()->id
                    : 'Заказчик #' . Auth::user()->id);


            $balance->status = $balance->status_lng($balance->status);
            $balance->_admin = ($balance->admin) ? 'Администратор #' . $balance->admin->id : 'Система';
            $balance->created_at;
        }

        return response()->json(['data' => $balances_wait->values()->all(), 'count' => $balances_wait->count()]);
    }

    public function store(Request $request)
    {
        $data = $request->all();


        $errors = Validator::make($data, FinanceTransaction::getRequiredFields($data))
            ->errors()
            ->getMessages();
        if ($errors) return response()->json($errors, 419);

        $sum = round(str_replace(',', '.', $data['sum']) * 100);
        if ($request['type'] === 'out' && ($sum > Auth::user()->getCurrentBalance())) {
            $errors['modals'] = ['Ошибка. Сумма превышает баланс'];
        }
        if ($errors) return response()->json($errors, 419);

        $card_payment = ($data['transaction_type'] === 'card_payment');
        $account_payment = ($data['transaction_type'] === 'account_payment');


        if ($account_payment && !Auth::user()->getActiveRequisite('customer')) {
            $errors['modals'] = [''];
            return response()->json(['modals' => ['Ошибка. Не заполнены реквизиты ЗАКАЗЧИКА.']], 419);
        }

        DB::beginTransaction();


        $transaction = FinanceTransaction::create([
            'user_id' => Auth::user()->id,
            'type' => FinanceTransaction::type($data['type']),
            'sum' => $sum,
            'balance_type' => Auth::user()->current_role,
            /*'requisites_id' => $requisite->id,
            'requisites_type' => Auth::user()->getActiveRequisiteType(),*/
        ]);

        if ($data['type'] === 'in') {

            if (Auth::user()->getCurrentRoleName() !== 'customer') {
                DB::rollBack();
                return response()->json(['modals' => ['Текущая роль должна быть ЗАКАЗЧИК.']], 419);
            }
            if ($card_payment) {
                $AlphaBank = new AlphaBank(route('balance_page', 'customer'), route('balance_page', 'customer') . '?failPayment=1');
                $user = Auth::user();
                $response = $AlphaBank->registerPayment([
                    'orderNumber' => urlencode($transaction->id),
                    'amount' => urlencode($sum),
                    'jsonParams' => json_encode(['email' => $user->email, 'phone' => $user->phone]),
                    'client_id' => urlencode(Auth::user()->id),
                    'description' => "TRANSBAZA пополнение счета. Пользователь #{$user->id}",
                ]);

                $transaction->update([
                    'card_payment' => 1
                ]);

                if (isset($response['formUrl'])) {

                    Payment::create([
                        'user_id' => Auth::user()->id,
                        'order_id' => $response['orderId'],
                        'finance_transaction_id' => $transaction->id,
                        'status' => 0,
                        'response' => json_encode($response)
                    ]);

                    DB::commit();
                    return response()->json([
                        'formUrl' => $response['formUrl'],
                        'message' => 'Сейчас Вы будете перенаправлены на страницу оплаты.'
                    ]);
                }
                DB::rollBack();
                return response()->json(['sum' => [$response['errorMessage'] ?? '']], 500);
            }

            if ($account_payment) {
                (new Subscription())->newAccountPaymentNotification($transaction);
            }

        }


        if ($data['type'] === 'out') {
            $old = Auth::user()->getCurrentBalance();
            $billing = Auth::user()->getCurrentRoleName();

            if (Auth::user()->isCustomer()) {
                Auth::user()->decrementCustomerBalance($sum);
            } else {
                Auth::user()->decrementContractorBalance($sum);
            }

            BalanceHistory::create([
                'user_id' => Auth::user()->id,
                'admin_id' => 0,
                'old_sum' => $old,
                'new_sum' => Auth::user()->getCurrentBalance(),
                'type' => BalanceHistory::getTypeKey('reserve'),
                /*     'requisite_id' => $requisite->id,
                     'requisite_type' => Auth::user()->getActiveRequisiteType(),*/
                'sum' => $sum,
                'reason' => TransactionType::getTypeLng('reserve'),
                'billing_type' => $billing,
            ]);
        }

        DB::commit();

        return response()->json(['message' => 'Транзакция отправлена в обработку']);

    }

    function reserveForProposal(Request $request)
    {
     /*   $request_search = $request->input('request');

        if (!is_array($request_search)) {
            parse_str($request->input('request'), $request_search);
        }*/

        $request->merge(json_decode($request->input('request'), true));


        $service = new OrderService($request);

        $service->mergeRequest(['hold_from' => 'required|in:invite,proposal']);

        $errors = $service->validateErrors()->getErrors();

        $sum = $service->search()->setNeedleUser($request->user_id)->getOrderSum();
        if (Auth::user()->getCurrentRoleName() !== 'customer') {

            return $errors['modals'] = ['Текущая роль должна быть ЗАКАЗЧИК.'];
        }
        if ($sum <= Auth::user()->getBalance('customer')) {
            $errors[] = 'Ошибка. Данные не актуальны. Попробуйте выполнить поиск еще раз.';
        }

        if ($errors) return response()->json($errors, 419);

        DB::beginTransaction();

        $transaction = FinanceTransaction::create([
            'user_id' => Auth::user()->id,
            'type' => FinanceTransaction::type('in'),
            'sum' => $sum,
            'balance_type' => Auth::user()->current_role,
            'card_payment' => 1
        ]);

        $AlphaBank = new AlphaBank(route('balance_page', 'customer'), route('balance_page', 'customer') . '?failPayment=1');
        $user = Auth::user();
        $response = $AlphaBank->registerPreAuth([
            'orderNumber' => urlencode($transaction->id),
            'amount' => urlencode($sum),
            'jsonParams' => json_encode(['email' => $user->email, 'phone' => $user->phone]),
            'client_id' => urlencode(Auth::user()->id),
            'description' => "HOLD средств #{$user->id}",
           // 'autocompletionDate' => $service->getEndDate()->addDay(1)->format('c')
        ]);
        if (isset($response['formUrl'])) {

            HoldPayment::create([
                'user_id' => Auth::user()->id,
                'order_id' => $response['orderId'],
                'finance_transaction_id' => $transaction->id,
                'status' => 0,
                'request_params' => json_encode($request->except('_token', 'request')),
                'response' => json_encode($response)
            ]);

            DB::commit();
            return response()->json([
                'formUrl' => $response['formUrl'],
                'message' => 'Сейчас Вы будете перенаправлены на страницу оплаты.'
            ]);
        }
        DB::rollBack();
        return response()->json(['sum' => [$response['errorMessage'] ?? '']], 500);
    }

    function getBalance()
    {
        return Auth::user()->getCurrentBalance(true);
    }


}

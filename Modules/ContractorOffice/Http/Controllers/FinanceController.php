<?php

namespace Modules\ContractorOffice\Http\Controllers;

use App\Directories\TransactionType;
use App\Finance\FinanceTransaction;
use App\User\BalanceHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FinanceController extends Controller
{
    function getTransactions(Request $request)
    {

        $transactions = FinanceTransaction::where('user_id', Auth::id())
            ->where('balance_type', 1)
            ->orderBy('created_at', 'DESC')
            ->paginate(10);

        return $transactions;

    }

    function getBalanceHistories(Request $request)
    {

        $balances = BalanceHistory::where('user_id', Auth::id())
            ->where('billing_type', 'contractor')
            ->orderBy('created_at', 'DESC')
            ->paginate(10);

        return $balances;
    }

    function withdraw(Request $request)
    {
        $data = $request->all();


        $errors = Validator::make($data, [
            'sum' => 'required|numeric|min:1'
        ])
            ->errors()
            ->getMessages();
        if ($errors) return response()->json($errors, 400);

        $sum = round(str_replace(',', '.', $data['sum']) * 100);

        DB::beginTransaction();

        $current_balance = Auth::user()->getBalance('contractor');
        if ($sum > Auth::user()->getBalance('contractor')) {
            $errors['sum'] = ['Ошибка. Сумма превышает баланс'];
        }

        if ($errors) return response()->json($errors, 400);




        FinanceTransaction::create([
            'user_id' => Auth::user()->id,
            'type' => FinanceTransaction::type('out'),
            'sum' => $sum,
            'balance_type' => 1,
        ]);

        Auth::user()->decrementContractorBalance($sum);

        BalanceHistory::create([
            'user_id' => Auth::user()->id,
            'admin_id' => 0,
            'old_sum' => $current_balance,
            'new_sum' => $current_balance - $sum,
            'type' => BalanceHistory::getTypeKey('reserve'),
            'sum' => $sum,
            'reason' => TransactionType::getTypeLng('reserve'),
            'billing_type' => 'contractor',
        ]);

        DB::commit();


        return response()->json();

    }
}

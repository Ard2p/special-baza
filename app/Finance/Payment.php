<?php

namespace App\Finance;

use App\Overrides\Model;

class Payment extends Model
{

    protected $table = 'alpha_payments';
    const STATUS = [
        'Заказ зарегистрирован, но не оплачен',
        'Предавторизованная сумма захолдирована (для двухстадийных платежей)',
        'Проведена полная авторизация суммы заказа',
        'Авторизация отменена',
        'По транзакции была проведена операция возврата',
        'Инициирована авторизация через ACS банка-эмитента',
        'Авторизация отклонена',
    ];

    protected $fillable = [
        'user_id', 'order_id', 'finance_transaction_id',
        'status', 'response',
    ];

    function finance_transaction()
    {
        return $this->belongsTo(FinanceTransaction::class);
    }
}

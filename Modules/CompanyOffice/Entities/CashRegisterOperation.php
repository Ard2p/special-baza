<?php

namespace Modules\CompanyOffice\Entities;

use App\User;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Builder;
use App\Overrides\Model;
use Modules\CompanyOffice\Entities\Company\ClientBankSetting;
use Modules\CompanyOffice\Filters\CashRegisterFilter;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\CompanyOffice\Services\HasManager;
use Modules\ContractorOffice\Entities\Vehicle\MachineryBase;
use Modules\Dispatcher\Entities\DispatcherInvoice;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\Payments\InvoicePay;

class CashRegisterOperation extends Model
{

    public const TYPE_PAY_FROM_BANK = 1;
    public const TYPE_CREATE_INVOICE_PAY = 2;
    public const TYPE_INVOICE_DISTRIBUTION = 3;

    protected $table = 'company_cash_register_operations';

    protected $fillable = [
        'company_cash_register_id',
        'sum',
        'type',
        'invoice_pay_id',
        'client_bank_setting_id',
        'request',
    ];

    protected $casts = [
        'request' => 'json'
    ];

    function company_cash_register()
    {
        return $this->belongsTo(CashRegister::class, 'company_cash_register_id');
    }

    function invoice_pay()
    {
        return $this->belongsTo(InvoicePay::class);
    }

    function client_bank_setting()
    {
        return $this->belongsTo(ClientBankSetting::class);
    }
}

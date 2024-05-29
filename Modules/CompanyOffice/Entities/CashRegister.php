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

class CashRegister extends Model
{
    use BelongsToCompanyBranch, HasManager, Filterable;

    protected $table = 'company_cash_registers';

    protected $fillable = [
        'sum',
        'stock',
        'type',
        'company_branch_id',
        'machinery_base_id',
        'expenditure_id',
        'invoice_pay_id',
        'comment',
        'creator_id',
        'datetime',
        'ref',
        'is_clientbank',
        'client_bank_setting_id',
        'vat'
    ];

    protected $casts = [
        'ref' => 'object',
        'is_clientbank' => 'boolean',
        'datetime' => 'datetime',
        'created_at' => 'datetime',
        'vat' => 'int',
    ];

   // protected $appends = ['order_manager'];

    public function getModelFilterClass()
    {
        return CashRegisterFilter::class;
    }

    function machineryBase()
    {
        return $this->belongsTo(MachineryBase::class);
    }

    function client_bank_setting()
    {
        return $this->belongsTo(ClientBankSetting::class);
    }

    function operations()
    {
        return $this->hasMany(CashRegisterOperation::class,'company_cash_register_id','');
    }

    function expenditure()
    {
        return $this->belongsTo(Expenditure::class);
    }

    function scopeWithoutPays(Builder $q)
    {
        return $q->where(function (Builder $cashBuilder)  {
            $cashBuilder->whereNull('invoice_pay_id')
            ->orWhereHas('invoicePay', function (Builder $invoicePayBuilder) use ($cashBuilder) {
                $ct = $cashBuilder->getModel()->getTable();
                $it = $invoicePayBuilder->getModel()->getTable();
                $invoicePayBuilder->whereRaw("`{$it}`.`sum` != `{$ct}`.`sum`");
                $invoicePayBuilder->whereHas('cashRegisters', function (Builder $q) use ($invoicePayBuilder) {

                },'>', 1)
                ->whereHasMorph('invoice', [DispatcherInvoice::class], function (Builder $q) use ($cashBuilder) {

                    $q->whereHasMorph('owner', [Order::class], function (Builder  $q)  use ($cashBuilder){
                        $ct = $cashBuilder->getModel()->getTable();

                     //  $q->whereRaw("`orders`.`machinery_base_id` != `{$ct}`.`machinery_base_id`");
                    });
                })->orWhereHasMorph('invoice', [DispatcherInvoice::class], function (Builder $q) use ($cashBuilder) {

                        $q->where('type', 'pledge');
                    });
            });
        });
    }

    function invoicePay()
    {
        return $this->belongsTo(InvoicePay::class);
    }

    function invoice()
    {
        return $this->hasOneThrough(
            DispatcherInvoice::class,
            InvoicePay::class,
            'id',
            'id',
            'invoice_pay_id',
            'invoice_id'
        );
    }


  // function getOrderManagerAttribute()
  // {
  //     return $this->invoice
  //         ? (User::query()->whereHas('orders', function ($q) {
  //             $q->whereHas('invoices', function ($q) {
  //                 $q->where('dispatcher_invoices.id', $this->invoice->id);
  //             });
  //         }))->first()
  //         : null;
  // }
}

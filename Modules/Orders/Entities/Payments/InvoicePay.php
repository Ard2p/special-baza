<?php

namespace Modules\Orders\Entities\Payments;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Overrides\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\CompanyOffice\Entities\CashRegister;
use Modules\CompanyOffice\Services\HasManager;
use Modules\ContractorOffice\Entities\Vehicle\MachineryBase;
use Modules\ContractorOffice\Entities\Vehicle\Price;
use Modules\Dispatcher\Entities\Lead;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\Service\ServiceCenter;
use Modules\PartsWarehouse\Entities\Shop\Parts\PartsSale;

class InvoicePay extends Model
{

    use HasManager;

    protected $fillable = [
        'type',
        'date',
        'operation',
        'sum',
        'tax_percent',
        'tax',
        'method',
        'integration_ref',
        'invoice_id',
        'invoice_type',
        'creator_id',
        'description',
    ];

    protected $casts = [
        'date' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();

        self::creating(function (self $model) {
            $model->creator_id = \Auth::id();

            return $model;
        });

        self::created(function (self $model) {
            $owner = $model->invoice->owner;
            $comment = '';
            $allBases  = [];
            if ($owner instanceof Order) {

                $defaultBaseId = $owner->machinery_base->id ?? null;

                $groupedPositions = $owner->components->groupBy(function ($position) {
                    return $position->worker->default_base_id ?: 0;
                });
                if(count($groupedPositions) === 1)
                    goto formCash;


                foreach ($groupedPositions as $baseId => $positions) {
                    $posCollection = collect($positions);
                    $sum = $posCollection->sum('total_sum');
                    $base = MachineryBase::query()->find($baseId);
                    if(!$base){
                        $comment = "";
                        goto formCash;
                    }
                    $sumFormat = number_format($sum / 100, 2, ',', ' ');
                    $comment .= "{$base->name} - {$sumFormat}; ";
                    $allBases[$base->id] = $sum;
                }
                if(($defaultBaseId ?? 0) === 0) {
                    throw ValidationException::withMessages([
                        'errors' => 'Не указана база по умолчанию'
                    ]);
                }
            }
            formCash:
            switch (get_class($owner)) {
                case Order::class:
                    $instance = 'order';
                    $name = "Сделка #{$owner->internal_number} {$comment}";
                    $vat = $owner->contractorRequisite?->vat_system === Price::TYPE_CASHLESS_VAT ? 20 : 0;
                    break;
                case Lead::class:
                    $instance = 'lead';
                    $name = "Заявка #{$owner->internal_number}";
                    $owner->update([
                        'tmp_status' => Lead::STATUS_INVOICE
                    ]);
                    $vat = $owner->contractorRequisite?->vat_system === Price::TYPE_CASHLESS_VAT ? 20 : 0;
                    break;
                case ServiceCenter::class:
                    $instance = 'service';
                    $name = "Заказ-наряд #{$owner->internal_number}";
                    $vat = $owner->contractorRequisite?->vat_system === Price::TYPE_CASHLESS_VAT ? 20 : 0;
                    break;
                case PartsSale::class:
                    $instance = 'partSale';
                    $name = "Продажа запчастей #{$owner->internal_number}";
                    break;
                default:
                    $vat = 0;
            }
            $cashRegister = new CashRegister([
                'sum'               => $model->sum,
                'stock'             => $model->type,
                'type'              => $model->operation,
                'company_branch_id' => $owner->company_branch_id,
                'machinery_base_id' => $defaultBaseId ?? null,
                'creator_id'        => \Auth::id(),
                'comment'           => $name,
                'invoice_pay_id'    => $model->id,
                'vat'                => $vat,
                'ref'               => [
                    'id'       => $owner->id,
                    'bases' => $allBases,
                    'method' => $model->method,
                    'instance' => $instance,
                ],
                'created_at' => Carbon::parse($model->date),
                'datetime' => now($model->invoice->company_branch->timezone)
            ]);
            $cashRegister->timestamps = false;
            $cashRegister->created_at = Carbon::parse($model->date);
            $cashRegister->save();
        });
    }

    function invoice()
    {
        return $this->morphTo('invoice');
    }

    function setTaxPercentAttribute($val)
    {
        $this->attributes['tax_percent'] = round($val * 100);
    }

    function getTaxPercentAttribute($val)
    {
        return $val / 100;
    }


    function scopeGetSum(Builder $q)
    {
        $result =
            $q->addSelect(\DB::raw('(SUM(CASE WHEN `operation` = "in" THEN `sum` ELSE 0 END) - SUM(CASE WHEN `operation` = "out" THEN `sum` ELSE 0 END)) as total_sum'))->first();

        return $result->total_sum ?? 0;
    }

    function scopeGetSumVat(Builder $q)
    {
        return (float) CashRegister::query()
            ->where('type', 'in')
            ->whereIn('invoice_pay_id', $q->select('id'))
            ->sum(DB::raw("`sum` / (100 + `vat`) * `vat` "));
      //  $result =
      //      $q->addSelect(\DB::raw('(SUM(CASE WHEN `operation` = "in" THEN `sum` ELSE 0 END) - SUM(CASE WHEN `operation` = "out" THEN `sum` ELSE 0 END)) as total_sum'))->first();

     //   return $result->total_sum ?? 0;
    }
    function cashRegisters()
    {
        return $this->hasMany(CashRegister::class);
    }

}

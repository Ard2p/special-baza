<?php

namespace Modules\Dispatcher\Entities;

use App\Overrides\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\ContractorOffice\Entities\Vehicle\Price;
use Modules\CorpCustomer\Entities\InternationalLegalDetails;
use Modules\Integrations\Services\OneC\OneCService;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderComponent;
use Modules\Orders\Entities\OrderDocument;
use Modules\Orders\Entities\Payments\InvoicePay;
use Modules\Orders\Entities\SystemPayment;
use OwenIt\Auditing\Auditable;

class DispatcherInvoice extends Model implements \OwenIt\Auditing\Contracts\Auditable
{

    use BelongsToCompanyBranch, Auditable;

    /**
     * @var array
     */
    protected $auditInclude = [
        'number',
        'sum',
        'alias',
        'is_paid',
        'paid_sum',
        'use_onec_naming',
        'partial_percent',
        'type',
    ];
    protected $fillable = [
        'number',
        'sum',
        'alias',
        'is_paid',
        'company_branch_id',
        'operation',
        'is_black',
        'paid_sum',
        'onec',
        'owner_type',
        'owner_id',
        'partial_percent',
        'type',
        'onec_release_info',
        'invoice_pay_days_count',
        'paid_date',
    ];

    protected $casts = [
        'is_black' => 'boolean',
        'is_paid' => 'boolean',
        'use_onec_naming' => 'boolean',
        'onec' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'onec_release_info' => 'object',
    ];

    protected $with = ['receivingFromDonor', 'company_branch'];

    protected $appends = ['link', 'paid',
        //'application_id',
        //   'order_data',
        // 'one_c_info'
    ];


    protected static function boot()
    {
        parent::boot();

        self::created(function (self $model) {
            if ($model->owner instanceof Lead) {
                $model->owner->update([
                    'tmp_status' => Lead::STATUS_INVOICE
                ]);

            }
            if ($model->owner instanceof Order) {
                $model->owner->update([
                    'tmp_status' => Lead::STATUS_INVOICE
                ]);

            }
        });
        self::deleting(function (self $item) {
            $item->pays()->delete();
        });

        self::deleted(function (self $item) {
            if ($item->company_branch->OneCConnection && $item->onec) {

                $service = new OneCService($item->company_branch);

                try {
                    $service->markDelete($item->id);
                } catch (\Exception $e) {

                }
            }
        });
    }

    function getPaidDateAttribute($val)
    {
        return $val ? Carbon::parse($val)->format('Y-m-d') : null;
    }

    function setAliasAttribute($val)
    {
        $uid = uniqid();
        $this->attributes['alias'] = md5("{$this->number}-{$uid}");
    }

    function donorTransfers()
    {
        return $this->hasMany(DispatcherInvoiceDepositTransfer::class, 'donor_invoice_id');
    }

    function receivingFromDonor()
    {
        return $this->hasMany(DispatcherInvoiceDepositTransfer::class, 'current_invoice_id');
    }

    public function documents()
    {
        return $this->hasMany(OrderDocument::class);
    }

    function owner()
    {
        return $this->morphTo();
    }

    function system_payment(): MorphOne
    {
        return $this->morphOne(SystemPayment::class, 'owner');
    }

    function pays()
    {
        return $this->morphMany(InvoicePay::class, 'invoice');
    }

    function leadPositions()
    {
        return $this->hasMany(DispatcherInvoiceLeadPivot::class, 'invoice_id');
    }

    function orderComponents()
    {
        return $this->belongsToMany(OrderComponent::class, 'invoice_application_pivot', 'invoice_id')->withPivot([
            'order_duration',
            'order_type',
            'cost_per_unit',
            'delivery_cost',
            'value_added',
            'return_delivery',
            'date_from',
            'date_to',
        ]);
    }

    function getSumAttribute($val)
    {
        return $val - $this->receivingFromDonor->sum('sum');
    }

    function getActualSumAttribute()
    {
        return $this->sum - $this->revert_sum;
    }

    function positions()
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }

    function getOrderDataAttribute()
    {
        return DB::table('invoice_application_pivot')->where('invoice_id', $this->id)->get();
    }

    function customerRequisite()
    {
        return $this->morphTo('requisite');
    }

    function getApplicationIdAttribute()
    {
        $component = $this->orderComponents()->first();
        return $component ? $component->application_id : '';
    }

    function getRevertSumAttribute()
    {
        return $this->pays->where('operation', 'out')->where('method', '!=', 'pledge')->sum('sum');
    }

    function getPaidAttribute()
    {
        $paysSum = $this->pays->where('operation', 'in')->sum('sum');
        $actualPays = $paysSum - $this->revert_sum;

        if ($this->paid_sum !== $actualPays) {
            self::query()->where('id', $this->id)
                ->update([
                    'paid_sum' => $actualPays,
                ]);
            $this->paid_sum = $actualPays;
        }
        // $isPaid = $sum == $revertSum;
        if ($this->actual_sum === 0 || $this->actual_sum > $actualPays && $this->is_paid) {
            self::query()->where('id', $this->id)
                ->update([
                    'is_paid' => false
                ]);
            $this->is_paid = false;
        }

        if ($this->actual_sum > 0 && $this->actual_sum <= $actualPays && !$this->is_paid) {
            self::query()->where('id', $this->id)
                ->update([
                    'is_paid' => true
                ]);
            $this->is_paid = true;
        }

        return $actualPays;
    }


    function dispatcherLegalRequisite()
    {
        return $this->morphTo('main_requisite');
    }

    function dispatcherIndividualRequisite()
    {
        return $this->morphTo('main_requisite');
    }

    function getCustomerAttribute()
    {
        $owner = $this->main_requisite_type && $this->customerRequisite ? $this->customerRequisite->owner : null;
        if ($owner && $this->customerRequisite instanceof InternationalLegalDetails) {
            return Customer::query()->orWhereHas('international_legal_requisites', function (Builder $q) {
                $q->where('international_legal_details.id', $this->customerRequisite->id);
            })->first();
        }

        return $owner;
    }


    function getOneCInfoAttribute()
    {
        if ($this->company_branch->OneCConnection && $this->onec) {

            $service = new OneCService($this->company_branch);

            try {
                $info = $service->getEntityInfo(DispatcherInvoice::class, $this->id);
                $refresh = false;
                if ($info && !empty($info['pays'])) {

                    foreach ($info['pays'] as $pay) {
                        if (!$this->pays()->where('integration_ref', $pay['Ref_Key'])->exists()) {
                            $this->pays()->save(new InvoicePay([
                                'type' => 'cash',
                                'date' => now()->format('Y-m-d'),
                                'sum' => numberToPenny($pay['СуммаПлатежа']),
                                'tax_percent' => 0,
                                'integration_ref' => $pay['Ref_Key'],
                                'tax' => 0,
                            ]));
                            $refresh = true;
                        }
                    }
                }
                if ($info && !empty($info['cashless_pays'])) {

                    foreach ($info['cashless_pays'] as $pay) {
                        if (!$this->pays()->where('integration_ref', $pay['Ref_Key'])->exists()) {
                            $this->pays()->save(new InvoicePay([
                                'type' => 'cashless',
                                'date' => now()->format('Y-m-d'),
                                'sum' => numberToPenny($pay['СуммаПлатежа']),
                                'tax_percent' => 0,
                                'integration_ref' => $pay['Ref_Key'],
                                'tax' => 0,
                            ]));

                            $refresh = true;
                        }
                    }
                }

                if ($refresh) {
                    $this->load('pays');
                    $paid = $this->paid;
                }
                return $info;
            } catch (\Exception $exception) {
                logger($exception->getMessage());
            }

        }
        return null;
    }

    function getDispatcherRequisites()
    {
        return $this->dispatcherIndividualRequisite ?: $this->dispatcherLegalRequisite;
    }

    function getCustomerRequisites()
    {
        return $this->customerRequisite;
    }

    function getLeadAttribute()
    {
        return $this->owner->lead ?: $this->owner->partsRequest;
    }

    function getLinkAttribute()
    {
        return route('dispatcher_invoice_link', $this->alias);
    }

    function getVatAmountAttribute()
    {
        return $this->lead && $this->lead->contractorRequisite && $this->lead->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT
            ? $this->calculateVat($this->sum)
            : 0;
    }

    function calculateVat($sum)
    {
        $vat = $this->company_branch->company->domain->country->vat;

        $totalPercent = (100 + $vat) / 100;
        $percent = $vat / 100;

        return $sum / $totalPercent * $percent;
    }


}

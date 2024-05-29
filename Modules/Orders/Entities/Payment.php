<?php

namespace Modules\Orders\Entities;

use App\Finance\TinkoffMerchantAPI;
use App\Finance\TinkoffPayment;
use App\User;
use App\Overrides\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\CompanyOffice\Services\HasManager;
use Modules\CorpCustomer\Entities\InternationalLegalDetails;
use Modules\Orders\Entities\Payments\Invoice;
use Modules\Orders\Entities\Payments\InvoiceRequisite;

class Payment extends Model
{
    use BelongsToCompanyBranch, HasManager;

    protected $fillable = [
        'system',
        'currency',
        'status',
        'order_id',
        'amount',
        'company_branch_id',
        'creator_id'
    ];

    protected $appends = ['status_lang', 'sum_format', 'invoice_link', 'summary_link'];

    const STATUS_WAIT = 'wait';
    const STATUS_ACCEPT = 'accept';
    const STATUS_CANCEL = 'cancel';

    const TYPE_TINKOFF = 'tinkoff';
    const TYPE_TINKOFF_PARTIAL = 'tinkoff_partial';
    const TYPE_PROMO = 'promo';
    const TYPE_INVOICE = 'invoice';

    const PARTIAL_PERCENT = 20;


    static function getSystems()
    {
        return [
            self::TYPE_TINKOFF,
            self::TYPE_TINKOFF_PARTIAL,
            self::TYPE_INVOICE
        ];
    }

    function user()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    function order()
    {
        return $this->belongsTo(Order::class);
    }

    function scopeHasHold($q)
    {
        return $q->whereHas('order', function ($q) {
            $q->whereHas('holds');
        });
    }

    function scopeForDomain($q, $domain = null)
    {
        $domain = $domain ?: request()->header('domain');

        if (!$domain) {
            return $q;
        }
        return $q->whereHas('user', function ($q) use ($domain) {
            $q->whereHas('country', function ($q) use ($domain) {
                $q->whereHas('domain', function ($q) use ($domain) {
                    $q->whereAlias($domain);
                });
            });
        });
    }

    function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    function tinkoff_payment()
    {
        return $this->hasOne(TinkoffPayment::class);
    }

    function generatePayment($pay_items, $type, $requisite = [])
    {
        switch ($type) {
            case 'tinkoff':
                $instance = $this->createTinkoffPayment($pay_items);
                break;
            case 'tinkoff_partial':
                $instance = $this->createTinkoffPayment($pay_items, true);
                break;

            case 'invoice':
                $this->createInvoice($requisite);
                $instance = 'invoice';
                break;

            case 'promo':
                $this->accept();
                $instance = 'promo';
                break;
        }

        return $instance;
    }

    function createInvoice($requisite)
    {
        $tax_percent = 20;

        $tax = $this->amount - round($this->amount - ($this->amount * $tax_percent / 100));

        $invoice = Invoice::create([
            'alias' => "alias",
            'number' => "{$this->user->id}-{$this->order_id}",
            'date' => now()->format('Y-m-d'),
            'sum' => $this->amount - $tax,
            'tax_percent' => $tax_percent,
            'tax' => $tax,
            'payment_id' => $this->id,
        ]);

        if ($this->order->domain->alias !== 'ru' && $requisite['type'] === 'entity') {
            $invoice_requisite = new InternationalLegalDetails(array_merge($requisite, ['user_id' => $this->order->user_id]));
        } else {
            $invoice_requisite = new InvoiceRequisite([
                'name' => $requisite['name'],
                'inn' => ($requisite['type'] === 'entity' ? $requisite['inn'] : null),
                'kpp' => ($requisite['type'] === 'entity' ? $requisite['kpp'] : null),
                'type' => $requisite['type'],
            ]);

            $invoice_requisite->invoice_id = $invoice->id;
        }



        $invoice_requisite->save();

       $invoice->requisite()->associate($invoice_requisite);

        $invoice->save();

        return $invoice;
    }

    function createTinkoffPayment($pay_items, $partial = false)
    {
        $tinkoff = TinkoffPayment::create([
            'payment_id' => $this->id,
            'amount' => $this->amount,
        ]);

        $data = $tinkoff->generateData($pay_items, $partial, self::PARTIAL_PERCENT);

        $tinkoffApi = new TinkoffMerchantAPI();
        $tinkoffApi->init($data);

        return $tinkoffApi;
    }


    function accept(): void
    {
        $this->order->accept();
        $this->update([
            'status' => \Modules\Orders\Entities\Payment::STATUS_ACCEPT
        ]);
    }

    function reverse()
    {
        $this->order->holds()->delete();

        $this->order->update([
            'status' => Order::STATUS_CLOSE
        ]);
        $this->update([
            'status' => \Modules\Orders\Entities\Payment::STATUS_CANCEL
        ]);
        if (in_array($this->system, TinkoffPayment::TYPES)) {
            $this->tinkoff_payment->reverse();
        }
    }

    function getStatusLangAttribute()
    {
        $messages = [
            self::STATUS_WAIT => trans('transbaza_order.pay_wait'),
            self::STATUS_ACCEPT => trans('transbaza_order.pay_accept'),
            self::STATUS_CANCEL => trans('transbaza_order.pay_cancel')
        ];
        return isset($messages[$this->status]) ? $messages[$this->status] : trans('transbaza_order.pay_unknown');
    }

    function getSumFormatAttribute()
    {
        return humanSumFormat($this->amount);
    }


    function cancel()
    {

        $this->update([
            'status' => \Modules\Orders\Entities\Payment::STATUS_CANCEL
        ]);

        $this->order->update([
            'status' => Order::STATUS_CLOSE
        ]);

        if (in_array($this->system, TinkoffPayment::TYPES)) {
            $this->tinkoff_payment->cancel();
        }
    }

    function getInvoiceLinkAttribute()
    {
        return $this->invoice ? route('order_invoice', ['alias' => $this->invoice->alias]) : '';
    }

    function getSummaryLinkAttribute()
    {
        return route('order_summary', ['id' => $this->id]);
    }
}

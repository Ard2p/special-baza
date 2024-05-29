<?php

namespace Modules\Orders\Entities\Payments;

use Carbon\Carbon;
use App\Overrides\Model;
use Modules\Orders\Entities\Payment;
use PDF;

class Invoice extends Model
{
    protected $fillable = [
        'alias',
        'number',
        'date',
        'sum',
        'tax_percent',
        'tax',
        'payment_id'
    ];

    protected $dates = ['date'];

    protected $appends = ['link', 'paid'];

    protected $with = ['pays'];

    function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    function pays()
    {
        return $this->morphMany(InvoicePay::class, 'invoice');
    }

    function requisite()
    {
        return $this->morphTo('requisite');
    }

    function setAliasAttribute($val)
    {
        $uid = uniqid();
        $this->attributes['alias'] = md5("{$this->number}-{$uid}");
    }

    function setTaxPercentAttribute($val)
    {
        $this->attributes['tax_percent'] = round($val * 100);
    }

    function getTaxPercentAttribute($val)
    {
        return $val / 100;
    }

    function getFullSumAttribute()
    {
        return $this->sum + $this->tax;
    }

    function generatePdf()
    {

    }

    function getPaidAttribute()
    {
        return $this->pays->sum('sum');
    }

    function getLinkAttribute()
    {
        return route('order_invoice', ['alias' => $this->alias]);
    }

    function getRuInvoice($html = false)
    {
        setlocale(LC_TIME, 'ru_RU.UTF-8');
        Carbon::setLocale('ru');

        $invoice = $this;
        $order = $this->payment->order;
        $vehicles = $order->vehicles;
        $contractors = $order->dispatcher_contractors;

        $transbaza = [
            'name' => 'ООО "Технологичные перевозки"',
            'inn' => '7731321718',
            'kpp' => '772301001',
            'ogrn' => '1167746649707',
            'address' => 'улица Верхние Поля, д. ДОМ 35, корп./ст. 2, кв./оф. 44, г. Москва',
            'bank' => [
                'name' => 'АО "АЛЬФА-БАНК"',
                'bik' => '044525593',
                'kor' => '30101810200000000593',
                'rs' => '40702810601600004347',
            ]

        ];



        $pdf = PDF::loadView('invoice.body', compact('transbaza', 'invoice', 'vehicles', 'order', 'contractors'));

        return $html ? view('invoice.body', compact('transbaza', 'invoice', 'vehicles', 'order', 'contractors')) : $pdf->stream('invoice.pdf');
    }

    function getKinoskInvoice($html = false)
    {
        setlocale(LC_TIME, 'en_EN.UTF-8');
        Carbon::setLocale('en');
        app()->setLocale('en');

        $invoice = $this;
        $order = $this->payment->order;
        $vehicles = $order->vehicles;

        $fmt = numfmt_create( $order->domain->options['default_locale'], \NumberFormatter::CURRENCY );

        $pdf = PDF::loadView('invoice.kinosk', compact('invoice', 'vehicles', 'order', 'fmt'))->setPaper('a4', 'landscape');
        return $html ? view('invoice.kinosk', compact('invoice', 'vehicles', 'order', 'fmt')) : $pdf->stream('invoice.pdf');
    }
}

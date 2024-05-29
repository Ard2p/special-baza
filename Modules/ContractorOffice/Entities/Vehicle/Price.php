<?php

namespace Modules\ContractorOffice\Entities\Vehicle;


use App\Overrides\Model;

class Price extends Model
{

    public $timestamps = false;

    const TYPE_CASH = 'cash';
    const TYPE_CASHLESS_VAT = 'cashless_vat';
    const TYPE_CASHLESS_WITHOUT_VAT = 'cashless_without_vat';
    const TYPE_VALUE_ADDED_CASH = 'value_added_cash';
    const TYPE_VALUE_ADDED_CASHLESS = 'value_added_cashless';
    const TYPE_VALUE_ADDED_CASHLESS_WITHOUT_VAT = 'value_added_cashless_without_vat';


    const CASHLESS_TYPE_INVOICE = 'invoice';
    const CASHLESS_TYPE_CARD = 'card';
    const CASHLESS_TYPE_VOUCHER = 'voucher';
    const CASHLESS_TYPE_RECEIPT = 'receipt';
    const CASHLESS_TYPE_PRE_AUTHORIZATION = 'pre_authorization';

    protected $table = 'vehicle_prices';

    protected $fillable = [
        'cost_per_hour',
        'cost_per_shift',
        'type',
        'machinery_type',
    ];

    const MARKET_PRICE_CURRENCIES = [
        'rub' => 'рублей',
        'eur' => 'евро',
        'usd' => 'долларов США',
        'aud' => 'Австралийский доллар',
    ];

    static function getCashlessTypes()
    {
        return [
            self::CASHLESS_TYPE_INVOICE,
            self::CASHLESS_TYPE_CARD,
            self::CASHLESS_TYPE_VOUCHER,
            self::CASHLESS_TYPE_PRE_AUTHORIZATION,
            self::CASHLESS_TYPE_RECEIPT,
        ];
    }
    static function getPriceNames()
    {
        return  [
            'rub' => trans('currencies.rub_gen'),
            'eur' => trans('currencies.eur_gen'),
            'usd' =>  trans('currencies.usd_gen'),
            'aud' =>  trans('currencies.aud_gen'),
        ];
    }

    static function removeVat($sum, $percent)
    {

       return round($sum / (($percent / 100) + 1), 2);
    }

    static function addVat($sum, $percent)
    {
        return round($sum * (($percent / 100) + 1), 2);
    }

    static function getVat($sum, $percent)
    {return round(($sum / ($percent + 100)) * 20, 2);
    }

    function setTypeAttribute($val)
    {
       if(!in_array($val, self::getTypes())) {
           throw new \InvalidArgumentException('wrong type argument', 500);
       }

       $this->attributes['type'] = $val;
    }

    static function getTypes()
    {
        return [
            self::TYPE_CASH,
            self::TYPE_CASHLESS_WITHOUT_VAT,
            self::TYPE_CASHLESS_VAT,
            self::TYPE_VALUE_ADDED_CASH,
            self::TYPE_VALUE_ADDED_CASHLESS,
            self::TYPE_VALUE_ADDED_CASHLESS_WITHOUT_VAT
        ];
    }
}

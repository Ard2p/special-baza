<?php

namespace Modules\RestApi\Entities;


use App\Overrides\Model;

class Currency extends Model
{

    public $timestamps = false;

    protected $fillable = [
        'code',
        'name',
        'short',
        'domain_id',
    ];

    function domain()
    {
        $this->belongsTo(Domain::class);
    }


     static function getCurrencies()
     {
         return collect(config('app.currencies'));
     }

     static function getCodesList($toString = false)
     {
         $currencies = self::getCurrencies()->pluck('code')->toArray();

         return $toString ? implode(',', $currencies) : $currencies;
     }


     static function getByCode($code)
     {
         return self::where('code', $code)->first();
     }
}

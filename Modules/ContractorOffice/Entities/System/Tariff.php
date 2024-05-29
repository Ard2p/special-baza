<?php

namespace Modules\ContractorOffice\Entities\System;

use App\Machines\Type;
use App\Overrides\Model;
use Modules\ContractorOffice\Services\Tariffs\ConcreteMixerCalculation;
use Modules\ContractorOffice\Services\Tariffs\DistanceCalculation;
use Modules\ContractorOffice\Services\Tariffs\TimeCalculation;

class Tariff extends Model
{
    protected $table = 'category_tariffs';

    const DISTANCE_CALCULATION = 'distance_calculation';
    const TIME_CALCULATION = 'time_calculation';
    const CONCRETE_MIXER = 'concrete_mixer';

    protected $fillable = [
        'type'
    ];
    protected $appends = ['name'];


    private function getCalculationSystem()
    {

        switch ($this->type) {
            case self::DISTANCE_CALCULATION:
                return new DistanceCalculation();
            case self::TIME_CALCULATION:
                return new TimeCalculation();
            case self::CONCRETE_MIXER:
                return new ConcreteMixerCalculation();
        }
    }

    /**
     * Расчет стоиомсти по тарифу.
     * Входящие параметры - Ассоциативный массив с необходимыми параметрами для расчета по тарифу.
     * @param $params
     * @return float|int
     */
    function calculateCost($params)
    {

        return $this->getCalculationSystem()->calculateCost(...$params);
    }

    private function getNames()
    {
        return [
            self::TIME_CALCULATION => trans('transbaza_machine_edit.tariff_time'),
            self::DISTANCE_CALCULATION => trans('transbaza_machine_edit.tariff_distance'),
            self::CONCRETE_MIXER => trans('transbaza_machine_edit.tariff_concrete'),
        ];
    }

    static function getTariffs()
    {
        return [
            self::TIME_CALCULATION,
            self::DISTANCE_CALCULATION,
            self::CONCRETE_MIXER,
        ];
    }

    function getNameAttribute()
    {
        return $this->getNames()[$this->type];
    }

    function categories()
    {
        return $this->belongsToMany(Type::class, 'categories_tariffs');
    }
}

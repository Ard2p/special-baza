<?php

namespace Modules\ContractorOffice\Entities\Telematics\Wialon;


class UnitReport
{

    private $reportResult = [];
    private $resultArray = [];
    private $unitTrips = [];
    private $unitGeneric = [];
    private $unitEngineHours = [];
    private $total = [];

    public function __construct(array $reportData)
    {
        $this->reportResult = collect($reportData['reportResult']);
        $this->resultArray = collect([]);
        $this->parseReport();
    }

    private function parseReport()
    {

        $this->total = collect([]);

        if(isset($this->reportResult['tables'][0])) {

            $this->unitGeneric = $this->parseTable($this->reportResult['tables'][0]);
            $this->total = $this->total->merge($this->unitGeneric->toArray());
        }

        if(isset($this->reportResult['tables'][1])) {

            $this->unitTrips = $this->parseTable($this->reportResult['tables'][1]);
            $this->total = $this->total->merge($this->unitTrips->toArray());
        }

        if(isset($this->reportResult['tables'][2])) {

            $this->unitEngineHours = $this->parseTable($this->reportResult['tables'][2]);
            $this->total = $this->total->merge($this->unitEngineHours->toArray());
        }

        $this->generateResultArray();
    }

    private function parseTable($data)
    {
        $result = [];
        foreach ($data['header_type'] as $key => $type) {

            $result[] = [
                'label' => $data['header'][$key],
                'type' => $type,
                'value' => $data['totalRaw'][$key]['v'] ?? ''
            ];

        }

        return collect($result);
    }

    private function parseStats()
    {
        $stats = $this->reportResult['stats'];
        $this->resultArray = $this->resultArray->merge([
         /*   'working_hours' => $stats[17][1],
            'time_in_motion' => $stats[25][1],
            'average_fuel' => floatval($stats[6][1]),
            'fuel_drain' => floatval($stats[39][1]),
            'fuel_drain_count' => intval($stats[38][1]),
            'mileage_by_work_hours' => intval($stats[22][1]) * 1000,*/
        ]);
    }

    private function generateResultArray()
    {
        //$this->parseStats();

        $this->resultArray = $this->resultArray->merge([
            'average_speed' => round($this->getValue('avg_speed'), 2),
            'max_speed' => round($this->getValue('max_speed'), 2),
            'mileage' => round($this->getValue('mileage')),
            'begin_mileage' => round($this->getValue('absolute_mileage_begin')),
            'end_mileage' => round($this->getValue('absolute_mileage_end')),
            'fuel_level_begin' => round($this->getValue('fuel_level_begin'), 2),
            'fuel_level_end' => round($this->getValue('fuel_level_end'), 2),

            'fuel_consumption_abs' => round($this->getValue('fuel_consumption_abs'), 2),
            'fuel_consumption_fls' => round($this->getValue('fuel_consumption_fls'), 2),
            'fuel_consumption_ins' => round($this->getValue('fuel_consumption_ins'), 2),

            'toll_roads_mileage' => round($this->getValue('toll_roads_mileage'), 2),
            'toll_roads_cost' => round($this->getValue('toll_roads_cost'), 2),

            'working_hours' => gmdate("H:i:s", $this->getValue('eh')),
            'time_in_motion' => gmdate("H:i:s", $this->getValue('in_motion')),

            'average_fuel' => round($this->getValue('avg_fuel_consumption_all'), 2),
            'fuel_drain' => round($this->getValue('thefted'), 2),
            'fuel_drain_count' => round($this->getValue('thefts_count'), 2),
            'mileage_by_work_hours' => round($this->getValue('mileage_by_work_hours'), 2),

            'driver' => $this->getValue('driver'),

        ]);
    }

    private function getValue($key)
    {
       // dd($this->total);
        $cell = $this->total->where('type', $key)->first();

        return $cell ? $cell['value'] : null;
    }

    function getUnitTrips()
    {
        return $this->unitTrips;
    }

    function getResult()
    {
        return $this->resultArray;
    }


}

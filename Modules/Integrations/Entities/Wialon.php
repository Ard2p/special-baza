<?php

namespace Modules\Integrations\Entities;

use App\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\RedirectMiddleware;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\TransferStats;
use App\Overrides\Model;
use Illuminate\Support\Str;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\CompanyOffice\Services\HasManager;
use Modules\ContractorOffice\Entities\Telematics\Wialon\UnitReport;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class Wialon extends Model
{

    use BelongsToCompanyBranch, HasManager;
    protected $table = 'wialon_accounts';

    private Logger $logger;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->logger = new Logger('wialon');
        $this->logger->pushHandler(new StreamHandler(storage_path('logs/wialon/' . now()->format('Y-m-d') . '.log')));
    }

    protected $fillable = [
        'login',
        'password',
        'host',
        'login_url',
        'token',
        'creator_id',
        'template_id',
        'company_branch_id',
    ];

    function vehicles()
    {
        return $this->hasMany(WialonVehicle::class, 'wialon_connection_id');
    }

    function user()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    function getHostAttribute($val)
    {
        return Str::contains($val, '.wialon.com')
            ? "https://hst-api.wialon.com"
            : $val;
    }

    function getToken()
    {

        $response = file_get_contents($this->login_url);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        @$dom->loadHTML($response);

        $nodes = $dom->getElementsByTagName('input');
        $hidden_inputs = [];

        foreach ($nodes as $node) {

            if ($node->hasAttributes()) {
                foreach ($node->attributes as $attribute) {
                    if ($attribute->nodeName == 'type' && $attribute->nodeValue == 'hidden') {

                        $hidden_inputs[$node->getAttribute('name')] = $node->getAttribute('value');
                    }
                }
            }
        }
        unset($node);

        $hidden_inputs['login'] = $this->login;
        $hidden_inputs['passw'] = $this->password;
        $hidden_inputs['access_type'] = -1;

        $client = new Client(['base_uri' => str_replace('login.html', '', $this->login_url)]);

        $client->post('oauth.html', [
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            RequestOptions::FORM_PARAMS => $hidden_inputs,
            'on_stats' => function (TransferStats $stats) use (&$redir) {
                $redir = (string)$stats->getEffectiveUri();
            },
            'allow_redirects' => true
        ])->getBody()->getContents();

        $url = parse_url($redir);

        $query_parts = [];

        parse_str(($url['query'] ?? ''), $query_parts);

        $this->update(['token' => $query_parts['access_token']]);

        return $this;
    }

    function getSessionEidAttribute()
    {
        try {
            return \Cache::remember("{$this->id}_wialon_eid", 180, function () {

                $response = $this->tokenLogin();
                $tries = 0;
                if (!isset($response['eid'])) {
                    while ($tries <= 3) {
                        try {
                            $response = $this->getToken()->tokenLogin();

                        } catch (\Exception $exception) {
                            // logger('Failed connection ' . $this->login . ' ' . $exception->getMessage());
                        }

                        $tries += (isset($response['eid']) ? 3 : 1);
                        sleep(2);
                    }
                }
                $eid = $response['eid'];
                return $eid;
            });
        }catch (\Exception $e){
            $this->logger->error($e->getMessage());
            $this->logger->error($e->getFile());
            $this->logger->error($e->getLine());
            $this->logger->error($e->getTraceAsString());
            return null;
        }
    }

    function tokenLogin()
    {
        $body = $this->request('post', '', [
            'svc' => 'token/login',
            'params' => json_encode([
                'token' => $this->token
            ])
        ])->getBody()->getContents();

        return json_decode($body, true);
    }

    function updateDataFlags($resource)
    {
        $response = $this->request('post', '', [
            'svc' => 'core/update_data_flags',
            'params' => json_encode([
                'spec' => [
                    [
                        "type" => "type",
                        "data" => $resource,
                        "flags" => '4294967295',
                        "mode" => 1
                    ]
                ]
            ]),
            'sid' => $this->session_eid
        ])->getBody()->getContents();

        return json_decode($response, true);
    }

    function accountInfo()
    {
        // dd($this->session_eid);
        $response = $this->request('get', '', [
            'svc' => 'core/get_account_data',
            'params' => json_encode([
                'type' => 1
            ]),
            'sid' => $this->session_eid
        ])->getBody()->getContents();

        return json_decode($response, true);
    }

    function searchItems($type = 'avl_unit')
    {
        $response = $this->request('get', '', [
            'svc' => 'core/search_items',
            'params' => json_encode([
                'spec' => [
                    'itemsType' => $type,
                    'propName' => 'sys_name',
                    'propValueMask' => '*',
                    'sortType' => 'sys_name',
                ],
                'force' => 128,
                'flags' => 5129,
                'from' => 0,
                'to' => 0,
            ]),
            'sid' => $this->session_eid
        ])->getBody()->getContents();

        return json_decode($response, true);
    }

    function getResourceByName($name = '.REPORTS')
    {
        $items = $this->searchItems('avl_resource');
        $items = collect(($items['items']));

        return $items->where('nm', $name)->first();
    }

    function getFirstResource()
    {
        $items = $this->searchItems('avl_resource');
        $items = collect(($items['items']));

        return $items->first();
    }

    function searchItem($id)
    {
        $response = $this->request('get', '', [
            'svc' => 'core/search_item',
            'params' => json_encode([
                'id' => $id,
                'flags' => 4294967295,
            ]),
            'sid' => $this->session_eid
        ])->getBody()->getContents();

        return json_decode($response, true);
    }

    private function createTransbazaReportTemplate()
    {
        /*    $report_data = [
                'id' => 0,
                'ct' => 'avl_unit',
                'n' => 'transbaza_report',
                'p' => '{"descr":"","bind":{"avl_unit":[]}}',
                'tbl' =>
                    [
                        [
                            'n' => 'unit_stats',
                            'l' => 'Статистика',
                            'f' => 0,
                            'c' => '',
                            'cl' => '',
                            'cp' => '',
                            'p' => '{"address_format":"1255211008_10_5","time_format":"%Y-%m-%E_%H:%M:%S","us_units":0}',
                            'sch' =>
                                [
                                    'y' => 0,
                                    'm' => 0,
                                    'w' => 0,
                                    'f1' => 0,
                                    'f2' => 0,
                                    't1' => 0,
                                    't2' => 0,
                                    'fl' => 0,
                                ],
                            'sl' => '["Address","Time Format","Measure"]',
                            's' => '["address_format","time_format","us_units"]',
                        ],
                        [
                            'n' => 'unit_stats',
                            'l' => 'Статистика',
                            'f' => 0,
                            'c' => '',
                            'cl' => '',
                            'cp' => '',
                            'p' => '',
                            'sch' =>
                                [
                                    'y' => 0,
                                    'm' => 0,
                                    'w' => 0,
                                    'f1' => 0,
                                    'f2' => 0,
                                    't1' => 0,
                                    't2' => 0,
                                    'fl' => 0,
                                ],
                            'sl' => '["Отчет","Объект","Начало интервала","Окончание интервала","Пробег по всем сообщениям","Потрачено","Потрачено по ДИРТ","Потрачено по ДАРТ","Потрачено по ДМРТ","Потрачено по ДУТ","Потрачено по расчету","Потрачено по нормам","Потрачено по ДИРТ в движении","Потрачено по ДАРТ в движении","Потрачено по ДМРТ в движении","Потрачено по ДУТ в движении","Потрачено по расчету в движении","Потрачено по нормам в движении","Потрачено по ДУТ без движения","Потрачено по расчету без движения","Потрачено по нормам без движения","Ср. расход","Ср. расход по ДИРТ","Ср. расход по ДАРТ","Ср. расход по ДМРТ","Ср. расход по ДУТ (весь пробег)","Ср. расход по ДУТ (пробег по детектору поездок)","Ср. расход по расчету","Ср. расход по нормам","Нач. уровень","Кон. уровень","Макс. уровень топлива","Мин. уровень топлива"]',
                            's' => '["report_name","unit_name","time_begin","time_end","mileage","fuel_consumption_all","fuel_consumption_imp","fuel_consumption_abs","fuel_consumption_ins","fuel_consumption_fls","fuel_consumption_math","fuel_consumption_rates","fuel_consumption_imp_move","fuel_consumption_abs_move","fuel_consumption_ins_move","fuel_consumption_fls_move","fuel_consumption_math_move","fuel_consumption_rates_move","fuel_avg_consumption_fls_idle","fuel_avg_consumption_math_idle","fuel_avg_consumption_rates_idle","avg_fuel_consumption_all","avg_fuel_consumption_imp","avg_fuel_consumption_abs","avg_fuel_consumption_ins","avg_fuel_consumption_fls","avg_fuel_consumption_fls_td","avg_fuel_consumption_math","avg_fuel_consumption_rates","fuel_level_begin","fuel_level_end","fuel_level_max","fuel_level_min"]',
                        ],
                        [
                            'n' => 'unit_engine_hours',
                            'l' => 'Моточасы',
                            'f' => 0,
                            'c' => '',
                            'cl' => '',
                            'cp' => '',
                            'p' => '',
                            'sch' =>
                                [
                                    'y' => 0,
                                    'm' => 0,
                                    'w' => 0,
                                    'f1' => 0,
                                    'f2' => 0,
                                    't1' => 0,
                                    't2' => 0,
                                    'fl' => 0,
                                ],
                            'sl' => '["Моточасы","Нач. моточасы","Кон. моточасы","Моточасы в движении","Моточасы на холостом ходу","Пробег в моточасах","Ср. обороты двигателя","Макс. обороты двигателя","Потрачено по ДИРТ на холостом ходу"]',
                            's' => '["duration","absolute_eh_begin","absolute_eh_end","duration_move","duration_stay","mileage_stats","avg_engine_rpm","max_engine_rpm","fuel_avg_consumption_imp_idle_eh"]',
                        ],
                        [
                            'n' => 'unit_fillings',
                            'l' => 'Заправки',
                            'f' => 0,
                            'c' => '',
                            'cl' => '',
                            'cp' => '',
                            'p' => '',
                            'sch' =>
                                [
                                    'y' => 0,
                                    'm' => 0,
                                    'w' => 0,
                                    'f1' => 0,
                                    'f2' => 0,
                                    't1' => 0,
                                    't2' => 0,
                                    'fl' => 0,
                                ],
                            'sl' => '["Всего топлива зарегистрировано","Разница"]',
                            's' => '["registered","difference"]',
                        ],
                        [
                            'n' => 'unit_thefts',
                            'l' => 'Сливы',
                            'f' => 0,
                            'c' => '',
                            'cl' => '',
                            'cp' => '',
                            'p' => '',
                            'sch' =>
                                [
                                    'y' => 0,
                                    'm' => 0,
                                    'w' => 0,
                                    'f1' => 0,
                                    'f2' => 0,
                                    't1' => 0,
                                    't2' => 0,
                                    'fl' => 0,
                                ],
                            'sl' => '["Всего топлива слито","Всего сливов"]',
                            's' => '["thefted","count"]',
                        ],
                        [
                            'n' => 'unit_speedings',
                            'l' => 'Превышение скорости',
                            'f' => 0,
                            'c' => '',
                            'cl' => '',
                            'cp' => '',
                            'p' => '',
                            'sch' =>
                                [
                                    'y' => 0,
                                    'm' => 0,
                                    'w' => 0,
                                    'f1' => 0,
                                    'f2' => 0,
                                    't1' => 0,
                                    't2' => 0,
                                    'fl' => 0,
                                ],
                            'sl' => '["Начальный пробег","Конечный пробег"]',
                            's' => '["absolute_mileage_begin","absolute_mileage_end"]',
                        ],
                        [
                            'n' => 'unit_trips',
                            'l' => 'Поездки',
                            'f' => 0,
                            'c' => '',
                            'cl' => '',
                            'cp' => '',
                            'p' => '',
                            'sch' =>
                                [
                                    'y' => 0,
                                    'm' => 0,
                                    'w' => 0,
                                    'f1' => 0,
                                    'f2' => 0,
                                    't1' => 0,
                                    't2' => 0,
                                    'fl' => 0,
                                ],
                            'sl' => '["Средняя скорость в поездках","Макс. скорость в поездках"]',
                            's' => '["avg_speed","max_speed"]',
                        ],
                        [
                            'n' => 'unit_violations',
                            'l' => 'Нарушения',
                            'f' => 0,
                            'c' => '',
                            'cl' => '',
                            'cp' => '',
                            'p' => '',
                            'sch' =>
                                [
                                    'y' => 0,
                                    'm' => 0,
                                    'w' => 0,
                                    'f1' => 0,
                                    'f2' => 0,
                                    't1' => 0,
                                    't2' => 0,
                                    'fl' => 0,
                                ],
                            'sl' => '["Количество нарушений"]',
                            's' => '["events_count"]',
                        ],
                        [
                            'n' => 'unit_cmds',
                            'l' => 'Выполненные команды',
                            'f' => 0,
                            'c' => '',
                            'cl' => '',
                            'cp' => '',
                            'p' => '',
                            'sch' =>
                                [
                                    'y' => 0,
                                    'm' => 0,
                                    'w' => 0,
                                    'f1' => 0,
                                    'f2' => 0,
                                    't1' => 0,
                                    't2' => 0,
                                    'fl' => 0,
                                ],
                            'sl' => '["Выполненные команды"]',
                            's' => '["count"]',
                        ],
                    ],
                't' => 'avl_unit',
                'itemId' => ($this->getResourceByName($this->login)['id']),
                'callMode' => 'create'
            ];*/

        $report_data = array(
            'id' => 0,
            'ct' => 'avl_unit',
            'n' => 'TRANSBAZA_REPORT',
            'p' => '{"bind":{"avl_unit":[]}}',
            'tbl' =>
                array(
                    0 =>
                        array(
                            'n' => 'unit_stats',
                            'l' => 'Статистика',
                            'f' => 0,
                            'c' => '',
                            'cl' => '',
                            'cp' => '',
                            'p' => '{"address_format":"1255211008_10_5","time_format":"%Y-%m-%E_%H:%M:%S","us_units":0}',
                            'sch' =>
                                array(
                                    'y' => 0,
                                    'm' => 0,
                                    'w' => 0,
                                    'f1' => 0,
                                    'f2' => 0,
                                    't1' => 0,
                                    't2' => 0,
                                    'fl' => 0,
                                ),
                            'sl' => '["Address","Time Format","Measure"]',
                            's' => '["address_format","time_format","us_units"]',
                        ),
                    1 =>
                        array(
                            'n' => 'unit_generic',
                            'l' => 'Сводка',
                            'f' => 4368,
                            'c' => '["mileage","mileage_all","correct_mileage","avg_speed","max_speed","in_motion","eh","sensor_duration","duration_stay","sensor_productivity","fuel_consumption_all","fuel_consumption_imp","fuel_consumption_abs","fuel_consumption_ins","fuel_consumption_fls","fuel_consumption_math","fuel_consumption_rates","avg_fuel_consumption_all","avg_fuel_consumption_imp","avg_fuel_consumption_abs","avg_fuel_consumption_ins","avg_fuel_consumption_fls","avg_fuel_consumption_math","avg_fuel_consumption_rates","fillings_count","thefts_count","filled","thefted","ecodriving","initial_counter_sensors","final_counter_sensors"]',
                            'cl' => '["Пробег в поездках","Пробег по всем сообщениям","Пробег (скорректированный)","Ср. скорость","Макс. скорость","Время в движении","Моточасы","Длительность полезной работы","Стоянки","Продуктивность","Потрачено","Потрачено по ДИРТ","Потрачено по ДАРТ","Потрачено по ДМРТ","Потрачено по ДУТ","Потрачено по расчету","Потрачено по нормам","Ср. расход","Ср. расход по ДИРТ","Ср. расход по ДАРТ","Ср. расход по ДМРТ","Ср. расход по ДУТ","Ср. расход по расчету","Ср. расход по нормам","Всего заправок","Всего сливов","Заправлено","Слито","Штраф","Нач. счетчик","Кон. счетчик"]',
                            'cp' => '[{},{},{},{},{},{},{},{},{},{},{},{},{},{},{},{},{},{},{},{},{},{},{},{},{},{},{},{},{},{},{}]',
                            'p' => '{"custom_interval":{"type":0}}',
                            'sch' =>
                                array(
                                    'f1' => 0,
                                    'f2' => 0,
                                    't1' => 0,
                                    't2' => 0,
                                    'm' => 0,
                                    'y' => 0,
                                    'w' => 0,
                                    'fl' => 0,
                                ),
                            'sl' => '',
                            's' => '',
                        ),
                    2 =>
                        array(
                            'n' => 'unit_trips',
                            'l' => 'Поездки',
                            'f' => 4368,
                            'c' => '["location_begin","coord_begin","location_end","coord_end","driver","toll_roads_mileage","toll_roads_cost","trips_count","counter_sensors","initial_counter_sensors","final_counter_sensors","absolute_mileage_begin","absolute_mileage_end","fuel_level_begin","fuel_level_end"]',
                            'cl' => '["Нач. положение","Нач. координаты","Кон. положение","Кон. координаты","Водитель","Пробег по платным дорогам","Стоимость платных дорог","Количество поездок","Счетчик","Нач. счетчик","Кон. счетчик","Начальный пробег","Конечный пробег","Нач. уровень","Кон. уровень"]',
                            'cp' => '[{},{},{},{},{},{},{},{},{},{},{},{},{},{},{}]',
                            'p' => '',
                            'sch' =>
                                array(
                                    'y' => 0,
                                    'm' => 0,
                                    'w' => 0,
                                    'f1' => 0,
                                    'f2' => 0,
                                    't1' => 0,
                                    't2' => 0,
                                    'fl' => 0,
                                ),
                            'sl' => '',
                            's' => '',
                        ),
                    3 =>
                        array(
                            'n' => 'unit_engine_hours',
                            'l' => 'Моточасы',
                            'f' => 4368,
                            'c' => '["duration","absolute_eh_begin","absolute_eh_end","duration_ival","duration_stay","mileage","correct_mileage","absolute_mileage_begin","absolute_mileage_end","sensor_productivity","fuel_avg_consumption_math_idle","fuel_avg_consumption_rates_idle"]',
                            'cl' => '["Моточасы","Нач. моточасы","Кон. моточасы","Общее время","Холостой ход","Пробег","Пробег (скорректированный)","Начальный пробег","Конечный пробег","Продуктивность","Потрачено по расчету на холостом ходу","Потрачено по нормам на холостом ходу"]',
                            'cp' => '[{},{},{},{},{},{},{},{},{},{},{},{}]',
                            'p' => '',
                            'sch' =>
                                array(
                                    'y' => 0,
                                    'm' => 0,
                                    'w' => 0,
                                    'f1' => 0,
                                    'f2' => 0,
                                    't1' => 0,
                                    't2' => 0,
                                    'fl' => 0,
                                ),
                            'sl' => '',
                            's' => '',
                        ),
                ),
            't' => 'avl_unit',
            'itemId' => $this->getResourceByName($this->login)['id'],
            'callMode' => 'create',
        );

        $response = $this->request('post', '', [
            'svc' => 'report/update_report',
            'params' => json_encode($report_data),
            'sid' => $this->session_eid
        ])->getBody()->getContents();

        return json_decode($response, true);
    }

    function createReportTemplate()
    {
        $tpl = $this->createTransbazaReportTemplate();
        $this->update(['template_id' => $tpl[1]['id']]);

        return $this;
    }

    function cleanReport()
    {
        $response = $this->request('get', '', [
            'svc' => '   report/cleanup_result',
            'params' => json_encode([]),
            'sid' => $this->session_eid
        ])->getBody()->getContents();

        return json_decode($response, true);

    }


    function generateReport($id, $resource_id, $from = null, $to = null)
    {
        $from = $from ?: now()->subDay()->startOfDay()->getTimestamp();
        $to = $to ?: now()->subDay()->endOfDay()->getTimestamp();
        $this->cleanReport();
        $response = $this->request('get', '', [
            'svc' => 'report/exec_report',
            'params' => json_encode([
                "reportResourceId" => $resource_id,
                "reportTemplateId" => $this->template_id,
                "reportTemplate" => null,
                "reportObjectId" => $id,
                "reportObjectSecId" => 0,
                'flags' => 0,
                "interval" => [
                    "flags" => 16777216,
                    "from" => $from,
                    "to" => $to
                ]
            ]),
            'sid' => $this->session_eid
        ])->getBody()->getContents();

        return json_decode($response, true);
    }

    function getUnitReport($id, $resource_id, $from = null, $to = null)
    {
        return new UnitReport($this->generateReport($id, $resource_id, $from, $to));
    }

    function request($method = 'get', $path = '', $params = [])
    {

        $client = new Client(['base_uri' => "{$this->host}/wialon/ajax.html"]);

        return $client->request($method, $path, ['query' => $params]);

    }

    function loadVehicles()
    {
        $items = $this->searchItems()['items'];

        foreach ($items as $item) {
            $vehicle = WialonVehicle::query()
                ->where('wialon_connection_id', $this->id)
                ->where('wialon_id', $item['id'])
                ->first();
            $fields = [
                'name' => $item['nm'],
                'wialon_id' => $item['id'],
                'wialon_connection_id' => $this->id,
            ];
            if ($vehicle) {
                $vehicle->update($fields);
            } else {
                WialonVehicle::create($fields);
            }
        }
    }


}

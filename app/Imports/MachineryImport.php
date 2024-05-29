<?php

namespace App\Imports;

use App\Machinery;
use App\Machines\Brand;
use App\Machines\MachineryModel;
use App\Machines\Type;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\ContractorOffice\Entities\System\Tariff;
use Modules\ContractorOffice\Entities\System\TariffUnitCompare;
use Modules\ContractorOffice\Entities\Vehicle\MachineryBase;
use Modules\ContractorOffice\Services\VehicleService;
use Modules\Dispatcher\Entities\Directories\Contractor;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class MachineryImport implements ToCollection, WithHeadingRow, WithChunkReading, WithCalculatedFormulas
{
    private $logger;
    private $bases;
    private $branch; 
    private $company;

    public function __construct(CompanyBranch $branch)
    {
        $this->branch = $branch;
        $this->company = $branch->company;

        $this->bases = MachineryBase::query()->forBranch()->get()->pluck('id', 'name');
        $now = Carbon::now()->format('Y-m-d');
        $this->logger = new Logger('machinery_import');
        $this->logger->pushHandler(new StreamHandler(storage_path("logs/machinery_import/$now.log")));
    }

    use Importable;


    public function collection(Collection $rows)
    {
        $city = $this->branch->city;
        $region = $this->branch->region;
        $units = TariffUnitCompare::query()->where('company_branch_id', $this->branch->id)->get();

//        try {

        foreach ($rows as $i => $row) {
            $this->logger->info($row);
            $base_id = $this->bases[trim($row['tocka_arendy'])];
            $min_order_type = (trim($row['minimalnyi_zakaz_cassmena']) == 'смена') ? 'shift' : 'hour';
            $price_includes_fas = (trim($row['ceny_s_gsm_danet']) == 'нет') ? 0 : 1;
            $telematics_type = (trim($row['telematika']) == 'нет') ? null : $row['telematika'];
            [$category, $brand, $model] = $this->findOrCreateItems(trim($row['kategoriya']), trim($row['marka']), trim($row['model']));

            $machinery = Machinery::query()->where([
                'serial_number' => trim($row['invent']),
                'company_branch_id' => $this->branch->id
            ])->first();
            $subOwnerId = null;
            $subOwnerType = null;
            $owner = null;
            if(!empty($row['sobstvennik_vladelets'])){
                $contractor = Contractor::query()->forBranch($this->branch->id)->where('company_name', $row['sobstvennik_vladelets'])->first();
                $subOwnerId = $contractor->id;
                $subOwnerType = Contractor::class;
                $owner = $row['sobstvennik_vladelets'];
            }

            $data = [
//                'sub_owner_id' => $subOwnerId,
//                'sub_owner_type' => $subOwnerType,
//                'owner' => $owner,
                'contractor_id' => $subOwnerId,
                'insurance_premium_cost' => $row['straxovaya_premiya_summa_vkl_nds'],
                'delivery_radius' => $row['dostupnaya_zona_dostavki_km'],
                'category_id' => $category->id,
                'model_id' => $model->id,
                'brand_id' => $brand->id,
                'base_id' => $base_id,
                'default_base_id' => $base_id,
                'board_number' => $row['artikul'],
                'serial_number' => $row['invent'],
                'change_hour' => $row['dlitelnost_smeny_casy'],
                'min_order' => $row['minimalnyi_zakaz_kol_vo'],
                'min_order_type' => $min_order_type,
                'price_includes_fas' => $price_includes_fas,
                'telematics_type' => $telematics_type,
                'market_price' => $row['rynocnaya_stoimost_texniki'],
                'pledge_cost' => $row['zalog_v_tc_nds'],
                'company_branch_id' => $this->branch->id,
                'shift_duration' => $row['dlitelnost_smeny_casy'],
                'name' => null,
                'coordinates' => null,
                'machine_type' => $category->type,
                'address' => '',

                'region_id' => $region->id,
                'city_id' => $city->id,

                'wialon_telematic' => null,
                'vin' => '',

                'optional_attributes' => [],
                'waypoints_price' => [],
                'driver_prices' => [],
                'tariff_type' => Tariff::TIME_CALCULATION,
                'description' => '',


                'available_for_sale' => false,
                'rent_with_driver' => false,
                'selling_price' => 0,
                'market_price_currency' => null,

                'free_delivery_distance' => 0,
                'delivery_cost_over' => 0,

                'is_rented' => true,
                'is_rented_in_market' => false,
                'show_market_price' => true,
                'show_company_market_price' => true,


                'is_contractual_delivery' => 0,
                'contractual_delivery_cost' => 0,

                'number' => '',
                'scans' => '',
                'photo' => '',
                'prices' => [
                    [
                        'id' => $this->getPriceId($machinery, $units, 'hour'),
                        'unit_compare_id' => $units->where('type', 'hour')->first()->id,
                        'is_fixed' => true,
                        'min' => $row['minimalnyi_zakaz_kol_vo'],
                        'grid_prices' => [
                            'cash' => $row['tarif_cas_nalicnye'],
                            'cashless_vat' => $row['tarif_cas_beznalicnye_nds_20'],
                            'cashless_without_vat' => $row['tarif_cas_beznalicnye_nds_0'],

                        ],
                    ],
                    [
                        'id' => $this->getPriceId($machinery, $units, 'shift'),
                        'unit_compare_id' => $units->where('type', 'shift')->first()->id,
                        'is_fixed' => true,
                        'min' => $row['minimalnyi_zakaz_kol_vo'],
                        'grid_prices' => [
                            'cash' => $row['tarif_smena_nalicnye'],
                            'cashless_vat' => $row['tarif_smena_beznalicnye_nds_20'],
                            'cashless_without_vat' => $row['tarif_smena_beznalicnye_nds_0'],

                        ],
                    ]
                ]
            ];

            $vehicleService = new VehicleService($this->branch);
            $vehicleService->setData($data);
            if ($machinery) {
                $vehicleService->updateVehicle($machinery->id);
            } else {
                $vehicleService->createVehicle();
            }

        }
//        } catch (\Exception $e) {
//            $this->logger->error($e->getMessage().' in '.$e->getFile().' '.$e->getLine());
//            throw new \Exception('Ошибка импорта: '.$e->getMessage().' in '.$e->getFile().' '.$e->getLine());
//        }
    }

    function findOrCreateItems($categoryName, $brandName, $modelName = null)
    {
       $category = Type::query()->where('name', 'like' ,"%{$categoryName}%")->first();
       $brand = Brand::query()->where('name', 'like' ,"%{$brandName}%")->first();
       $machineryModel = MachineryModel::query()->where('name', 'like' ,"%{$modelName}%")->first();
        if (!$brand) {
            $brand = \App\Machines\Brand::create([
                'name' => trim($brandName)
            ]);
        }
        if(!$category) {
            $category = Type::create([
                'type'             => 'equipment',
                'name'             => trim($categoryName),
                'name_style'       => $categoryName,
                'eng_alias'        => generateChpu($categoryName),
                'alias'            => generateChpu($categoryName),
                'vin'              => 0,
                'rent_with_driver' => 0,
                'licence_plate'    => 0,
            ]);
        }
        if(!$machineryModel && $modelName) {
            $machineryModel = \App\Machines\MachineryModel::create([
                'category_id' => $category->id,
                'brand_id'    => $brand->id,
                'name'        => trim($modelName),
                'alias'       => generateChpu($modelName)
            ]);
        }
        return [
            $category,
            $brand,
            $machineryModel
        ];
    }

    public function chunkSize(): int
    {
        return 100;
    }

    private function getPriceId($machinery, $units, $type)
    {
        if ($machinery && $machinery->prices->where('unit_compare_id', $units->where('type', $type))->count() > 0) {
            return $machinery->prices->where('unit_compare_id', $units->where('type', $type))->first()->id;
        }
        return null;
    }
}

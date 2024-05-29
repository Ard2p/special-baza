<?php

namespace Modules\ContractorOffice\Http\Controllers;

use App\Machinery;
use App\Service\RequestBranch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\ContractorOffice\Entities\Sets\MachinerySet;
use Modules\ContractorOffice\Entities\Sets\MachinerySetEquipment;
use Modules\ContractorOffice\Entities\Sets\MachinerySetPart;
use Modules\ContractorOffice\Http\Requests\MachinerySetRequest;
use Modules\ContractorOffice\Transformers\MachinerySetResource;
use Modules\PartsWarehouse\Entities\Stock\Item;
use Modules\PartsWarehouse\Entities\Stock\Stock;
use Modules\PartsWarehouse\Services\RentService;
use Modules\PartsWarehouse\Transformers\RentPartResource;

class MachinerySetController extends Controller
{

    private $companyBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch->companyBranch;

        $block = $this->companyBranch->getBlockName(CompanyRoles::BRANCH_VEHICLES);

        $this->middleware("accessCheck:{$block},".CompanyRoles::ACTION_SHOW)->only('index', 'show');
        $this->middleware("accessCheck:{$block},".CompanyRoles::ACTION_CREATE)->only(['store', 'update']);
        $this->middleware("accessCheck:{$block},".CompanyRoles::ACTION_DELETE)->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $query = MachinerySet::forBranch()->with('equipments.category');

        if ($request->filled('category_id')) {
            $query->whereHas('equipments', function ($q) use (
                $request
            ) {
                $q->where('category_id', $request->input('category_id'));
            });
        }
        return $request->filled('no_pagination')
            ? $query->get()
            : $query->paginate($request->per_page
                ?: 10);
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request  $request
     * @return Response
     */
    public function store(MachinerySetRequest $request)
    {
        $data = $request->validated();

        \DB::beginTransaction();

        $set = MachinerySet::create([
            'name' => $data['name'],
            'company_branch_id' => $this->companyBranch->id,
            'prices' => [
                'delivery_cost' => numberToPenny($data['prices']['delivery_cost']),
                'return_delivery_cost' => numberToPenny($data['prices']['return_delivery_cost']),
                'cash' => numberToPenny($data['prices']['cash']),
                'cashless_without_vat' => numberToPenny($data['prices']['cashless_without_vat']),
                'cashless_vat' => numberToPenny($data['prices']['cashless_vat']),
            ]
        ]);

        foreach ($data['equipments'] as $equipment) {
            $eq = new MachinerySetEquipment([
                'model_id' => $equipment['modal_id'] ?? null,
                'machinery_set_id' => $set->id,
                'brand_id' => $equipment['brand_id'] ?? null,
                'category_id' => $equipment['category_id'],
                'count' => $equipment['count'],
            ]);

            $eq->save();

            if (!empty($equipment['parts'])) {
                foreach ($equipment['parts'] as $part) {
                    $partFields = [
                        'part_id' => $part['part_id'],
                        'count' => $part['count'],
                        'machinery_set_equipment_id' => $eq->id
                    ];

                    MachinerySetPart::create($partFields);
                }

            }
        }
        \DB::commit();
        return;
    }

    /**
     * Show the specified resource.
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        return MachinerySetResource::make(MachinerySet::query()->with([
            'equipments.parts',
            'equipments.parts.part'
        ])->forBranch()->findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(
        MachinerySetRequest $request,
        $id
    ) {
        $set = MachinerySet::query()->forBranch()->findOrFail($id);

        $data = $request->validated();

        \DB::beginTransaction();

        $set->update([
            'name' => $data['name'],
            'company_branch_id' => $this->companyBranch->id,
            'prices' => [
                'delivery_cost' => numberToPenny($data['prices']['delivery_cost']),
                'return_delivery_cost' => numberToPenny($data['prices']['return_delivery_cost']),
                'cash' => numberToPenny($data['prices']['cash']),
                'cashless_without_vat' => numberToPenny($data['prices']['cashless_without_vat']),
                'cashless_vat' => numberToPenny($data['prices']['cashless_vat']),
            ]
        ]);
        $set->equipments()->delete();

        foreach ($data['equipments'] as $equipment) {
            $fields = [
                'model_id' => $equipment['model_id'] ?? null,
                'machinery_set_id' => $set->id,
                'category_id' => $equipment['category_id'],
                'brand_id' => $equipment['brand_id'] ?? null,
                'count' => $equipment['count'],
            ];
            $eq = new MachinerySetEquipment($fields);

            $eq->save();

            if (!empty($equipment['parts'])) {
                foreach ($equipment['parts'] as $part) {
                    $partFields = [
                        'part_id' => $part['part_id'],
                        'count' => $part['count'],
                        'machinery_set_equipment_id' => $eq->id
                    ];

                    MachinerySetPart::create($partFields);
                }

            }
        }

        \DB::commit();
    }

    /**
     * Remove the specified resource from storage.
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }

    function scaffolding(Request $request)
    {
        $parts = [];

        $rentService = new RentService();
        $dateFrom = Carbon::parse($request->input('date_from'));
        $orderDuration = $request->input('order_duration');

        $items = $rentService->getRentPartsCountForPeriod($dateFrom->format('Y-m-d'),
            $dateFrom->addDays($orderDuration)->format('Y-m-d'), 'scaffolding', $request->get('stock_id'));

        foreach ($items as $item) {
            $count = 0;
            switch ($item->part->name) {
                case 'Рама с лестницей':
                    $count = ($request->cells_by_height * $request->ascents) * $request->count;
                    break;
                case 'Связь диагональная':
                    $count = ($request->cells_by_height * $request->cells_by_length) * $request->count;
                    break;
                case 'Рама проходная':
                    $count = (($request->cells_by_height * ($request->cells_by_length + 1)) - ($request->cells_by_height * $request->ascents)) * $request->count;
                    break;
                case 'Связь горизонтальная':
                    $count = ($request->cells_by_height * $request->cells_by_length * 2) * $request->count;
                    break;
                case 'Ригель настила':
                    $count = ($request->tiers_with_decking * $request->cells_by_length * 2) * $request->count;
                    break;
                case 'Настил деревянный':
                    $count = ($request->facade_length * $request->tiers_with_decking) * $request->count;
                    break;
            }

            $item->count = $count;
            $parts[] = RentPartResource::make($item);
        }
        return ['result' => $parts];
    }

    function search(Request $request)
    {
        $request->validate([
            'machinery_set_id' => 'required|exists:machinery_sets,id',
            'order_type' => 'required|in:month,shift,hour',
            'order_duration' => 'required|numeric|min:1',
            'date_from' => 'required|date',
            'base_id' => 'required|exists:machinery_bases,id',
            'start_time' => 'required|date_format:H:i',
            'coordinates' => 'required',
        ]);

        /** @var MachinerySet $set */
        $set = MachinerySet::query()->with('equipments.category')->forBranch()->findOrFail($request->input('machinery_set_id'));

        $dateFrom = Carbon::parse("{$request->input('date_from')} {$request->input('start_time')}");
        $dateTo = getDateTo($dateFrom, $request->input('order_type'), $request->input('order_duration'));

        $coordinates = explode(',', $request->input('coordinates'));

        $parts = collect(Item::getParts(null, Stock::query()
            ->where('machinery_base_id', $request->input('base_id'))
            ->pluck('id')
            ->toArray()
        ));

        $selectedMachinery = $request->input('skip') ?: [];

        $packs = [];
        while ($set->equipments->count()) {
            $machinery = collect();

            foreach ($set->equipments as $equipment) {

                $result = Machinery::query()->forBranch()
                    ->categoryBrandModel($equipment->category_id, $equipment->machineryModel->brand_id ?? null,
                        $equipment->machineryModel->id ?? null)
                    ->whereInCircle($coordinates[0], $coordinates[1])
                    ->checkAvailable($dateFrom, $dateTo, $request->input('order_type'),
                        $request->input('order_duration'))
                    ->take($equipment->count)
                    ->where('base_id', $request->input('base_id'))
                    ->whereNotIn('id', $selectedMachinery)
                    ->get();


                $result =
                    $result->map(function ($item) use (
                        $equipment,
                        $parts,
                        &$selectedMachinery
                    ) {
                        $selectedMachinery[] = $item->id;
                        $item->services = $item->_type->services()->forBranch()->get()->map(function ($service) {
                            $service->price /= 100;
                            return $service;
                        });
                        $item->parts = collect();

                        foreach ($equipment->parts as $part) {
                            $neededParts = $parts->where('part_id', $part->part_id);
                            $remain = $part->count;
                            foreach ($neededParts as &$neededPart) {
                                if ($neededPart['available_amount'] >= $remain) {
                                    $neededPart['amount'] = $remain;
                                    $neededPart['available_amount'] = 0;
                                    $item->parts->push($neededPart);
                                    continue 2;
                                }

                                $remain -= $neededPart['available_amount'];
                                $neededPart['available_amount'] = 0;


                                $item->parts->push($neededPart);
                            }
                        }

                        return $item;
                    });

                if ($result->count() < $equipment->count) {


                    break 2;
                    /*  $need = $equipment->count - $result->count();
                      throw ValidationException::withMessages([
                          'errors' => "Для формирования комплекта на указанные даты отсутсвует '{$equipment->category->name}' в размере {$need} ед."
                      ]);*/
                }
                $machinery = $machinery->merge($result);
            }
            $packs[] = $machinery->toArray();
        }
        return response()->json([
            'set' => $set,
            'result' => $packs
        ]);
    }
}

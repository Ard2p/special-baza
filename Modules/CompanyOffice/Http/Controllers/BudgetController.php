<?php

namespace Modules\CompanyOffice\Http\Controllers;

use App\Service\RequestBranch;
use App\User;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Modules\CompanyOffice\Entities\Budget;
use Modules\CompanyOffice\Entities\CashRegister;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\ContractorOffice\Entities\Vehicle\MachineryBase;
use Modules\ContractorOffice\Services\Shop\SaleService;
use Modules\Dispatcher\Entities\DispatcherInvoice;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\Service\ServiceCenter;
use Modules\PartsWarehouse\Entities\Shop\Parts\PartsSale;

class BudgetController extends Controller
{
    /** @var CompanyBranch */
    private $currentBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->currentBranch = $companyBranch->companyBranch;


        $block = $this->currentBranch->getBlockName(CompanyRoles::BRANCH_DASHBOARD);

        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only(
            [
                'index',
                'show',
            ]);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_UPDATE)->only(
            [
                'store',
                'update',
                'destroy',

            ]);

    }

    public function index(Request $request, $id)
    {
        $request->validate(
            [
                'year' => 'required|in:2022,2023',
                'type' => 'required|in:rent,service,parts_sale',
                'entity' => 'required|in:manager,base',
            ]);
        $entity = $request->input('entity') === 'manager' ? User::class : MachineryBase::class;

        $start = Carbon::createFromFormat('Y', $request->input('year'));
        $monthRange = CarbonPeriod::create($start->startOfYear(), '1 month', $start->clone()->endOfYear());


            /** @var Collection $budgets */
            $budgets = Budget::query()->forBranch()
                ->where('year', $request->input('year'))
                ->where('type', $request->input('type'))
                ->whereHasMorph('owner', [$entity])
                ->get();

            $factBudget = collect();
            $searchEntity = match ($request->input('type')) {
              'rent' => 'order',
              'service' => 'service',
              'parts_sale' => 'parts',
            };
            if($entity === User::class) {
                $entities = $this->currentBranch->employees;

                foreach ($entities as $entity) {
                    foreach ($monthRange as $month) {
                        $cashQuery = CashRegister::query()
                            ->where('creator_id',  $entity->id)
                            ->where('ref', 'like', "%{$searchEntity}%")
                            //->whereHasMorph('owner', [$searchEntity], fn($q) => $q->where("{$searchEntityTable}.creator_id", $entity->id))
                            ->whereDate('created_at', '>=', $month->startOfMonth())
                            ->whereDate('created_at', '<=', $month->clone()->endOfMonth());
                        $cashOutQuery = clone $cashQuery;

                        $factBudget->push([
                            'owner_id' => $entity->id,
                            'year' => $request->input('year'),
                            'month' => $month->format('F'),
                            'sum' => ($cashQuery
                                ->where('type', 'in')
                                ->sum('sum')
                            -  $cashOutQuery
                                    ->where('type', 'out')
                                    ->sum('sum'))
                    ]);
                    }

                }
            }else {
                $bases = MachineryBase::query()->forBranch()->get();
                foreach ($bases as $base) {
                    foreach ($monthRange as $month) {
                        $cashInQuery = CashRegister::query()
                            ->where('machinery_base_id',  $base->id)
                            ->where('ref', 'like', "%{$searchEntity}%")
                            //->whereHasMorph('owner', [$searchEntity], fn($q) => $q->where("{$searchEntityTable}.creator_id", $entity->id))
                            ->whereDate('created_at', '>=', $month->startOfMonth())
                            ->whereDate('created_at', '<=', $month->clone()->endOfMonth());
                        $cashOutQuery = clone $cashInQuery;

                        $factBudget->push([
                            'type' => 'base',
                            'owner_id' => $base->id,
                            'year' => $request->input('year'),
                            'month' => $month->format('F'),
                            'sum' => ($cashInQuery
                                    ->where('type', 'in')
                                    ->sum('sum')
                                -  $cashOutQuery
                                    ->where('type', 'out')
                                    ->sum('sum'))
                        ]);
                    }
                }
            }


        return response()->json([
            'fact' => $factBudget,
            'plan' => $budgets,
        ]);
    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request, $id)
    {
        $request->validate([
            'type' => 'required|in:rent,service,parts_sale',
            'owner_type' => 'required|in:manager,base',
            'owner_id' => 'required',
            'month' => 'required',
            'year' => 'required',
            'sum' => 'required|numeric|min:0',
        ]);
        $ownerType = $request->input('owner_type') === 'manager' ? User::class : MachineryBase::class;

       $budget = Budget::query()->forBranch()
           ->where('owner_type', $ownerType)
           ->where('owner_id', $request->input('owner_id'))
           ->where('month', $request->input('month'))
           ->where('type', $request->input('type'))
           ->delete();

       Budget::create([
           'company_branch_id' => $this->currentBranch->id,
           'year' => $request->input('year'),
           'owner_type' => $ownerType,
           'owner_id' => $request->input('owner_id'),
           'month' => $request->input('month'),
           'type' => $request->input('type'),
           'sum' => numberToPenny($request->input('sum')),
       ]);


      // $budget = Budget::query()->forBranch()->where('year', $request->input('year'))
      //     ->where('month', $request->input('month'))
      //     ->where('direction', $request->input('direction'))
      //     ->where('type', $request->input('type'))
      //     ->first();
      // if (!$budget) {
      //     Budget::create([
      //         'company_branch_id' => $this->currentBranch->id,
      //         'year' => $request->input('year'),
      //         'direction' => $request->input('direction'),
      //         'month' => $request->input('month'),
      //         'type' => $request->input('type'),
      //         'sum' => numberToPenny($request->input('sum')),
      //     ]);
      // }

        return response()->json();

    }

}

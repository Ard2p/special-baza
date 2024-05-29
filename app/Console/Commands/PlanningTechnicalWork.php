<?php

namespace App\Console\Commands;

use App\Events\ServiceCenterCreatedEvent;
use App\Machinery;
use App\Machines\FreeDay;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\ContractorOffice\Entities\Vehicle\TechnicalWork;
use Modules\Orders\Entities\Service\ServiceCenter;

class PlanningTechnicalWork extends Command
{
    protected $signature = 'technical-work:plan';

    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        Machinery::query()
            ->with(['plans' => fn($q) => $q->where('active', true), 'lastTechnicalWork'])
            ->whereDoesntHave('lastTechnicalWork.periods', function (Builder $builder) {
                $builder->where(function ($q) {
                    $q->forPeriod(now(), now())->orWhere('startDate', '>', now());
                });
        })->whereHas('plans', function ($builder) {
            $builder->where('active', true);
        })->whereHas('lastTechnicalWork')->chunkById(10, function (Collection $machineries) {
            /** @var Machinery $machinery */
            foreach ($machineries as $machinery) {

                /** @var TechnicalWork $lastWork */
                $lastWork = $machinery->lastTechnicalWork;

                $currentPlan = null;
                foreach ($machinery->plans as $plan) {
                   if($lastWork) {
                       $dF = $lastWork->dateFrom;
                       $lastRent = FreeDay::query()->whereHas('orderWorker', function ($q) use ($dF, $machinery) {
                           $q->where('worker_id', $machinery->id)
                               ->where('worker_type', Machinery::class)
                               ->forPeriod(Carbon::parse($dF), now());

                       })->count();
                   }
                    $lastRent = $lastRent ?? 0;
                    $daysDiff = $lastWork ? Carbon::parse($lastWork->dateFrom)->diffInDays(now()) : 0;
                    $engineHoursDiff = $machinery->engine_hours_after_tw -  $lastWork?->engine_hours ?? 0;
                    if($plan->type === 'engine_hours' && $engineHoursDiff > $plan->duration_between_works) {
                         $currentPlan = $plan;
                    }
                    if($plan->type === 'days' && $daysDiff > $plan->duration_between_works) {
                        $currentPlan = $plan;
                    }
                      if($plan->type === 'rent_days' && $lastRent > $plan->duration_between_works) {
                          $currentPlan = $plan;
                      }
                }

                if(!$currentPlan) {
                    continue;
                }

                $duration = now()->startOfDay()->setTimeFrom($currentPlan->duration)->diffInMinutes(now()->startOfDay());
                $df = Carbon::parse($lastWork->dateTo)->addDays($currentPlan->duration_between_works + $currentPlan->duration_plan)->setTimeFrom('08:00');
                $dt = $df->clone()->addMinutes($duration);
                /** @var CompanyBranch $branch */
                $branch = $machinery->company_branch;
                $requisite = $branch->requisite->first();
                DB::beginTransaction();

                $center = new ServiceCenter([
                    'is_plan'              => true,
                    'is_warranty'              => false,
                    'name'              => 'Тех. обсуживание',
                    'type'              => 'in',
                    'phone'             => '',
                    'contact_person'    => $requisite->contact_person,
                    'bank_requisite_id'    => $requisite->bank_requisite_id,
                    'status_tmp'        => 'new',
                    'address'           =>  '',
                    'address_type'      => '',
                    'date_from'         => $df,
                    'date_to'           => $dt,
                    'creator_id'        => $machinery->creator_id,
                    'documents_pack_id' => $branch->documentsPack()->first()->id,
                    'base_id'           => $machinery->base->id,
                    'machinery_id'      => $machinery->id,
                    'comment'      =>  '',
                    'company_branch_id' => $branch->id
                ]);

                $center->save();


                    /** @var TechnicalWork currentService */
                    $service = TechnicalWork::create([
                        'engine_hours' => ($lastWork?->engine_hours ?? 0) + $machinery->engine_hours_after_tw,
                        'type' => 'maintenance',
                        'description' => '',
                        'machinery_id' => $machinery->id,
                        'service_center_id' => $center->id,
                    ]);
                    FreeDay::create([
                        'startDate' => $dt,
                        'endDate' => $df,
                        'type' => 'busy',
                        'machine_id' => $machinery->id,
                        'creator_id' => $machinery->creator_id,
                        'technical_work_id' => $service->id
                    ]);

                $center->customer()->associate($branch);
                $center->save();

                DB::commit();
                ServiceCenterCreatedEvent::dispatch($center);
            }
        });
        return 0;
    }
}

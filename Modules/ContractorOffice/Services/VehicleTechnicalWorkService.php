<?php


namespace Modules\ContractorOffice\Services;


use App\City;
use App\Machinery;
use App\Machines\Brand;
use App\Machines\FreeDay;
use App\Machines\MachineryModel;
use App\Machines\Type;
use App\Support\Gmap;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\ContractorOffice\Entities\CompanyWorker;
use Modules\ContractorOffice\Entities\System\Tariff;
use Modules\ContractorOffice\Entities\Vehicle\TechnicalWork;
use Modules\Integrations\Entities\WialonVehicle;


class VehicleTechnicalWorkService
{

    private $machinery;
    public $currentService;

    public function __construct(Machinery $machinery)
    {
        $this->machinery = $machinery;
    }


    function addService($type, $mechanicIds = [], $engineHours = null)
    {
        $workers = CompanyWorker::query()->forBranch($this->machinery->company_branch->id)->whereIn('id', $mechanicIds ?: [])->pluck('id');
        $max = $this->machinery->technicalWorks()->selectRaw('*, CAST(engine_hours as DECIMAL)')->max('engine_hours');
        if($max > $engineHours) {
            $error =  ValidationException::withMessages([
                'engine_hours' => [trans('transbaza_machine_edit.engine_hours_warn', ['count' => $max])]
            ]);

            throw $error;
        }
        /** @var TechnicalWork currentService */
        $this->currentService = TechnicalWork::create([
            'engine_hours' => $engineHours,
            'type' => $type,
            'description' => '',
            'machinery_id' => $this->machinery->id,
        ]);

        $this->currentService->mechanics()->sync($workers);

        return $this;
    }

    function addPeriod(Carbon $dateFrom, Carbon $dateTo)
    {
        return FreeDay::create([
            'startDate' => $dateFrom,
            'endDate' => $dateTo,
            'type' => 'busy',
            'machine_id' => $this->machinery->id,
            'creator_id' => Auth::check() ? Auth::id() : null,
            'technical_work_id' => $this->currentService->id
        ]);
    }

}

<?php

namespace Modules\ContractorOffice\Http\Controllers;

use App\Machinery;
use App\Machines\FreeDay;
use App\Service\RequestBranch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\CompanyOffice\Services\ContactsService;
use Modules\ContractorOffice\Entities\CompanyWorker;
use Modules\ContractorOffice\Entities\System\DrivingCategory;
use Modules\ContractorOffice\Http\Requests\CompanyWorkerRequest;
use Modules\ContractorOffice\Services\DriverService;
use Modules\ContractorOffice\Transformers\MachineryEvent;

class WorkersController extends Controller
{
    private $companyBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch->companyBranch;

        $block = $this->companyBranch->getBlockName(CompanyRoles::BRANCH_CLIENTS);

        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only('index');
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_CREATE)->only(['store', 'update']);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_DELETE)->only(['destroy']);

    }


    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $workers = CompanyWorker::query()->with(['contacts'])->forBranch()
        ->orderBy('id', 'desc');
        if($request->filled('type')) {
            $workers->where('type', $request->input('type'));
        }
        return \Modules\ContractorOffice\Transformers\CompanyWorker::collection(
            $request->filled('noPagination')
            ? $workers->get()
            : $workers->paginate($request->per_page ?: 15));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(CompanyWorkerRequest $request)
    {

        \DB::beginTransaction();

        $worker = CompanyWorker::create([
            'type' => $request->input('type'),
            'photos' => $request->input('photos'),
            'company_branch_id' => $this->companyBranch->id,
            'passport_number' => $request->input('passport_number'),
            'passport_scans' => $request->input('passport_scans'),
            'passport_place_of_issue' => $request->input('passport_place_of_issue'),
            'passport_date_of_issue' => $request->input('passport_date_of_issue'),
        ]);

        $service = new ContactsService($this->companyBranch);
        $service->createContact($request->input('contact'), $worker);

        if($worker->type === CompanyWorker::TYPE_DRIVER)
        {
            $service = new DriverService($worker);

            $service
                ->saveDocument($request->input('driver_document'))
                ->setDrivingCategories($request->input('driver_document.driving_categories'))
            ->setMachineryCategories($request->input('driver_document.machinery_categories'));
        }

        \DB::commit();


        return  response()->json($worker);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return CompanyWorker::query()->forBranch()->findOrFail($id);
    }


    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(CompanyWorkerRequest $request, $id)
    {
        $worker = CompanyWorker::forBranch()->findOrFail($id);
        \DB::beginTransaction();

        $worker->update([
            'type' => $request->input('type'),
            'photos' => $request->input('photos'),
            'passport_number' => $request->input('passport_number'),
            'passport_scans' => $request->input('passport_scans'),
            'passport_place_of_issue' => $request->input('passport_place_of_issue'),
            'passport_date_of_issue' => $request->input('passport_date_of_issue'),
        ]);

        $service = new ContactsService($this->companyBranch);
        $service->updateContact($worker->contact->id, $request->input('contact'));

        if($worker->type === CompanyWorker::TYPE_DRIVER)
        {
            $service = new DriverService($worker);

            $service
                ->saveDocument($request->input('driver_document'))
                ->setDrivingCategories($request->input('driver_document.driving_categories'))
                ->setMachineryCategories($request->input('driver_document.machinery_categories'));
        }

        \DB::commit();

        return  response()->json($worker);
    }

    function getMachinery(Request $request, $id)
    {
        /** @var CompanyWorker $worker */
        $worker = CompanyWorker::forBranch()->findOrFail($id);

        return $worker->machinery()->paginate($request->per_page ?: 15);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }

    function getInitial()
    {
        $licences = DrivingCategory::forDomain()->get();
        return $licences;
    }


    function attachMachinery(Request $request, $id)
    {
        /** @var CompanyWorker $worker */
        $worker = CompanyWorker::forBranch()->findOrFail($id);
        $machinery = Machinery::forBranch()->findOrFail($request->input('machinery_id'));

        $worker->machinery()->syncWithoutDetaching([$machinery->id]);

        return response()->json();
    }


    function detachMachinery(Request $request, $id)
    {
        /** @var CompanyWorker $worker */
        $worker = CompanyWorker::forBranch()->findOrFail($id);
        $machinery = Machinery::forBranch()->findOrFail($request->input('machinery_id'));
        $worker->machinery()->detach([$machinery->id]);

        return response()->json();
    }

    function getEvents(Request $request, $id)
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date',
        ]);

        $dateFrom = Carbon::parse($request->input('date_from'));
        $dateTo = Carbon::parse($request->input('date_to'));

        /** @var CompanyWorker $worker */
        $worker = CompanyWorker::forBranch()->findOrFail($id);

        $events = FreeDay::query();
            if($worker->type === CompanyWorker::TYPE_DRIVER) {

                $events->whereHas('order', function ($q) use ($id) {

                    $q->whereHas('components', function ($q) use ($id) {
                        $q->where('company_worker_id', $id);
                    });

                });

            }else {
                $events->whereHas('technicalWork', function ($q) use ($id) {

                    $q->whereHas('mechanics', function ($q) use ($id) {
                        $q->where('company_worker_id', $id);
                    });

                });
            }

        $events->forPeriod($dateFrom->copy(), $dateTo->copy());

        $allEvents = $events->get();
        $allEvents = $allEvents->map(function ($item, $key) {
            $item['ev_id'] = "ev-{$key}";

            return $item;
        });

        return response()->json(MachineryEvent::collection($allEvents));
    }

    function getAvailable(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date',
            'machinery_id' => 'nullable|exists:machineries,id',
            'type' => 'required|in:' . implode(',', [
                 CompanyWorker::TYPE_DRIVER,
                 CompanyWorker::TYPE_MECHANIC
                ])
        ]);

        $dateFrom = Carbon::parse($request->input('date_from'));
        $dateTo = Carbon::parse($request->input('date_to'))->setTimezone(config('app.timezone'));

        /** @var Builder $workers */
        $workers = CompanyWorker::query()->forBranch()->whereType($request->input('type'));

        $isOperator = toBool($request->input('worker_operator'));

        if(!$isOperator) {
            $workers->checkAvailable($dateFrom, $dateTo, $request->input('type'));
        }

        if($request->filled('machinery_id')) {
            $workers->whereHas('machinery', function ($q) use ($request) {
               $q->where('machineries.id', $request->input('machinery_id'));
            });
        }

        return $workers->get();
    }
}

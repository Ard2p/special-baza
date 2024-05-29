<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Helpers\RequestHelper;
use App\Machinery;
use App\Service\DaData;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Services\CompaniesService;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\ContractorOffice\Entities\Vehicle\Price;
use Modules\ContractorOffice\Http\Controllers\CalendarController;
use Modules\ContractorOffice\Services\Tariffs\TimeCalculation;
use Modules\ContractorOffice\Services\VehicleService;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\Lead;
use Modules\Dispatcher\Jobs\NewLeadNotifcations;
use Modules\Dispatcher\Services\LeadService;
use Modules\Marketplace\Http\Requests\CompanyMarketplaceRequest;
use Modules\RestApi\Entities\Domain;

class MarketplaceController extends Controller
{

    /** @var Domain */
    private $domain;

    public function __construct(Request $request)
    {
        $this->domain = RequestHelper::requestDomain();
    }

    function makeRequest(CompanyMarketplaceRequest $request)
    {

        $user = Auth::guard('api')->check()
            ? Auth::guard('api')->user()
            : User::register($request->email, $request->phone, null, $request->contact_person);

        $daData = new DaData();
        $company =
            $this->domain->alias === 'ru'
                ? $daData->searchByInn($request->input('company.inn'), $request->input('company.type'))
                : null;

        if (!Auth::guard('api')->check()) {

            // Auth::onceUsingId($user->id);
            $service = CompaniesService::createCompany($user, $this->domain->id, $request->input('company.name'));

            $branch =
                $service->createBranch($request->input('company.name'), $request->input('region_id'), $request->input('city_id'));

            $requisite = [];

            $requisite['creator_id'] = $branch->creator_id;

            if ($this->domain->alias === 'ru' && !empty($company->suggestions[0])) {
                $input = $company->suggestions[0];

                $requisite['name'] = $input->value;
                $requisite['inn'] = $request->input('company.inn');
                $requisite['kpp'] = $input->data->kpp ?? null;
                $requisite['ogrn'] = $input->data->ogrn ?? null;

                $request->input('company.type') === 'legal'
                    ? $branch->addLegalRequisites($requisite)
                    : $branch->addIndividualRequisites($requisite);
            } else {
                $requisite['user_id'] = $branch->creator_id;
                $requisite['account'] = $request->input('company.inn');
                $requisite['account_name'] = $request->input('company.name');
                $branch->addLegalRequisites($requisite);
            }


        } else {

            $branch = $request->input('company_branch_id')
                ? CompanyBranch::query()->userHasAccess($user->id, '*')->findOrFail($request->input('company_branch_id'))
                : Customer::query()->whereHas('corpUsers', function (Builder $q) use
                (
                    $user
                ) {
                    $q->where('users.id', $user->id);
                })->findOrFail($request->input('customer_id'));
        }
        $coordinates = explode(',', $request->coordinates);
        $requestVehicles = collect($request->input('vehicles'));


        $vehicles = Machinery::query()
            ->whereIn('id', $requestVehicles->pluck('id'))
            ->whereInCircle($coordinates[0], $coordinates[1])
            ->get()->groupBy('company_branch_id');

        if ($requestVehicles->count() !== $vehicles->count()) {
            $error = ValidationException::withMessages([
                'errors' => ['Техника не найдена или точка не в радиусе доступности.']
            ]);

            throw $error;
        }


        if ($branch instanceof CompanyBranch) {
            if ($this->domain->alias === 'ru' && empty($company->suggestions[0])) {
                $error = ValidationException::withMessages([
                    'errors' => ['Данные компании не найдены. Проверьте правильность ИНН.']
                ]);

                throw $error;
            }
        }

        $daDataCompany =
            $this->domain->alias === 'ru' && $branch instanceof CompanyBranch
                ? $company->suggestions[0]
                : null;
        DB::beginTransaction();

        foreach ($vehicles as $companyBranchId => $vehicles) {

            /** @var CompanyBranch $contractorBranch */
            $contractorBranch = CompanyBranch::findOrFail($companyBranchId);

            if ($branch instanceof CompanyBranch) {
                $remoteCustomer =
                    Customer::query()->forBranch($contractorBranch->id)->whereHas('remoteCompanyBranch', function (
                        $q) use
                    (
                        $branch
                    ) {
                        $q->where($branch->getTable() . ".id", $branch->id);
                    })->first();
            } else {
                $remoteCustomer = $branch;
            }

            /** @var Customer $customer */
            $customer = $remoteCustomer
                ?: Customer::create([
                    'email'             => $user->email,
                    'company_name'      => $request->input('company.name'),
                    'contact_person'    => $request->input('contact_person'),
                    'phone'             => $user->phone,
                    'creator_id'        => $contractorBranch->creator_id,
                    'company_branch_id' => $contractorBranch->id,
                    'domain_id'         => $this->domain->id,
                ]);

            $contractorBranch->remoteCustomer()->syncWithoutDetaching($customer->id);
            $customer->corpUsers()->syncWithoutDetaching($user->id);
            if (!$remoteCustomer) {

                $requisite = [];

                $requisite['creator_id'] = $branch->creator_id;
                $requisite['company_branch_id'] = $customer->company_branch_id;

                if ($daDataCompany && $this->domain->alias === 'ru') {
                    $requisite['name'] = $daDataCompany->value;

                    $requisite['inn'] = $request->input('company.inn');
                    $requisite['kpp'] = $daDataCompany->data->kpp ?? null;
                    $requisite['ogrn'] = $daDataCompany->data->ogrn ?? null;

                    $request->input('company.type') === 'legal'
                        ? $customer->addLegalRequisites($requisite)
                        : $customer->addIndividualRequisites($requisite);
                } else {
                    $requisite['user_id'] = $branch->creator_id;
                    $requisite['account'] = $request->input('company.inn');
                    $requisite['account_name'] = $request->input('company.name');
                    $customer->addLegalRequisites($requisite);
                }
            }

            $positions = [];
            foreach ($vehicles as $vehicle) {
                $positionInfo = $requestVehicles->where('id', $vehicle->id)->first();

                $positions[] = [
                    'id'                 => $vehicle->type,
                    'order_type'         => $positionInfo['order_type'],
                    'order_duration'     => $positionInfo['order_duration'],
                    'count'              => 1,
                    'machinery_model_id' => $vehicle->model_id,
                    'vehicle_id'         => $vehicle->id,
                    'date_from'          => $positionInfo['date_from'],
                    'start_time'         => $positionInfo['start_time'],

                ];
            }

            /** @var Customer $customer */

            $leadService = new LeadService();

            $leadService
                ->setSource(Lead::SOURCE_TB)
                ->setDispatcherCustomer($customer)
                ->createNewLead(array_merge([
                    'title'        => trans('transbaza_proposal.from_marketplace'),
                    'phone'        => $customer->phone,
                    'email'        => $customer->email,
                    'pay_type'     => Price::TYPE_CASHLESS_VAT,
                    'address'      => $request->input('address'),
                    'city_id'      => $request->input('city_id'),
                    'region_id'    => $request->input('region_id'),
                    'publish_type' => Lead::PUBLISH_MAIN,
                    'coordinates'  => $request->input('coordinates'),

                ], ['vehicles_categories' => $positions]), $contractorBranch->id, $contractorBranch->creator_id);
            dispatch(new NewLeadNotifcations($leadService->getLead()));
        }

        DB::commit();

        return response()->json();

    }

    function availableDates(
        Request $request,
                $id)
    {
        $request->validate([
            'duration'  => 'required|numeric|min:1',
            'date_from' => 'required|date'
        ]);

        /** @var Machinery $vehicle */
        $vehicle = Machinery::query()->findOrFail($id);

        $dates = $vehicle->getDatesForOrder(
            Carbon::parse($request->input('date_from')),
            $request->input('duration'),
            $request->input('type', TimeCalculation::TIME_TYPE_SHIFT)
        );

        return response()->json($dates);
    }

    function getEvents(
        Request $request,
                $machine_id)
    {
        /** @var Machinery $machinery */
        $machinery = Machinery::query()->findOrFail($machine_id);

        try {
            $dateFrom = Carbon::parse($request->input('date_from'));
            $dateTo = Carbon::parse($request->input('date_to'));
        } catch (\Exception $exception) {
            return response()->json([]);
        }


        $events = VehicleService::getEvents($machinery, $dateFrom, $dateTo, $request->input('filter'));
        $events = $events->map(function ($item) {
            $item['color'] = 'orange';
            $item['title'] = trans('contractors/edit.busy');

            return $item;
        });
        return response()->json($events);

    }

    function getActualDeliveryCost(
        Request $request,
                $id)
    {
        $request->validate([
            'distance' => 'required|numeric'
        ]);

        /** @var Machinery $machinery */
        $machinery = Machinery::query()->findOrFail($id);

        $forward = $machinery->calculateDeliveryCost(round($request->input('distance')), 'forward');
        $back = $machinery->calculateDeliveryCost(round($request->input('distance')), 'back');

        return response()->json([
            'delivery_cost'        => $forward,
            'return_delivery_cost' => $back,
        ]);
    }
}

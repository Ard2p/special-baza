<?php

namespace Modules\AdminOffice\Http\Controllers\Dispatcher;

use App\Finance\TinkoffMerchantAPI;
use App\Helpers\RequestHelper;
use App\Machinery;
use App\Machines\Brand;
use App\Machines\Type;
use App\Support\Region;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\AdminOffice\Entities\Filter;
use Modules\AdminOffice\Transformers\ClientLeadForOperator;
use Modules\AdminOffice\Transformers\LeadForOperator;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\Directories\Contractor;
use Modules\Dispatcher\Entities\Lead;
use Modules\Dispatcher\Http\Requests\AcceptDispatcherOffer;
use Modules\Dispatcher\Http\Requests\AcceptOffer;
use Modules\Dispatcher\Http\Requests\CreateLeadRequest;
use Modules\Dispatcher\Http\Requests\CreateMyOrderFromLead;
use Modules\Dispatcher\Http\Requests\SelectContrator;
use Modules\Dispatcher\Jobs\NewLeadNotifcations;
use Modules\Dispatcher\Services\LeadService;
use Modules\Orders\Entities\Order;
use Modules\Orders\Jobs\SendOrderInvoice;

class LeadsController extends Controller
{
    public function __construct(Request $request)
    {
        if ($request->filled('phone')) {
            $request->merge([
                'phone' => trimPhone($request->phone)
            ]);
        }

    }

    /**
     * Display a listing of the resource.
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function index(Request $request)
    {
        $leads = Lead::query()->with('city', 'user');

        $filter = new Filter($leads);

        $filter->getLike([
            'customer_name' => 'customer_name',
            'phone' => 'phone',
            'address' => 'address',
            'comment' => 'comment',
        ]);
        $leads->where('is_fast_order', 0);

        $leads->forDomain()->orderBy('created_at', 'desc');

        return $leads->paginate($request->per_page ?: 10);
    }

    function audits(Request $request, $id)
    {
        $lead = Lead::findOrFail($id);

        return $lead->audits()->with('user')->paginate($request->input('per_page', 10));

    }


    /**
     * Создание диспетчерской заявки из админки
     * @param CreateLeadRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function store(CreateLeadRequest $request)
    {
        $request->validated();

        $company_branch = CompanyBranch::findOrFail($request->input('company_branch_id'));

        $clientLead = toBool($request->input('client'));

        $leadService = new LeadService();

        DB::beginTransaction();


        if ($clientLead) {
            $leadService
                ->setCustomer($company_branch)
                ->createNewLead($request->all(), $company_branch->id,$request->input('creator_id') ?: Auth::id());

        } else {

            if ($request->filled('customer_id')) {
                $customer = Customer::forBranch($company_branch->id)->findOrFail($request->input('customer_id'));
            } else {
                $customer = Customer::create([
                    'email' => $request->input('email'),
                    'company_name' => $request->input('customer_name'),
                    'contact_person' => $request->input('contact_person'),
                    'phone' => $request->input('phone'),
                    'creator_id' => \Auth::id(),
                    'company_branch_id' => $company_branch->id,
                    'domain_id' => RequestHelper::requestDomain('id'),
                ]);
            }

            $leadService
                ->setDispatcherCustomer($customer)
                ->createNewLead($request->all(), $company_branch->id, $request->input('creator_id') ?: Auth::id());
        }

        $lead = $leadService->getLead();

        DB::commit();


        dispatch(new NewLeadNotifcations($lead));


        return response()->json($lead);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return Lead::with(['city' => function ($q) {
            $q->with('region');
        }])->findOrFail($id);


    }

    function info($id)
    {
        $lead = Lead::with(['city' => function ($q) {
            $q->with('region');
        }])->findOrFail($id);

        return $lead->customer instanceof Customer
            ? LeadForOperator::make($lead)
            : ClientLeadForOperator::make($lead);
    }


    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(CreateLeadRequest $request, $id)
    {
        $lead = Lead::whereStatus(Lead::STATUS_OPEN)->findOrFail($id);
//        dd($lead);
        if (!$lead->can_edit) {
            return response()->json(['errors' => ['Невозможно редактировать в текущем статусе.']], 400);
        }
        DB::beginTransaction();
        $lead->update([
            'customer_name' => $request->input('contact_person'),
            'phone' => $request->input('phone'),
            'address' => $request->input('address'),
            'comment' => $request->input('comment'),
            'start_date' => $request->input('start_date'),
            'status' => $request->input('status'),
            'city_id' => $request->input('city_id'),
            'region_id' => $request->input('region_id'),
            'coordinates' => $request->input('coordinates'),
            'customer_contract_id' => $request->input('contract_id', null),
        ]);

        $sync = [];
        foreach ($request->vehicles_categories as $category) {
            $date = Carbon::parse($category['date_from'])->format('Y-m-d');
            $time = $category['start_time'];
            $sync[$category['id']] = [
                'date_from' => Carbon::parse("{$date} {$time}"),
                'order_type' => $request->input('order_type'),
                'order_duration' => $request->input('duration'),
                'optional_attributes' => json_encode($category['optional_attributes']),
                'count' => $category['count']];
        }
        $lead->categories()->sync($sync);
        DB::commit();

        return response()->json($lead);
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


    function createHelper(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);
        $categories = Type::query()->with('tariffs')->get();
        $customers = Customer::whereUserId($request->input('user_id'))->get();
        $regions = Region::forDomain()->with('cities')->orderBy('name', 'desc')->get();
        $categories = Type::setLocaleNames($categories);
        $brands = Brand::all();

        return response()->json([
            'statuses' => Lead::getStatuses(),
            'categories' => $categories,
            'brands' => $brands,
            'customers' => $customers,
            'regions' => $regions,
            'documentsPack' => $this->co
        ]);
    }


    /**
     * Управление оператором в админке.
     * Создание диспетчерского заказа на технику исполнителей ТРАНСБАЗЫ
     * @param SelectContrator $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    function selectContractor(SelectContrator $request, $id)
    {

        /** @var Lead $lead */
        $lead = Lead::query()
            ->findOrFail($id);

        return LeadService::selectContractor($lead, $request);
    }

    /**
     * Управление Оператором из админки
     * Подтверждение предлоежния от исполнителя для диспетчерской заявки.
     * Диспетчер может указывать добавленные стоимости для любой позиции.
     * @param AcceptDispatcherOffer $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    function acceptDispatcherOffer(AcceptDispatcherOffer $request, $id)
    {
        $lead = Lead::query()->dispatcherLead()->findOrFail($id);

        return LeadService::acceptDispatcherOffer($lead, $request);

    }


    /**
     * Управление оператором из админки
     * Подтверждение предложения в заявке от исполнителя.
     * Создается оплачиваемый заказ. Данный метод только для клиентской заявки
     * @param AcceptOffer $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    function acceptOffer(AcceptOffer $request, $id)
    {
        $lead = Lead::query()->clientLead()->findOrFail($id);

        return LeadService::acceptOffer($lead, $request);
    }

    /**
     * Создание диспетчерского заказа на собственную технику диспетчера или его подрядчиков
     * @param CreateMyOrderFromLead $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    function createOrder(CreateMyOrderFromLead $request, $id)
    {

        $lead = Lead::query()
            ->findOrFail($id);

        return LeadService::createDispatcherOrder($lead, $request);
    }


    function close($id)
    {
        $lead = Lead::query()->forDomain()->whereStatus(Lead::STATUS_OPEN)->findOrFail($id);

        $lead->close();

        return response()->json();
    }
}

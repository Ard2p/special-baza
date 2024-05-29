<?php

namespace Modules\Orders\Http\Controllers;

use App\Directories\LeadRejectReason;
use App\Events\OrderUpdatedEvent;
use App\Http\Controllers\Avito\Events\OrderChangedEvent;
use App\Http\Controllers\Avito\Models\AvitoOrder;
use App\Machinery;
use App\Machines\Brand;
use App\Machines\FreeDay;
use App\Machines\Type;

use App\Service\Insurance\InsuranceService;
use App\Service\RequestBranch;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Jurosh\PDFMerge\PDFMerger;
use Modules\AdminOffice\Entities\Filter;
use Modules\AdminOffice\Entities\Marketing\Mailing\Template;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Entities\Company\Contact;
use Modules\CompanyOffice\Entities\Company\InsCertificate;
use Modules\CompanyOffice\Services\CompaniesService;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\CompanyOffice\Services\ContactsService;
use Modules\ContractorOffice\Entities\Driver;
use Modules\ContractorOffice\Services\Tariffs\TimeCalculation;
use Modules\Dispatcher\Entities\ContractorPay;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\DispatcherOrder;
use Modules\Dispatcher\Entities\Lead;
use Modules\Dispatcher\Http\Controllers\InvoiceController;
use Modules\Dispatcher\Services\LeadService;
use Modules\Integrations\Entities\Mails\MailConnector;
use Modules\Integrations\Rules\Coordinates;
use Modules\Orders\Entities\CustomerFeedback;
use Modules\Orders\Entities\MachineryStamp;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderComponent;
use Modules\Orders\Entities\OrderComponentHistory;
use Modules\Orders\Entities\OrderDocument;
use Modules\Orders\Entities\OrderMedia;
use Modules\Orders\Entities\Service\ServiceCenter;
use Modules\Orders\Entities\UdpRegistry;
use Modules\Orders\Services\OrderDocumentService;
use Modules\Orders\Services\OrderService;
use Modules\Orders\Transformers\CustomerOrder;
use Modules\Orders\Transformers\OrdersList;
use Modules\Profiles\Entities\UserNotification;
use Modules\RestApi\Emails\DBMail;

class OrdersController extends Controller
{

    /** @var CompanyBranch */
    private $companyBranch;

    public function __construct(Request $request)
    {
        if ($request->filled('phone')) {
            $request->merge([
                'phone' => trimPhone($request->input('phone'))
            ]);
        }
        $this->companyBranch = app()->make(RequestBranch::class)->companyBranch;
        $block = $this->companyBranch->getBlockName(CompanyRoles::BRANCH_ORDERS);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only([
            'index', 'getOrders', 'getDocuments', 'getFilters',
            'getContract',
            'getReturnAct',
            'getDisagreementAct',
            'getReports',
            'sendDocument',
            'getAcceptanceReportAct',
            'changeUdp',
            'generateApplication',
            'getSetApplication',
            'getVehiclesForPosition'
        ]);

        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_CREATE)->only([
            'changeManager',
            'store',
            'update',
            'updateOrderName',
            'cancelHold',
            'cancelOrder',
            'doneOrder',
            'customerFeedback',
            'uploadDoc',
            'changeAmount',
            'completePosition',
            'replaceVehicleInPosition',
            'addContact',
            'deleteContact',
            'changeAddress',
        ]);

        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_DELETE)->only(['destroy', 'closeLead']);


    }


    function filterOrders(Request $request, Builder $orders)
    {
        $request->validate([
            'pay' => 'nullable|in:1,0,true,false',
            'category_id' => 'nullable|exists:types,id',
            'name' => 'nullable|string|max:255'
        ]);
        $filter = new Filter($orders);
        if ($request->filled('pay') && filter_var($request->input('pay'), FILTER_VALIDATE_BOOLEAN)) {
            $orders->where('status', Order::STATUS_HOLD);
        }
        if (toBool($request->input('overdue'))) {
            $orders->whereHas('components', function ($q) {
                $q->where('status', Order::STATUS_ACCEPT)
                    ->where('date_to', '<', now());
            });
        }
        if (is_array($request->input('status'))) {
            $orders->whereIn('status', $request->input('status'));
        } else {
            $filter->getEqual([
                'status' => 'status'
            ]);
        }
        $filter->getLike([
            'id' => 'internal_number'
        ]);

        if ($request->filled('category_id')) {
            $orders->whereHas('vehicles', function ($q) use ($request) {
                $q->whereType($request->category_id);
            });
        }
        if ($request->filled('name')) {
            $orders->where(function ($q) use ($request) {
                $q->where('id', (int)$request->input('name'))
                    ->orWhere(function ($q) use ($request) {
                        $q->whereHas('vehicles', function ($q) use ($request) {
                            $q->where('name', 'like', "%{$request->name}%");
                        });
                    });
            });
        }

        if ($request->filled('customer_id')) {
            $orders->where('customer_type', Customer::class)
                ->where('customer_id', $request->input('customer_id'));
        }

        if ($request->filled('contact_person')) {
            $orders->filterContactPerson($request->input('contact_person'));
        }


    }

    function getOrders(Request $request, $id = null)
    {

        $data = Order::query()->withCount('tasks')
            ->with([
                'tasks' => function ($query) {
                    $query->where('employee_id', \Auth::id())
                        ->orWhere('responsible_id', \Auth::id());
                },
                'components.services.customService',
                'components.worker',
                'machinerySets',
                'creator',
                'leads.customer', 'categories', 'customer', 'contacts', 'dispatcher_contractors', 'invoices'
            ])
            ->forBranch()
            ->forDomain()
            ->orderBy('created_at', 'desc');


        if ($id) {
            $data->with([
                'vehicles' => function ($q) use ($id) {
                    $q->with([
                        'order_timestamps' => function ($q) use ($id) {
                            $q->where('order_id', $id);
                        }
                    ]);
                },
                'components' => function ($q) {
                    $q->withSum('contractorPays as contractor_pays_sum', 'sum');
                },
                'contacts.phones',
                'avito_order',
                'contacts.emails',
                'components.worker',
                'components.histories',
                'components.idle_periods',
                'components.driver',
                'components.media',
                'components.services',
                'components.parts',
                'components.actual.reports',
                'components.rent_parts',
                'warehouse_part_sets.parts'
            ]);
            $data = $data->findOrFail($id);
            return $data->company_branch_id === $this->companyBranch->id
                ? CustomerOrder::make($data)
                : \Modules\ContractorOffice\Transformers\Order::make($data);
        }

        if ($request->input('type') === 'customer') {
            $data->customerOrders();
        }
        if ($request->input('type') === 'dispatcher') {
            $data->whereType('dispatcher');
            $data->where('contractor_id', '!=', $this->companyBranch->id);
        }

        if ($request->input('type') === 'client') {
            $data->whereType('client');
            $data->where('contractor_id', '!=', $this->companyBranch->id);
        }
        if ($request->input('type') === 'my_contractors') {

            $data->whereHas('dispatcher_contractors', function ($q) {
                $q->forBranch();
            });
        }

        if ($request->filled('withUser')) {
            $data->with('user');
        }
        $this->filterOrders($request, $data);

        return OrdersList::collection($data->paginate($request->per_page ?: 3));
    }

    function changeManager(Request $request, $id)
    {
        $order = Order::query()
            ->forBranch()
            ->whereStatus(Order::STATUS_ACCEPT)
            ->findOrFail($id);
        $this->companyBranch->employees()->findOrFail($request->input('manager_id'));

        $order->update([
            'creator_id' => $request->input('manager_id')
        ]);

        return response()->json();
    }

    function cancelHold($id)
    {
        $order = Order::with('payment', 'user')
            ->forBranch()
            ->whereStatus(Order::STATUS_HOLD)
            ->findOrFail($id);

        DB::beginTransaction();
        $order->payment->reverse();
        DB::commit();

        $order->refresh();

        return $order;
    }

    function cancelOrder($id)
    {
        $order = Order::with('payment', 'user')
            ->forBranch()
            ->whereStatus(Order::STATUS_ACCEPT)
            ->findOrFail($id);

        if ($order->can_cancel) {
            DB::beginTransaction();

            $order->cancel();

            DB::commit();

            return $order;
        }

        return response()->json(['message' => [trans('transbaza_order.cant_cancel')]]);

    }


    function doneOrder($id)
    {
        $order = Order::with('user', 'payment')->forBranch()
            ->whereStatus(Order::STATUS_ACCEPT)
            ->findOrFail($id);

        DB::beginTransaction();

        $order->done();

        OrderUpdatedEvent::dispatch($order);
        DB::commit();

        return $order;
    }

    function customerFeedback(Request $request, $id)
    {
        $order = Order::with('user')
            ->whereDoesntHave('customer_feedback')
            ->forBranch()
            ->findOrFail($id);

        $errors = Validator::make($request->all(), [
            'content' => 'required|string|min:5|max:255'
        ])->errors()->getMessages();

        if ($errors) {
            return response()->json($errors, 419);
        }

        DB::beginTransaction();

        CustomerFeedback::create([
            'content' => $request->input('content'),
            'order_id' => $order->id
        ]);

        DB::commit();

        $order->load('customer_feedback');

        return $order;
    }

    function getFilters()
    {
        $categories = Type::whereHas('machines', function ($q) {
            $q->whereHas('orders', function ($q) {
                $q->forBranch();
            });
        })->get();

        $brands = Brand::whereHas('machines', function ($q) {
            $q->whereHas('orders', function ($q) {
                $q->forBranch();
            });
        })->get();

        $statuses = Order::statuses();
        $filter_statuses = [];

        foreach ($statuses as $key => $v) {
            $filter_statuses[] = [
                'name' => $v,
                'value' => $key,
            ];
        }
        // $customers = Customer::query()->forBranch()->whereHas('orders')->get();
        return response()->json([
            'categories' => Type::setLocaleNames($categories),
            'brands' => $brands,
            'statuses' => $filter_statuses,
            'rejectTypes' => LeadRejectReason::all(),
            //  'customers' => $customers
        ]);
    }

    function uploadDoc(Request $request, $id)
    {
        $order = Order::forBranch()->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'doc' => 'required|string|max:255',
        ]);
        $tmp_dir = config('app.upload_tmp_dir');

        if (!Str::contains($request->input('doc'), $tmp_dir)) {
            return response()->json([], 400);
        }
        $tmp_file_path = "{$request->input('doc')}";

        $exists = Storage::disk()->exists($request->input('doc'));

        if (!$exists) {
            return response()->json(['doc' => ['Файл не найден. Попробуйте еще раз.']], 400);
        }

        return $order->addDocument($request->input('name'), $tmp_file_path);
    }

    function getDocuments($id)
    {
        $order = Order::contractorOrCustomer()->findOrFail($id);

        return $order->documents()->with('user')->get();
    }

    function changeAmount(Request $request, $id)
    {
        $order = Order::query()
            ->forBranch()
            ->whereDoesntHave('invoices')
            ->findOrFail($id);
        $min = ($order->contractor_sum / 100);
        $request->validate([
            'sum' => "required|numeric|min:{$min}|max:9999999"
        ]);

        $order->update([
            'amount' => numberToPenny($request->input('sum'))
        ]);

        return response()->json();
    }

    function getContract(Request $request, $id)
    {
        /** @var Order $order */
        $order = Order::query()->contractorOrCustomer()->findOrFail($id);

        $url = $order->getContractUrl($request->input('subContractorId'));

        return $url ? response()->json([
            'url' => $url
        ])
            : response()->json([], 400);
    }

    function getReturnAct(Request $request, $id)
    {
        $request->validate([
            'position_id' => 'required|exists:order_workers,id',
            'date' => 'nullable|date',
            'time' => 'nullable|date_format:H:i',
        ]);

        $order = Order::query()->contractorOrCustomer()->findOrFail($id);
        $service = new OrderDocumentService($request->all());
        return response()->json([
            'url' => $service->formReturnAct($order)
        ]);
    }

    function getDisagreementAct(Request $request, $id)
    {
        $request->validate([
            'position_id' => 'required|exists:order_workers,id',
            'date' => 'nullable|date',
            'time' => 'nullable|date_format:H:i',
        ]);

        $order = Order::query()->contractorOrCustomer()->findOrFail($id);

        $service = new OrderDocumentService($request->all());

        return response()->json([
            'url' => $service->generateDisagreementAct($order)
        ]);

    }

    function postCustomDocument(Request $request, $id)
    {
        $data = $request->all();
        $request->validate([
            'positions' => 'required|array',
            'type' => 'required|in:default_single_act_url,default_single_application_url,default_return_single_act_url,default_single_act_services_url,default_upd_url',
            'date' => 'nullable|date',
            'time' => 'nullable|date_format:H:i',
            'doc' => is_array($data['doc'])?'required|array':'required|string|max:255',
        ]);
        $order = Order::query()->contractorOrCustomer()->findOrFail($id);
        $type = $request->input('type');
        if ($type === 'default_upd_url') {
            UdpRegistry::getNumber($order, 'upd');
        }
        switch ($type) {
            case 'default_single_act_url':
                $status = 'arrival';
                break;
            case 'default_single_application_url':
                $status = 'on_the_way';
                break;
            case 'default_return_single_act_url':
                $status = 'done';
                break;
            default:
                $status = null;
        }
        $date = ($request->input('date')
            ? Carbon::parse($request->input('date'))->format('Y-m-d')
            : now($this->companyBranch->timezone)->format('Y-m-d'));

        $time = ($request->input('date')
            ? Carbon::parse($request->input('date'))->format('H:i:s')
            : now($this->companyBranch->timezone)->format('H:i:s'));

        foreach ($request->input('positions') as $position) {
            $position = $order->workers()->findOrFail($position);
            if ($status) {
                MachineryStamp::createTimestamp($position->worker->id, $position->order_id, $status, "{$date} {$time}",
                    $order->coordinates);
            }
            if ($type === 'default_return_single_act_url') {
                MachineryStamp::createTimestamp($position->worker->id, $position->order_id, 'done', "{$date} {$time}",
                    $order->coordinates);
                //    $s->donePosition($order->components()->find($position->id));
            }
        }
        $name = match ($type) {
            'default_upd_url' => 'УПД - передаточный документ (акт)',
            'default_single_act_url' => trans('transbaza_order.common_act'),
            'default_return_single_act_url' => 'Акт возврата',
            'default_single_act_services_url' => 'Акт оказания услуг',
            default => trans('transbaza_order.common_app')
        };

        $name .= str_replace('"',"'"," {$order->internal_number} {$order->customer->company_name} (signed)");

        $docs = $order->documents()->where('name','LIKE', "$name%")->get();

        $docs->each(function ($item) {
            $item->delete();
        });
        $docType = match ($type) {
            'default_upd_url' => 'upd',
            default => null
        };

        // $documentPdf = $this->generatePdf($file, $request->input('doc'), $name, $order);

        //  Storage::disk('public_disk')->delete($request->input('doc'));

        if(is_array($request->input('doc'))){
            if($request->input('merge')) {
                try {

                    $dt = Carbon::now()->format('d.m.Y H-i');
                    $pdfName = $name;

                    $pdfPath = config('app.upload_tmp_dir') . "/$pdfName.pdf";

                    $merger = new PDFMerger();

                    $paths = [];
                    foreach ($request->input('doc') as $document) {
                        $path = $document;
                        $paths[] = $path;
                        Storage::disk('public_disk')
                            ->put(
                                $path,
                                Storage::disk()->get($document)
                            );

                        $merger->addPdf(public_path($document));
                    }

                    $merger->merge('file', public_path($pdfPath));

                    Storage::disk()->put($pdfPath, Storage::disk('public_disk')->get($pdfPath));

                    $document = $order->addDocument($pdfName, $pdfPath, type: $docType);

                    Storage::disk('public_disk')->delete($paths);
                }catch(Exception $e){
                    foreach ($request->input('doc') as $i => $document){
                        $postFix = $i === 0 ? '' : " ($i)";
                        /** @var  Order $order */
                        $order->addDocument($name . $postFix, $document, type: $docType);
                    }

                    return response()->json(['message' => 'Документы добавлены без объединения.']);
                }
            }else{
                foreach ($request->input('doc') as $i => $document){
                    $postFix = $i === 0 ? '' : " ($i)";
                    /** @var  Order $order */
                    $order->addDocument($name . $postFix, $document, type: $docType);
                }
            }

            return response()->json();
        } else{
            $extension = getFileExtensionFromString($request->input('doc'));

            $file = "{$order->id}_single_act_signed.{$extension}";
            /** @var  Order $order */
            $document = $order->addDocument($name, $request->input('doc'), type: $docType);

            return response()->json();//Storage::disk()->url($documentPdf['url']);
        }
    }

    function getSingleAct(Request $request, $id)
    {
        $request->validate([
            'positions' => 'required|array',
            'type' =>
                ['required', Rule::in([
                    'default_order_claims_url',
                    'default_order_claims_url_html',
                    'default_order_claims_url_with_stamp',
                    'default_order_claims_url_with_stamp_html',
                    'default_single_act_url',
                    'default_single_application_url',
                    'default_return_single_act_url',
                    'default_single_act_services_url',
                    'default_upd_url',
                    'default_single_contract_url',
                ])],
            'date' => 'nullable|date',
            'time' => 'nullable|date_format:H:i',
            //'with_stamp' => 'nullable|boolean',
        ]);
        if ($request->input('doc')) {
            return $this->postCustomDocument($request, $id);
        }
        $order = Order::query()->contractorOrCustomer()->findOrFail($id);
        $service = new OrderDocumentService($request->all());
        if ($request->boolean('preview')) {
            return response()->json([
                'preview' => $service->formSingleAct($order, $request->input('type'), $request->boolean('with_stamp'), true)
            ]);
        }
        return response()->json([
            'url' => $service->formSingleAct($order, $request->input('type'), $request->boolean('with_stamp'))
        ]);
    }

    function getInsCertificate(Request $request, Order $id)
    {
        $request->validate([
            'position_id' => 'required|exists:order_workers,id',
            'cert_id' => 'required|exists:ins_certificates,id',
        ]);
        $insuranceService = new InsuranceService();


        $component = OrderComponent::query()->findOrFail($request->get('position_id'));
        $insCertificate = $component->ins_certificates()
            ->where('id', $request->get('cert_id'))
            ->first();

        $url = $insuranceService->generateDocument($insCertificate, $component);

        return response()->json([
            'url' => Storage::disk()->url($url)
        ]);
    }

    function getCertificates(Request $request, Order $id)
    {
        $request->validate([
            'position_id' => 'required|exists:order_workers,id',
        ]);

        $component = OrderComponent::query()->findOrFail($request->get('position_id'));
        return response()->json($component->ins_certificates->where('status', 1));
    }


    function getAcceptanceReportAct(Request $request, $id)
    {
        $request->validate([
            'position_id' => 'required|exists:order_workers,id',
            'date' => 'nullable|date',
            'time' => 'nullable|date_format:H:i',
        ]);
        $order = Order::query()->contractorOrCustomer()->findOrFail($id);
        $service = new OrderDocumentService($request->all());
        return response()->json([
            'url' => $service->formAcceptance($order)
        ]);
    }

    function generateApplication(Request $request, $id)
    {
        $request->validate([
            'position_id' => 'required|exists:order_workers,id'
        ]);
        $order = Order::query()->contractorOrCustomer()->findOrFail($id);
        $service = new OrderDocumentService($request->all());
        return response()->json([
            'url' => $service->generateApplication($order, $request->boolean('with_stamp'))
        ]);
    }


    function completePosition(Request $request, $order_id, $position_id)
    {
        /** @var OrderComponent $position */
        $position = OrderComponent::query()->whereHas('order', function ($q) use ($order_id) {
            $q->forBranch()->where('orders.id', $order_id);
        })->findOrFail($position_id);

        $service = new OrderService();
        DB::beginTransaction();

        $service->donePosition($position, $request->input('base_id'), toBool($request->input('actual')));

        if ($position->driver) {
            $document = new OrderDocumentService();
            $document->formWorkerResult($position);
        }

        DB::commit();
        OrderUpdatedEvent::dispatch(Order::query()->find($order_id));

        return response()->json();
    }

    function returnToWork($order_id, $position_id)
    {
        $position = OrderComponent::query()->whereHas('order', function ($q) use ($order_id) {
            $q->forBranch()->where('orders.id', $order_id);
        })->findOrFail($position_id);

        $service = new OrderService();
        DB::beginTransaction();

        $service->returnToWork($position);

        DB::commit();
        OrderUpdatedEvent::dispatch(Order::query()->find($order_id));
        return response()->json();
    }

    function changeUdp(Request $request, $order_id, $position_id)
    {
        $position = OrderComponent::query()->whereHas('order', function ($q) use ($order_id) {
            $q->forBranch()->where('orders.id', $order_id);
        })->findOrFail($position_id);

        $request->validate([
            'udp_number' => 'required|string|max:255',
            'udp_date' => 'required|date',
        ]);

        $position->update([
            'udp_number' => $request->input('udp_number'),
            'udp_date' => $request->input('udp_date'),
        ]);
    }

    /**
     * Получить доступную технику для заказа
     * @param $order_id
     * @param $position_id
     * @return mixed
     */
    function getVehiclesForPosition($order_id, $position_id)
    {
        $position = OrderComponent::query()->whereHas('order', function ($q) use ($order_id) {
            $q->forBranch()->where('orders.id', $order_id);
        })->findOrFail($position_id);

        $coords = explode(',', $position->order->coordinates);

        $machines =
            LeadService::getMachineriesForPeriod(Machinery::query()->forBranch()
                ->whereNull('sub_owner_id')
                ->where('is_rented', true)
                ->categoryBrandModel($position->category_id)
                ->whereInCircle($coords[0], $coords[1], true)->where('machineries.id', '!=', $position->worker_id),
                $position->date_from,
                $position->order_type,
                $position->order_duration);

        return $machines;
    }

    /**
     * Замена техники на другую единицу в заказе
     * @param Request $request
     * @param $order_id
     * @param $position_id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    function replaceVehicleInPosition(Request $request, $order_id, $position_id)
    {
        $position = OrderComponent::query()->whereHas('order', function ($q) use ($order_id) {
            $q->forBranch()->where('orders.id', $order_id);
        })
            ->where('complete', false)
            ->where('worker_type', Machinery::class)
            ->findOrFail($position_id);
        $order = Order::query()->findOrFail($order_id);

        $coords = explode(',', $position->order->coordinates);

        $oldVehicle = $position->worker;

        DB::beginTransaction();

        if (toBool($request->input('service.service'))) {
            $dateFrom = $request->input('service.date_from') . ($request->input('service.time_from') ? ' ' . $request->input('service.time_from') : '');
            $dateTo = $request->input('service.date_to') . ($request->input('service.time_to') ? ' ' . $request->input('service.time_to') : '');
            $center = new ServiceCenter([
                'name' => "Сделка #{$order->internal_number}",
                'type' => 'inner',
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'phone' => $order->contact_phone,
                'contact_person' => $order->contact_person,
                'description' => $request->input('service.description'),
                'machinery_id' => $oldVehicle->id,
                'company_branch_id' => $this->companyBranch->id
            ]);
            FreeDay::create([
                'startDate' => $dateFrom,
                'endDate' => $dateTo,
                'type' => 'busy',
                'machine_id' => $oldVehicle->id,
                'creator_id' => Auth::check() ? Auth::id() : null,
            ]);
            $center->customer()->associate($this->companyBranch);
            $center->save();
        }
        $vehicle = Machinery::query()->forBranch()
            ->categoryBrandModel($position->category_id)
            ->whereInCircle($coords[0], $coords[1], true);
        if ($position->order_type === TimeCalculation::TIME_TYPE_HOUR) {
            $vehicle->checkAvailable($position->date_from, $position->date_to);
        }
        /** @var Machinery $vehicle */
        $vehicle = $vehicle->find($request->input('id'));
        // ->checkAvailable($position->date_from, $position->date_to)->find($request->input('id'));
        if (!$vehicle) {
            $error = ValidationException::withMessages([
                'errors' => ['В указанном периоде замены техника занята']
            ]);

            throw $error;
        }

        FreeDay::query()
            ->where('order_component_id', $position->id)
            ->where('machine_id', $position->worker->id)
            ->where('order_id', $order_id)->update([
                'machine_id' => $vehicle->id
            ]);

        $position->worker()->associate($vehicle);
        $position->save();

        $position->histories()->save(new OrderComponentHistory([
            'type' => 'replace',
            'description' => "Замена техники с {$oldVehicle->name} на {$vehicle->name}",

        ]));

        MachineryStamp::query()->where('machinery_id', $oldVehicle->id)
            ->where('order_id', $order_id)
            ->update([
                'machinery_id' => $vehicle->id,
            ]);


        /* $dates = $vehicle->getDatesForOrder($position->date_from, $position->order_duration, $position->order_type);

         foreach ($dates as $date) {
             FreeDay::create([
                 'startDate' => $date,
                 'endDate' => ($position->order_type === TimeCalculation::TIME_TYPE_HOUR ? $position->date_to : Carbon::parse($date)->endOfDay()),
                 'type' => 'order',
                 'order_id' => $order_id,
                 'machine_id' => $vehicle->id,
                 'order_component_id' => $position->id
             ]);
         }
         if (TimeCalculation::TIME_TYPE_SHIFT === $position->order_type) {
             $position->update([
                 'date_to' => $dates[count($dates) - 1]
             ]);
         }*/


        $companyService = new CompaniesService($vehicle->company_branch->company);

        $companyService->addUsersNotification(
            trans('user_notifications.order_replace_vehicle', ['id' => $position->order->internal_number]),
            Auth::user() ?: null,
            UserNotification::TYPE_INFO,
            $position->order->generateCompanyLink(),
            $vehicle->company_branch);

        DB::commit();

        return response()->json();
    }

    function addContact(Request $request, $order_id)
    {
        $request->validate(
            ContactsService::getValidationRules(true)
        );
        $order = Order::query()
            ->forBranch()
            ->findOrFail($order_id);

        $service = new ContactsService($order->company_branch);

        return $service->createContact($request->all(), $order);
    }

    function deleteContact($order_id, $contact_id)
    {
        $order = Order::query()
            ->forBranch()
            ->findOrFail($order_id);

        $order->contacts()->detach($contact_id);
        return response()->json();
    }

    function changeAddress(Request $request, $orderId)
    {
        $request->validate([
            'address' => 'required|string|max:255',
            'coordinates' => [
                'required',
                new Coordinates()
            ]
        ]);
        $order = Order::query()
            ->forBranch()
            ->findOrFail($orderId);

        $order->update([
            'address' => $request->input('address'),
            'coordinates' => $request->input('coordinates'),
        ]);

        return response()->json();
    }

    function getReports(Request $request, $order_id, $position_id)
    {
        $position = OrderComponent::query()->whereHas('order', function ($q) use ($order_id) {
            $q->forBranch()->where('orders.id', $order_id);
        })->findOrFail($position_id);

        return $position->reports;
    }

    function updateReport(Request $request, $order_id, $position_id)
    {

        $request->validate([
            'report_timestamps.*.time_from' => 'nullable|date',
            'report_timestamps.*.duration' => 'required|numeric|min:0',
            'report_timestamps.*.idle_duration' => 'required|numeric|min:0',
            'report_timestamps.*.cost_per_unit' => 'nullable|numeric|min:0',
        ]);
        /** @var OrderComponent $position */
        $position = OrderComponent::query()->whereHas('order', function ($q) use ($order_id) {
            $q->forBranch()->where('orders.id', $order_id);
        })->findOrFail($position_id);

        DB::beginTransaction();

        foreach ($request->input('report_timestamps') as $item) {
            $stamp = $position->reportsTimestamps()->findOrFail($item['id']);
            $time = Carbon::parse($item['time_from']);
            $duration = $item['duration'] + $item['idle_duration'];
            $stamp->update([
                'time_from' => $item['time_from'] ? $time->format('H:i') : null,
                'time_to' => $duration > 0 ? $time->addHours($duration) : null,
                'duration' => $item['duration'],
                'idle_duration' => $item['idle_duration'],
                'cost_per_unit' => numberToPenny($item['cost_per_unit'] ?? 0),

            ]);
        }
        DB::commit();

        return response()->json();

    }

    function sendDocument(Request $request, $id)
    {
        $request->validate([
            'email' => 'required|email',
            'subject' => 'required|string|max:255',
            'body' => 'required|string|max:8000',
            'bind_type' => 'nullable|string|max:255',
            'bind_id' => 'nullable',
        ]);
        $docs = OrderDocument::query()->whereIn('id', $request->input('ids'))->get()->map(function ($doc) {
            if ($doc->type === 'contract' && $doc->order instanceof Lead) {
                $doc->order->update([
                    'contract_sent' => now(),
                    'tmp_status' => 'contract',
                ]);
            }
            return [
                'name' => $doc->name,
                'path' => $doc->url
            ];
        });

        $template = Template::getTemplate(Template::TYPE_SEND_ORDER_DOCUMENT, $this->companyBranch->domain->id);

        /** @var MailConnector $connector */
        $connector = Auth::user()->mailConnector ?: $this->companyBranch->mailConnector;
        //  logger($connector);
        if ($connector) {

            /*$template->parse([
                'company' => $this->companyBranch->name,
            ]);*/
            $docs = $docs->map(function ($doc) {

                $url = Storage::disk()->url($doc['path']);
                $parts = explode('/', $url);
                $encoded = rawurlencode(array_pop($parts));
                $parts[] = $encoded;
                $doc['path'] = $url;///implode('/', $parts);
                return $doc;
            });
            $bindType = match ($request->input('bind_type')) {
                'transbaza' => Order::class,
                'order' => Order::class,
                'lead' => Lead::class,
                default => null
            };
            $result = $connector->sendRawEmail([$request->input('email')], $request->input('subject'),
                $request->input('body'), $docs->toArray(), null, $bindType, $request->input('bind_id'));

            if ($connector->lastStatusCode === 200) {
                return response()->json();
            } else {
                logger()->error('Fail to send mail data',[$result]);
                return response()->json($result, 400);
            }

        }
        Mail::to($request->input('email'))->queue(new DBMail($template, [
            'company' => $this->companyBranch->name,
        ], $docs->toArray()));


        return response()->json();
    }

    function sendNewContacts(Request $request, $id)
    {
        $order = Order::query()
            ->forBranch()
            ->findOrFail($id);

        if ($order->isAvitoOrder()){
            OrderChangedEvent::dispatch($order, AvitoOrder::STATUS_PREPAID);
        }
    }

    function updateOrderName(Request $request, $id)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'comment' => 'nullable|string',
            'contract_number' => 'nullable|string|max:255',
            'object_name' => 'nullable|string|max:255',
            'status_tmp' => 'required|string|max:255',
            'work_type' => 'nullable|string|max:500',
        ]);

        /** @var Order $order */
        $order = Order::query()
            ->forBranch()
            ->findOrFail($id);

        $oldStatus = $order->tmp_status;
        $driver_id = null;
        DB::beginTransaction();
        if($request->filled('driver')){
            $driverData = $request->input('driver');
            $driver = Driver::query()->updateOrCreate([
                'id' => $driverData['id']
            ],$driverData);

            $driver_id = $driver->id;
        }
        $order->update([
            'name' => $request->input('name'),
            'comment' => $request->input('comment'),
            'contract_number' => $request->input('contract_number'),
            'tmp_status' => $request->input('status_tmp'),
            'work_type' => $request->input('work_type'),
            'driver_id' => $driver_id,
        ]);

        if ($order->isAvitoOrder() && $oldStatus !== $request->input('status_tmp')) {
            $status = match ($request->input('status_tmp')) {
                Order::STATUS_AGREED => AvitoOrder::STATUS_PROPOSED,
                Order::STATUS_FINISH => AvitoOrder::STATUS_FINISHED,
                Lead::STATUS_INVOICE => AvitoOrder::STATUS_PREPAID,
                Order::STATUS_REJECT => AvitoOrder::STATUS_CANCELED,
                default => null,
            };
            if ($status === AvitoOrder::STATUS_PROPOSED && $order->invoices()->where('is_paid', false)->count() > 1) {
                throw ValidationException::withMessages([
                    'message' => 'В сделке более 1 неоплаченного счёта. Удалите все неоплаченные счета и нажмите “Согласовано”. В случае возникновения вопросов свяжитесь с технической поддержкой'
                ]);
            }
            if ($request->input('status_tmp') === Order::STATUS_FINISH) {

                (new OrderDocumentService([]))->formSingleAct($order, 'default_upd_url', true);
            }
        }

        $this->processComponents($request, $order);

        $order->lead->update([
            'object_name' => $request->input('object_name'),
        ]);

        DB::commit();

        return response()->json();
    }


    function getSetApplication(Request $request, $id)
    {
        $order = Order::contractorOrders()->findOrFail($id);

        $service = new OrderDocumentService($request->all());
        return response()->json([
            'url' => $service->getSetApplication($order)
        ]);

    }

    function getSetAct(Request $request, $id)
    {
        $order = Order::contractorOrders()->findOrFail($id);

        $service = new OrderDocumentService($request->all());
        return response()->json([
            'url' => $service->getSetAct($order)
        ]);

    }

    function getSetReturnAct(Request $request, $id)
    {
        $order = Order::contractorOrders()->findOrFail($id);

        $service = new OrderDocumentService($request->all());
        return response()->json([
            'url' => $service->getSetReturnAct($order)
        ]);
    }

    function setStatus(Request $request, $id, $positionId)
    {
        $request->validate([
            'status' => ['required', Rule::in([
                'on_the_way',
                'arrival',
                'done',
            ])],
        ]);

        $component = OrderComponent::query()->whereHas('order', fn(Builder $builder) => $builder->forBranch())->findOrFail($positionId);

        switch ($request->input('status')) {
            case 'on_the_way':
                MachineryStamp::createTimestamp($component->worker_id, $component->order_id, 'arrival', now())?->delete();
                MachineryStamp::createTimestamp($component->worker_id, $component->order_id, 'done', now())?->delete();
                MachineryStamp::createTimestamp($component->worker_id, $component->order_id, $request->input('status'), now());
                break;
            case 'arrival':
                MachineryStamp::createTimestamp($component->worker_id, $component->order_id, 'done', now())?->delete();
                MachineryStamp::createTimestamp($component->worker_id, $component->order_id, $request->input('status'), now());
                break;
            case 'done':
                MachineryStamp::createTimestamp($component->worker_id, $component->order_id, $request->input('status'), now());
                break;
        }
    }

    /**
     * @param Request $request
     * @param Order $order
     * @return void
     */
    private function processComponents(Request $request, Order $order): void
    {
        $componentStatus = null;
        $stampStatus = null;
        if ($request->has('status_tmp')) {
            $componentStatus = match ($request->input('status_tmp')) {
                Order::STATUS_DONE, Order::STATUS_FINISH => OrderComponent::STATUS_DONE,
                Order::STATUS_REJECT => OrderComponent::STATUS_REJECT,
                default => null,
            };
            $stampStatus = match ($request->input('status_tmp')) {
                Order::STATUS_PREPARE => MachineryStamp::STATUS_PREPARE,
                Order::STATUS_START => MachineryStamp::STATUS_START,
                Order::STATUS_FINISH, Order::STATUS_DONE => MachineryStamp::STATUS_FINISH,
                default => null,
            };
        }

        if ($stampStatus) {
            foreach ($order->components as $component) {
                $this->setStatus(new Request([
                    'status' => $stampStatus,
                ]), $order->id, $component->id);
            }
        }
        if ($componentStatus) {

            foreach ($order->components as $component) {
                switch ($request->input('status_tmp')) {
                    case Order::STATUS_DONE:
                    case Order::STATUS_FINISH:
                        $requestComplete = new Request([
                            "base_id" => $component->machinery_base_id,
                            "actual" => false
                        ]);
                        $requestComplete->headers->add([
                            'company' => $order->company_branch->company->alias,
                            'branch' => $order->company_branch->id,
                        ]);
                        $this->completePosition($requestComplete, $order->id, $component->id);
                        break;
                    case Order::STATUS_REJECT:
                        $rejectRequest = new Request([
                            'position_ids'=>[$component->id],
                            'remove'=>false,
                            'type'=>"other",
                        ]);
                        $rejectRequest->headers->add([
                            'company' => $order->company_branch->company->alias,
                            'branch' => $order->company_branch->id,
                        ]);

                        $c = new \Modules\ContractorOffice\Http\Controllers\OrdersController($rejectRequest, new RequestBranch(fn() => $rejectRequest));
                        $c->rejectApplication($rejectRequest, $order->id);
                        break;
                }
            }
        }
    }


}

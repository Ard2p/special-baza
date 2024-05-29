<?php

namespace Modules\Orders\Http\Controllers;

use App\Finance\TinkoffMerchantAPI;
use App\Finance\TinkoffPayment;
use App\Helpers\RequestHelper;
use App\Machinery;
use App\Service\RequestBranch;
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
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Services\CompaniesService;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\Dispatcher\Entities\Lead;
use Modules\Integrations\Rules\Coordinates;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderManagement;
use Modules\Orders\Entities\Payment;
use Modules\Orders\Entities\Payments\Invoice;
use Modules\Orders\Http\Requests\OrderRequest;
use Modules\Orders\Http\Requests\PaymentRequest;
use Modules\Orders\Jobs\CancelPayment;
use Modules\Orders\Jobs\SendOrderInvoice;
use Modules\Orders\Services\OrderService;
use PDF;

class PaymentsController extends Controller
{
    private $currentBranch;

    public function __construct(RequestBranch $currentBranch)
    {
        $this->currentBranch = $currentBranch->companyBranch;

        if ($this->currentBranch) {
            $block = $this->currentBranch->getBlockName(CompanyRoles::BRANCH_ORDERS);
            $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only(
                [
                    'generateInvite',
                    'inviteUser',
                    'inviteInfo',
                    'updateBranch',
                ]);
        }

    }

    function payment(PaymentRequest $request)
    {

        $service = new OrderService();

        DB::beginTransaction();

        $user = Auth::guard('api')->check()
            ? Auth::guard('api')->user()
            : User::register($request->email, $request->phone);


        if (Auth::guard('api')->check()) {
            $company_branch = $this->currentBranch ?: CompanyBranch::query()->userHasAccess($user->id)->findOrFail($request->input('company_branch_id'));
        } else {
            $service = CompaniesService::createCompany($user, RequestHelper::requestDomain()->id);

            $company_branch = $service->createBranch();
        }

        $result = $service->generatePayment($request, $company_branch, $user);
        DB::commit();

        return $result;
    }


    function generatePayment(OrderRequest $request)
    {
        $request->validated();

        $collection = collect($request->order_vehicles);
        $coords = explode(',', $request->coordinates);

        $region = Region::whereName($request->region)->first();
        $city = false;
        if ($region) {
            $city = $region->cities()->whereName($request->city)->first();
        }

        $user = Auth::guard('api')->check()
            ? Auth::guard('api')->user()
            : User::register($request->email, $request->phone);

        $duration = $request->input('duration');


        $old_payments = $user->payments()->whereStatus(Payment::STATUS_WAIT)->hasHold()->get();

        if ($old_payments->isNotEmpty()) {
            $errors['hold'] = trans('transbaza_order.cant_create');
        }
        $df = Carbon::parse($request->date_from)->format('Y-m-d');
        $tf = $request->input('start_time');
        $date_from = Carbon::createFromFormat('Y-m-d H:i', "{$df} {$tf}");


        $date_to = (clone $date_from);

        if ($request->input('type') === 'shift') {
            $date_to->addDays($duration - 1)->endOfDay();
        } else {
            $date_to->addHours($duration);
        }

        $vehicles = Machinery::whereIn('id', $collection->pluck('id'))
            ->checkAvailable($date_from, $date_to, $request->input('type'), $duration)
            ->whereInCircle($coords[0], $coords[1])
            ->sharedLock()
            ->get();

        //  return response()->json($vehicles, 400);

        if ($vehicles->count() !== $collection->count()) {

            return response()->json(['address' => [trans('transbaza_order.vehicle_busy')]], 400);
        }

        $lock_ids = $collection->pluck('id')->toArray();
        $lock = checkLock($lock_ids);
        if (!$lock) {
            return response()->json(['errors' => [trans('transbaza_order.vehicle_wait_busy')]], 419);
        }
        DB::beginTransaction();

        try {

            $required_categories = [];
            foreach ($vehicles as $vehicle) {
                $required_categories[] = [
                    'order_type' => $request->input('type'),
                    'order_duration' => $duration,
                    'type_id' => $vehicle->type,
                ];
            }
            $orderService = new OrderManagement($required_categories, $request->coordinates);

            $orderService->setContractorUser($vehicles->first()->user);

            $pay_items = $orderService->prepareVehicles($vehicles);

            $orderService
                ->setCustomerUser($user)
                ->setDateFrom($date_from)
                ->setDetails([
                    'contact_person' => $request->contact_person,
                    'address' => $request->address,
                    'region_id' => $region ? $region->id : null,
                    'city_id' => $city ? $city->id : null,
                    'coordinates' => $request->coordinates,
                    'start_time' => $date_from->format('H:i'),
                ])
                ->createProposalForPayment();

            $for_promo = ($request->filled('promo_code') && config('in_mode'));


            $payment = Payment::create([
                'system' => ($for_promo ? Payment::TYPE_PROMO : $request->input('pay_type')),
                'status' => Payment::STATUS_WAIT,
                'currency' => RequestHelper::requestDomain()->currency->code,
                'amount' => $orderService->created_proposal->amount,
                'user_id' => $orderService->customerUser->id,
                'order_id' => $orderService->created_proposal->id
            ]);

            $instance = $payment->generatePayment($pay_items, $request->pay_type, $request->input('invoice'));

        } catch (\Exception $exception) {
            DB::rollBack();
            disableLock($lock_ids);

            Log::info("error payment {$exception->getMessage()} {$exception->getTraceAsString()}");
            return response()->json(['error payment'], 500);
        }

        if ($instance === 'promo' || $instance === 'invoice') {
            DB::commit();
            disableLock($lock_ids);

            if ($instance === 'invoice') {
                dispatch(new SendOrderInvoice($orderService->created_proposal));
            }

            return response()->json(['order_id' => $orderService->created_proposal->id]);
        }
        if ($instance instanceof TinkoffMerchantAPI) {
            $tinkoffApi = $instance;
        }


        if (!$tinkoffApi->paymentUrl) {
            DB::rollBack();
            disableLock($lock_ids);
            return response()->json(['error'], 419);
        }

        $payment->tinkoff_payment->updateData($tinkoffApi);
        DB::commit();
        disableLock($lock_ids);
        return \response()->json([
            'url' => $tinkoffApi->paymentUrl
        ]);
    }


    function acceptPayment(Request $request)
    {
        Log::info('Payment Log ' . json_encode($request->all()));
        $tinkoff = TinkoffPayment::with('payment')->hasHold()->find($request->OrderId);
        if (!$tinkoff) {
            return response('OK');
        }

        $current_status = $tinkoff->status;
        $tinkoff->update(['status' => $request->Status]);

        DB::beginTransaction();

        if ($request->Status === TinkoffMerchantAPI::CONFIRMED
            && $current_status !== TinkoffMerchantAPI::CONFIRMED
            && $tinkoff->hasHolds()) {

            try {

                $tinkoff->payment->accept();

            } catch (\Exception $exception) {
                Log::info($exception->getMessage());
                DB::rollBack();
                dispatch(new CancelPayment($tinkoff))->delay(now()->addSeconds(5));
                return response('ОК');
            }

        }
        if (in_array($request->Status, TinkoffMerchantAPI::BAD_STATUSES)) {

            if ($tinkoff->payment->order->holds->isNotEmpty()) {
                $tinkoff->payment->reverse();
            }

        }
        if ($request->Status === TinkoffMerchantAPI::REFUNDED) {

            if ($tinkoff->payment->order->vehicles->isNotEmpty()) {
                $tinkoff->payment->reverse();
            }
        }
        DB::commit();
        return response('OK');
    }

    function verify(Request $request, $id)
    {
        $exists = Payment::query()->where('id', $id)->exists();

        return response()->json([], ($exists ? 200 : 400));
    }


    function getInvoice(Request $request, $alias)
    {

        $html = $request->filled('html');
        $invoice = Invoice::whereAlias($alias)->firstOrFail();

        return $invoice->payment->order->domain->alias === 'ru'
            ? $invoice->getRuInvoice($html)
            : $invoice->getKinoskInvoice($html);
    }

    function getSummary($id)
    {
        setlocale(LC_TIME, 'ru_RU.UTF-8');
        Carbon::setLocale('ru');

        $payment = Payment::findOrFail($id);

        $order = $payment->order;

        $vehicles = $order->vehicles;

        $pdf = PDF::loadView('invoice.summary', compact('vehicles', 'order'));

        return $pdf->stream('summary.pdf');
    }

}

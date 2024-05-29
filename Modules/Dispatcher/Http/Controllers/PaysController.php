<?php

namespace Modules\Dispatcher\Http\Controllers;

use App\Http\Controllers\Avito\Events\OrderChangedEvent;
use App\Http\Controllers\Avito\Models\AvitoOrder;
use App\Service\RequestBranch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\Dispatcher\Entities\DispatcherInvoice;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\Payments\InvoicePay;
use Modules\Orders\Services\OrderDocumentService;

class PaysController extends Controller
{
    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch->companyBranch;

        $block = $this->companyBranch->getBlockName(CompanyRoles::BRANCH_PAYMENTS);

        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only('index');
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_CREATE)->only(['store', 'update']);

    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request, $invoice_id)
    {
        $invoice = DispatcherInvoice::forBranch()->findOrFail($invoice_id);

        return $invoice->pays;
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request, $invoice_id)
    {
        $invoice = DispatcherInvoice::forBranch()->findOrFail($invoice_id);

        $request->validate([
            'type' => 'required|in:cash,cashless',
            'operation' => 'required|in:in,out',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
            'method' => 'nullable|in:card,pledge,bank',
            'sum' => 'required|numeric|min:1' . ($request->input('operation') === 'in' ? ( '|max:' . ($invoice->sum - $invoice->paid_sum) / 100) : ''),
        ], [
            'sum.max' => trans('transbaza_order.validation_sum_max')
        ]);

        $lock = Cache::lock("dispatcher_invoice_{$invoice->id}", 600);
        if (!$lock->get()) {
            return response()->json(['errors' => 'Операция заблокирована, подождите некоторое время и попробуйте снова.'], 400);
        }

        try {

            DB::beginTransaction();

            $pay = new InvoicePay([
                'type' => $request->input('type'),
                'date' => Carbon::parse($request->input('date') . ' ' . $request->input('time')),
                'sum' => numberToPenny($request->input('sum')),
                'operation' => $request->input('operation'),
                'method' => $request->input('method'),
                'description' => $request->input('description'),
                'tax_percent' => 0,
                'tax' => 0,
            ]);

            $invoice->pays()->save($pay);

            $invoice->load('pays');

            if ($invoice->paid >= $invoice->sum) {
                if($invoice->owner instanceof Order && $invoice->owner->isAvitoOrder()) {
                    OrderChangedEvent::dispatch($invoice->owner, AvitoOrder::STATUS_PREPAID);
                }
                $invoice->update([
                    'is_paid' => true
                ]);
            }

            DB::commit();


        } catch (\Exception $exception) {

            logger($exception->getMessage() . ' ' . $exception->getTraceAsString());
            DB::rollBack();

        } finally {
            $lock->release();
        }

        return response()->json([
            'pay' => $pay,
        ]);
    }

    function downloadCashOrder(Request $request, $invoice_id)
    {
        $document = new OrderDocumentService();

        $invoice = DispatcherInvoice::forBranch()->findOrFail($invoice_id);
        $pay = $invoice->pays()->findOrFail($request->input('pay_id'));

        $url = $document->formOrderCash($invoice, $pay, toBool($request->input('stamp')));

        return response()->json([
            'url' => $url,
        ]);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return view('dispatcher::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        return view('dispatcher::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($invoiceId, $id)
    {
        $invoice = DispatcherInvoice::forBranch()->findOrFail($invoiceId);
        if($invoice->owner instanceof Order && $invoice->owner->channel === 'avito') {
//            throw ValidationException::withMessages([
//                'errors' => 'Невозможно удалить оплату для заказа Avito'
//            ]);
        }
        $invoice->pays()->where('invoice_pays.id', $id)->delete();

        return response()->json();
    }
}

<?php

namespace Modules\Dispatcher\Http\Controllers;

use App\Finance\Payment;
use App\Service\RequestBranch;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\Dispatcher\Entities\ContractorPay;
use Modules\Dispatcher\Entities\DispatcherInvoice;
use Modules\Dispatcher\Entities\DispatcherOrder;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderComponent;
use Modules\Orders\Entities\Payments\InvoicePay;

class ContractorPaysController extends Controller
{
    private $position, $companyBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch->companyBranch;
        $block = $this->companyBranch->getBlockName(CompanyRoles::BRANCH_PAYMENTS);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_SHOW)->only('index', 'show');
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_CREATE)->only(['store']);

        $this->position = OrderComponent::query()->whereHas('order', function (Builder $q) {
            $q->forBranch($this->companyBranch->id);
        })->findOrFail($request->input('position_id'));


    }

    /**
     * Получение списка выплат подярчикам
     */
    public function index()
    {


        return $this->position->contractorPays;

    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {

        $max_value = ($this->position->getContractorSum() - $this->position->getContractorPaidSum()) / 100;

        $request->validate([
            'type' => 'required|in:cash,cashless',
            'date' => 'required|date',
            'sum' => 'required|numeric|min:1|max:' . $max_value,
        ]);
        $lock = Cache::lock("dispatcher_contractor_pay_{$this->position->id}", 600);
        if (!$lock->get()) {
            return \response('LOCK', 400);
        }

        try {

            $pay = new ContractorPay([
                'type' => $request->input('type'),
                'date' => Carbon::parse($request->input('date'))->format('Y-m-d'),
                'sum' => numberToPenny($request->input('sum')),
            ]);


            $pay->contractor()->associate($this->position->worker->subOwner);

            $this->position->contractorPays()->save($pay);

        } catch (\Exception $exception) {


        } finally {
            $lock->release();
        }

        return response()->json($pay);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return $this->order->contractor_pays()->findOrFail($id);
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
    public function destroy($id)
    {
        //
    }
}

<?php

namespace Modules\CompanyOffice\Http\Controllers;


use AnourValar\EloquentSerialize\Facades\EloquentSerializeFacade;
use App\Service\RequestBranch;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\Paginator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Octane\Facades\Octane;
use Modules\CompanyOffice\Entities\CashRegister;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\Dispatcher\Entities\DispatcherInvoice;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\Payments\InvoicePay;

class CashRegisterController extends Controller
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
            ]);
        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_UPDATE)->only(
            [
                'store',
                'employeeWithdrawal',

            ]);

        $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_DELETE)->only(
            [
                'destroy',

            ]);

    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        /** @var Builder $query */
        $query =
            CashRegister::query()->select('*')->with('expenditure', 'manager')
                ->withCount(['invoice as order_invoice_id' => fn(Builder $q) => $q->select($q->qualifyColumn('id'))])
                ->selectSub(User::query()->select('id')->whereHas('orders', fn (Builder $q) => $q->whereHas('invoices',
                    fn (Builder $q) => $q->whereRaw("`{$q->getModel()->getTable()}`.`id` = `order_invoice_id`"))), 'order_manager')
                ->filter($request->all())
                ->forBranch()
                ->with('machineryBase');

        $incomeQuery = EloquentSerializeFacade::serialize((clone $query)->where('type', 'in'));
        $totalConsumptionQuery = EloquentSerializeFacade::serialize((clone $query)->where('type', 'out'));

        try {
            [$totalIncome, $totalConsumption] = Octane::concurrently([
                fn() => EloquentSerializeFacade::unserialize($incomeQuery)->sum('sum'),
                fn() => EloquentSerializeFacade::unserialize($totalConsumptionQuery)->sum('sum'),
            ]);
        }catch (\Exception $exception) {
            $totalIncome = EloquentSerializeFacade::unserialize($incomeQuery)->sum('sum');
            $totalConsumption = EloquentSerializeFacade::unserialize($totalConsumptionQuery)->sum('sum');
        }

        if ($request->filled('sortBy')) {
            $sort =
                toBool($request->input('sortDesc'))
                    ? 'desc'
                    : 'asc';

            $query->orderBy($request->input('sortBy'), $sort);

        } else {
            $query->orderBy('created_at', 'desc');
        }
        /** @var Paginator $paginator */
        $paginator = $query->with([
            'client_bank_setting'
        ])->paginate(10);
        $collection = $paginator->getCollection();

        $paginator->setCollection($collection->map(function ($item) {
            $item->order_manager = $this->currentBranch->employees->where('id', $item->order_manager)->first();

            return $item;
        }));
        return response()->json([
            'data'              => $paginator,
            'total_income'      => $totalIncome,
            'total_consumption' => $totalConsumption,
        ]);
    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'sum'               => 'required|numeric|min:0',
            'stock'             => 'required|in:cashless,cash,bank_card,pledge',
            'type'              => 'required|in:in,out',
            'machinery_base_id' => 'nullable|integer',
            'comment'           => 'nullable|string',
            'date'              => 'nullable|date',
            'time'              => 'nullable',
            'expenditure_id'    => 'nullable|exists:expenditures,id',
        ]);

        $datetime = null;

        try {
            $datetime = Carbon::parse("{$request->date} {$request->time}", $this->currentBranch->timezone);
        } catch (\Exception $exception) {

        }
        $cashRegister = new CashRegister([
            'sum'               => numberToPenny($request->input('sum')),
            'stock'             => $request->input('stock'),
            'type'              => $request->input('type'),
            'company_branch_id' => $this->currentBranch->id,
            'machinery_base_id' => $request->machinery_base_id,
            'comment'           => $request->comment,
            'expenditure_id'    => $request->expenditure_id,
            'datetime'          => now($this->currentBranch->timezone),
            'creator_id'        => Auth::id(),
        ]);
        $cashRegister->timestamps = false;
        $cashRegister->created_at =
            $datetime
                ?: now()->setTimezone($this->currentBranch->timezone);

        $cashRegister->save();

    }

    function breakPays(
        Request $request,
                $branch,
                $id)
    {
        $request->validate([
            '*.id'  => 'required|exists:machinery_bases,id',
            '*.sum' => 'required|numeric|min:0'
        ]);
        $collection = collect($request->all());

        $oldCash = CashRegister::query()->forBranch()->findOrFail($id);

        if ($oldCash->sum / 100 !== $collection->sum('sum')) {
            $neededSum = $oldCash->sum / 100;
            throw ValidationException::withMessages(['errors' => "Общая сумма должна быть равной {$neededSum}"]);
        }
        \DB::transaction(function () use
        (
            $oldCash,
            $request,
            $collection
        ) {

            $order = Order::query()->findOrFail($oldCash->ref->id);
            $newDefaultSum = 0;
            foreach ($collection as $base) {
                $sum = numberToPenny($base['sum']);

                if ((int)$base['id'] === $oldCash->machinery_base_id) {
                    $newDefaultSum = $sum;
                    continue;
                }

                $outCashRegister = new CashRegister([
                    'sum'               => $sum,
                    'stock'             => $oldCash->stock,
                    'type'              => 'out',
                    'company_branch_id' => $oldCash->company_branch_id,
                    'machinery_base_id' => $oldCash->machinery_base_id,
                    'creator_id'        => \Auth::id(),
                    'comment'           => "Сделка #{$order->internal_number}",
                    'invoice_pay_id'    => $oldCash->invoice_pay_id,
                    'ref'               => [
                        'id'       => $order->id,
                        'bases'    => [],
                        'instance' => 'order'
                    ],
                    'created_at'        => $oldCash->created_at,
                    'datetime'          => now($this->currentBranch->timezone)
                ]);

                $outCashRegister->timestamps = false;
                $outCashRegister->created_at = $oldCash->created_at;
                $outCashRegister->save();

                $cashRegister = new CashRegister([
                    'sum'               => $sum,
                    'stock'             => $oldCash->stock,
                    'type'              => $oldCash->type,
                    'company_branch_id' => $oldCash->company_branch_id,
                    'machinery_base_id' => $base['id'],
                    'creator_id'        => \Auth::id(),
                    'comment'           => "Сделка #{$order->internal_number}",
                    'invoice_pay_id'    => $oldCash->invoice_pay_id,
                    'ref'               => [
                        'id'       => $order->id,
                        'bases'    => [],
                        'instance' => 'order'
                    ],
                    'created_at'        => $oldCash->created_at,
                    'datetime'          => now($this->currentBranch->timezone)
                ]);
                $cashRegister->timestamps = false;
                $cashRegister->created_at = $oldCash->created_at;
                $cashRegister->save();
            }

            $oldCash->update([
                'comment' => "Сделка #{$order->internal_number}",
                'ref'     => [
                    'id'       => $order->id,
                    'bases'    => [],
                    'instance' => 'order'
                ],
            ]);

        });
        return response()->json();
    }

    function destroy(
        $branch,
        $id)
    {
        CashRegister::query()->withoutPays()->forBranch()->where('id', $id)->delete();
    }

    function employeeWithdrawal(Request $request)
    {
        $hasItems = !!$request->input('selected');
        $request->validate([
            'machinery_base_id' => $hasItems
                ? 'nullable'
                : 'required|exists:machinery_bases,id',
            'selected'          => 'nullable|array',
            'sum'               => $hasItems
                ? 'nullable'
                : 'required|numeric|min:0',
            'employee_id'       => $hasItems
                ? 'nullable'
                : 'required',
        ]);

        $pays = $hasItems ? InvoicePay::query()->whereHasMorph('invoice', [DispatcherInvoice::class], function ($q) {
            $q->forBranch();
        })->whereIn('method', ['card', 'bank'])->whereIn('id', $request->input('selected'))->get() : ([
            (object)[  'sum' => numberToPenny($request->input('sum'))]
        ]);

        DB::beginTransaction();

        foreach ($pays as $pay) {
            if($pay instanceof InvoicePay) {
                $pay->update([
                    'method' => null
                ]);
                $cashReg = CashRegister::query()->where('invoice_pay_id', $pay->id)->first();
                if (!$cashReg) {
                    continue;
                }

                $ref = $cashReg->ref;
                $ref->method = null;
                $cashReg->ref = $ref;
                $cashReg->save();
            }

            $cashRegister = new CashRegister([
                'sum'               => $pay->sum,
                'stock'             => $hasItems ? 'cash' : 'bank_card',
                'type'              => 'out',
                'company_branch_id' => $this->currentBranch->id,
                'machinery_base_id' => $hasItems ? $cashReg->machinery_base_id : $request->input('machinery_base_id'),
                'comment'           => 'Снятие с карты сотрудника',
                'datetime'          => now($this->currentBranch->timezone),
                'ref'               => [
                    'instance' => 'withdrawal'
                ],
                'creator_id'        => $hasItems ? Auth::id() : $request->input('employee_id'),
            ]);

            $cashRegister->save();

            $cashRegister = new CashRegister([
                'sum'               => $pay->sum,
                'stock'             =>  $hasItems ? 'cash' : 'bank_card',
                'type'              => 'in',
                'company_branch_id' => $this->currentBranch->id,
                'machinery_base_id' => $hasItems ? $cashReg->machinery_base_id : $request->input('machinery_base_id'),
                'comment'           => 'Распределение с карты сотрудника',
                'ref'               => [
                    'instance' => 'withdrawal'
                ],
                'datetime'          => now($this->currentBranch->timezone)->addSecond(30),
                'creator_id'        => $hasItems ? Auth::id() : $request->input('employee_id'),
            ]);

            $cashRegister->save();
        }


        DB::commit();


        return response()->json();
    }
}

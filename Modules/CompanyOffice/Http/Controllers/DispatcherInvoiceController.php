<?php

namespace Modules\CompanyOffice\Http\Controllers;

use App\Service\RequestBranch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\CompanyOffice\Entities\CashRegister;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\Dispatcher\Entities\DispatcherInvoice;
use Modules\Dispatcher\Entities\Lead;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\Payments\InvoicePay;
use Modules\Orders\Entities\Service\ServiceCenter;

class DispatcherInvoiceController extends Controller
{
    /** @var CompanyBranch */
    private $currentBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {
        $this->currentBranch = $companyBranch->companyBranch;
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function getInvoices(Request $request)
    {
        $invoices = DispatcherInvoice::query()
            ->with([
                'owner',
                'customerRequisite'
            ])
            ->where('is_paid', false)
            ->where('company_branch_id', $this->currentBranch->id);

        if ($request->filled('internal_number')) {
            $invoices = $invoices->whereHasMorph('owner', [
                Lead::class,
                Order::class,
                ServiceCenter::class
            ], function ($q) use ($request) {
                return $q->where('internal_number', 'LIKE', "%{$request->input('internal_number')}%");
            });
        }

        if ($request->filled('invoice_id')) {
            $invoices = $invoices->where('number', 'LIKE', "%{$request->input('invoice_id')}%");
        }

        if ($request->filled('customer_id')) {
            $invoices = $invoices->whereHas('customerRequisite', function ($q) use ($request) {
                return $q->where('requisite_id', $request->input('customer_id'));
            });
        }

        $invoices = $invoices->get();
        $invoices = $invoices->map(function ($invoice) {
            $invoice->order_number = $invoice->owner?->internal_number;
            $invoice->owner_name = $invoice->requisite?->name;
            $invoice->to_pay = 0;

            return $invoice;
        });
        return $invoices;

    }

    public function distributeInvoices(Request $request, CashRegister $cr)
    {

        try {
            DB::beginTransaction();
            foreach ($request->payed_invoices as $inv) {
                $invoice = DispatcherInvoice::forBranch()->findOrFail($inv['id']);
                $pay = new InvoicePay([
                    'type' => 'cashless',
                    'date' => Carbon::now()->format('Y-m-d H:i:s'),
                    'sum' => $inv['to_pay'],
                    'operation' => 'in',
                    'method' => 'card',
                    'tax_percent' => 0,
                    'tax' => 0,
                ]);

                $invoice->pays()->save($pay);

                $invoice->load('pays');

                $distributeSum = $inv['to_pay'] / 100;

                $cr->update([
                    'sum' => DB::raw("sum - {$inv['to_pay']}"),
                    'comment' => DB::raw("CONCAT(comment, '| Распределено: $invoice->number - $distributeSum р.')")
                ]);

                if ($invoice->paid >= $invoice->sum) {
                    $invoice->update([
                        'is_paid' => true
                    ]);
                }
            }
            DB::commit();
        } catch (\Exception $exception) {
            logger($exception->getMessage().' '.$exception->getTraceAsString());
            DB::rollBack();
        }

        return response()->json([]);
    }
}

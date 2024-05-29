<?php

namespace Modules\AdminOffice\Http\Controllers;

use App\Finance\TinkoffPayment;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\AdminOffice\Entities\Filter;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\Payment;
use Modules\Orders\Entities\Payments\Invoice;
use Modules\Orders\Entities\Payments\InvoicePay;
use Modules\Orders\Http\Requests\PaymentRequest;
use Modules\Orders\Services\OrderService;

class PaymentsController extends Controller
{
    private function modifyQuery(Request $request, $payments)
    {


        $filter = new Filter($payments);

        $filter->getEqual([
            'payment_id' => 'payment_id',
            'status' => 'status',
        ])->getBetween([
            'amount_from' => 'amount',
            'amount_to' => 'amount',
        ], true);
        if ($request->filled('user')) {

            $payments->whereHas('user', function ($q) use ($request) {
                $q->where('email', 'like', "%{$request->input('user')}%")
                    ->orWhere('phone', 'like', "%{$request->input('user')}%");
            });

        }
        $payments->orderBy('created_at', 'DESC');
    }

    function getPayments(Request $request, $id = null)
    {

        $payments = Payment::with('user', 'order', 'invoice')->forDomain();

        if ($id) {
            return $payments->findOrFail($id)->makeVisible(['payment_id']);
        }
        $this->modifyQuery($request, $payments);
        $payments = $payments->paginate($request->per_page ?: 10);
        $payments->each(function ($item) {
            $item->makeVisible(['payment_id']);
        });
        return $payments;
    }

    function acceptInvoice($id)
    {
        $payment = Payment::whereStatus('wait')->whereHas('invoice')->findOrFail($id);

        $payment->accept();

        return $payment;
    }


    function updatePayment(Request $request, $id)
    {

    }

    function getInvoice($id)
    {
        return Invoice::findOrFail($id);
    }

    function addPay(Request $request, $invoice_id)
    {
        $invoice = Invoice::findOrFail($invoice_id);

        $request->validate([
            'type' => 'required|in:card,cashless',
            'date' => 'required|date',
            'sum' => 'required|numeric|min:1',
            'tax_percent' => 'required|numeric|min:0',
            'tax' => 'required|numeric|min:0',
            'method' => 'nullable|in:card',
        ]);

        $invoicePay = new InvoicePay([
            'type' => $request->input('type'),
            'date' => $request->input('date'),
            'sum' => numberToPenny($request->input('sum')),
            'tax_percent' => $request->input('tax_percent'),
            'tax' => $request->input('tax'),
            'method' => $request->input('method'),
        ]);
        $invoice->pays()->save($invoicePay);

        return $invoicePay;
    }


    function generate(PaymentRequest $request)
    {
        $service = new OrderService();

        $user = User::query()->findOrFail($request->user_id);

        return $service->generatePayment($request, $user);
    }
}

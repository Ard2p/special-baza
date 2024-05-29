<?php

namespace Modules\Dispatcher\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Dispatcher\Entities\DispatcherInvoice;
use Modules\Dispatcher\Entities\DispatcherOrder;
use Modules\Orders\Services\OrderDocumentService;
use PDF;

class InvoiceGeneratorController extends Controller
{

    function getPdfDetails($id)
    {
        $order = DispatcherOrder::query()->whereStatus(DispatcherOrder::STATUS_ACCEPT)->findOrFail($id);

        $pdf = PDF::loadView('dispatcher::order_details', compact('order'));

        return $pdf->stream('details.pdf');
    }

    function getPdf(Request $request, $alias)
    {

        $invoice = DispatcherInvoice::whereAlias($alias)->firstOrFail();

        $service = new OrderDocumentService();


        $data =  $service->formInvoice($invoice, toBool($request->input('stamp')));
        if(isset($data['preview'])) {
            return response()->json($data);
        }
        return response()->json([
            'url' => $service->formInvoice($invoice, toBool($request->input('stamp')))
        ]);
       // $dispatcher = $invoice->getDispatcherRequisites();
//
       // $order = $invoice->owner;
       // // dd($order);
       // $fmt = numfmt_create($order->company_branch->company->domain->options['default_locale'], \NumberFormatter::CURRENCY);
//
       // $customer = $invoice->getCustomerRequisites();
//
//
       // $pdf = PDF::loadView(($order->company_branch->company->domain->alias === 'ru'
       //     ? 'invoice.dispatcher' : 'invoice.kinosk_dispatcher'), compact('dispatcher', 'invoice', 'customer', 'order', 'fmt'));

      //  return $pdf->stream('invoice.pdf');

    }

}

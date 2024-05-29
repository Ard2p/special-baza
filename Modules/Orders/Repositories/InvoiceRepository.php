<?php

namespace Modules\Orders\Repositories;

use App\Http\Controllers\Avito\Models\AvitoOrder;
use App\Service\RequestBranch;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;
use Modules\Dispatcher\Entities\DispatcherInvoice;
use Modules\Dispatcher\Entities\InvoiceItem;
use Modules\Dispatcher\Http\Controllers\InvoiceController;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\Payments\InvoicePay;
use Modules\Orders\Services\OrderDocumentService;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class InvoiceRepository
{
    private Logger $logger;

    public function __construct(Order $order)
    {
        $this->logger = new Logger('invoice-repository');
        $this->logger->pushHandler(new StreamHandler(storage_path('logs/invoice-repository/' . now()->format('Y-m-d') . '.log')));
    }

    /**
     * @param Order $order
     * @return DispatcherInvoice
     * @throws ValidationException
     */
    public function makeInvoice(Order $order): DispatcherInvoice
    {
        $invoice = [
            'type' => 'time_calculation',
            'use_oneC' => 0,
            'owner_id' => $order->id,
            'owner_type' => 'order',
            'items' => []
        ];

        foreach ($order->components->where('status','accept') as $position) {
            $invoice['items'][] = [
                'id' => $position->id,
                'order_duration' => $position->order_duration,
            ];
        }

        $request = new Request($invoice);
        $request->headers->add([
            'company' => $order->company_branch->company->alias,
            'branch' => $order->company_branch->id,
        ]);

        $c = new InvoiceController($request, new RequestBranch(fn() => $request));
        $c->store($request);

        $this->syncHoldPayments($order->avito_order);

        $order->load('invoices');

        return $order->invoices()->first();
    }

    /**
     * @param AvitoOrder $avitoOrder
     * @return void
     * @throws ValidationException
     */
    private function syncHoldPayments(AvitoOrder $avitoOrder): void
    {
        if ($avitoOrder->hold > 0) {
            $invoice = $avitoOrder->order->invoices->first();

            if ($invoice->sum === 0) {
                $invoice->update([
                    'sum' => $avitoOrder->hold,
                    'is_paid' => 1,
                    'paid_sum' => $avitoOrder->hold,
                ]);

                $this->addPaymentToInvoice($avitoOrder->hold, $invoice);

            } else {
                $invoice->update([
                    'number' => DB::raw('CONCAT(number, "/", 2)'),
                ]);
                $holdInvoice = $this->createDispatcherInvoice($avitoOrder->hold, $avitoOrder->avito_order_id . '/1', $invoice);
                (new OrderDocumentService([], $avitoOrder->order->company_branch))->formInvoice($holdInvoice, false, 'default_avito_invoice_url');
            }
            $avitoOrder->update([
                'hold' => 0
            ]);
        }
    }

    /**
     * @param int $sum
     * @param string $number
     * @param DispatcherInvoice $invoice
     * @param string|null $type
     * @return DispatcherInvoice
     */
    public function createDispatcherInvoice(int $sum, string $number, DispatcherInvoice $invoice, string $type = null): DispatcherInvoice
    {
        /** @var DispatcherInvoice $invoiceAvito */
        $invoiceAvito = DispatcherInvoice::query()->create([
            'sum' => $sum,
            'number' => $number,
            'is_paid' => 1,
            'alias' => 1,
            'requisite_id' => $invoice->requisite_id,
            'requisite_type' => $invoice->requisite_type,
            'main_requisite_id' => $invoice->main_requisite_id,
            'main_requisite_type' => $invoice->main_requisite_type,
            'owner_type' => $invoice->owner_type,
            'owner_id' => $invoice->owner_id,
            'company_branch_id' => $invoice->company_branch_id,
            'paid_sum' => $sum,
            'type' => $type ?? $invoice->type,
        ]);

        $item = $invoice->positions()->first();
        $invoiceItem = new InvoiceItem([
            'use_oneC' => $item->use_oneC,
            'owner_id' => $item->owner_id,
            'owner_type' => $item->owner_type,
            'cost_per_unit' => $sum,
            'amount' => $type === 'avito_dotation' ? 1 : $item->amount,
            'name' => $type === 'avito_dotation' ? 'Доплата по заказу: ' . $invoice->owner->external_id : $item->name,
            'description' => $type === 'avito_dotation' ? 'Доплата по заказу: ' . $invoice->owner->external_id : $item->description,
            'vendor_code' => $item->vendor_code,
            'unit' => $type === 'avito_dotation' ? 'Штук' : $item->unit,
        ]);
        $invoiceAvito->positions()->save($invoiceItem);

        $this->addPaymentToInvoice($sum, $invoiceAvito);

        return $invoiceAvito;
    }

    /**
     * @param int $sum
     * @param DispatcherInvoice $invoice
     * @return void
     */
    private function addPaymentToInvoice(int $sum, DispatcherInvoice $invoice): void
    {
        $pay = new InvoicePay([
            'type' => 'cashless',
            'date' => Carbon::now()->format('Y-m-d H:i:s'),
            'sum' => $sum,
            'operation' => 'in',
            'method' => 'bank',
            'tax_percent' => 0,
            'tax' => 0,
        ]);
        $invoice->pays()->save($pay);
    }

    public function updateDispatcherInvoice(int $avito_dotation_sum, DispatcherInvoice $avitoDotationInvoice)
    {
        $avitoDotationInvoice->update([
            'sum' => $avito_dotation_sum,
            'paid_sum' => $avito_dotation_sum,
        ]);

        $avitoDotationInvoice->pays()->first()->update([
            'sum' => $avito_dotation_sum,
        ]);

        return $avitoDotationInvoice->fresh();
    }

}

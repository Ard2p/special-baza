<?php

namespace Modules\Orders\Repositories;

use App\Http\Controllers\Avito\Events\OrderChangedEvent;
use App\Http\Controllers\Avito\Models\AvitoOrder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Modules\Dispatcher\Entities\DispatcherInvoice;
use Modules\Orders\Entities\Order;
use Modules\Orders\Services\AvitoPayService;
use Modules\Orders\Services\OrderDocumentService;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class OrderRepository
{
    /**
     * @param Order $order
     * @return void
     */
    public static function revertToPrepare(Order $order): void
    {
        if (!$order->isAvitoOrder()) {
            return;
        }
        if ($order->invoices->count() > 0 && request()->input('delete_all')) {
            $sum = $order->invoices->where('type', '!=', 'avito_dotation')->sum('paid_sum');

            $order->avito_order->update([
                'hold' => $sum
            ]);
        }

        /** @var Collection<DispatcherInvoice> $invoicesToDelete */
        self::clearInvoices($order);

        $order->tmp_status = Order::STATUS_ACCEPT;
        $order->save();

        OrderChangedEvent::dispatch($order, AvitoOrder::STATUS_CREATED);
    }

    private static function logger(): Logger
    {
        $logger = new Logger('order-repository');
        $logger->pushHandler(new StreamHandler(storage_path('logs/order-repository/' . now()->format('Y-m-d') . '.log')));
        return $logger;
    }

    /**
     * @param Order $order
     * @return void
     * @throws ValidationException
     */
    public static function makeAvitoInvoice(Order $order): void
    {
        if ($order->tmp_status === Order::STATUS_AGREED
            && $order->isAvitoOrder()
            && $order->invoices()->doesntExist()
        ) {
            $invoiceRepository = new InvoiceRepository($order);
            $invoice = $invoiceRepository->makeInvoice($order);

            $invoice->update([
                'type' => 'avito'
            ]);

            self::addDotation($order, $invoice);

            (new OrderDocumentService([], $order->company_branch))->formInvoice($invoice, false, 'default_avito_invoice_url');

            self::sendDataToAvito($order);
        }
    }

    /**
     * @param Order $order
     * @param mixed $invoice
     * @return void
     * @throws ValidationException
     */
    private static function addDotation(Order $order, mixed $invoice): void
    {
        $position = $order->components()->first();

        if ($position->avito_dotation_sum > 0) {
            $invoiceRepository = new InvoiceRepository($order);
            $avitoDotationInvoice = $order->invoices()->where('type', 'avito_dotation')->first();
            if(!$avitoDotationInvoice) {
                $avitoInvoice = $invoiceRepository->createDispatcherInvoice($position->avito_dotation_sum, $order->external_id . '-AV', $invoice, 'avito_dotation');
                (new OrderDocumentService([], $order->company_branch))->formInvoice($avitoInvoice, false, 'default_avito_invoice_url');
            }else{
                $avitoInvoice = $invoiceRepository->updateDispatcherInvoice($position->avito_dotation_sum, $avitoDotationInvoice);
                (new OrderDocumentService([], $order->company_branch))->formInvoice($avitoInvoice, false, 'default_avito_invoice_url');
            }
        }
    }

    /**
     * @param Order $order
     * @return void
     */
    private static function sendDataToAvito(Order $order): void
    {
        if ($order->invoices()->sum('paid_sum') >= $order->amount) {
            OrderChangedEvent::dispatch($order, AvitoOrder::STATUS_PREPAID);
        } else {
            $avitoService = app(AvitoPayService::class);
            $now = now()->format("d.m.Y");
            $avitoService->registerPayment(
                sum: $order->amount,
                description: "Оплата по заказу № $order->external_id от $now",
                details: [/*order data */],
                successUrl: "https://m.avito.ru/web/1/mrt/payment/result_page?order_id=$order->external_id&result=success", //
                failUrl: "https://m.avito.ru/web/1/mrt/payment/result_page?order_id=$order->external_id&result=failure",
                modelOwner: $order->invoices->first(),
            );

            OrderChangedEvent::dispatch($order, AvitoOrder::STATUS_PROPOSED);
        }
    }

    /**
     * @param Order $order
     * @return void
     */
    public static function clearInvoices(Order $order): void
    {
        if (!request()->input('delete_all')) {
            return;
        }
        $invoicesToDelete = $order->invoices()->get();

        $invoicesToDelete->each(function (DispatcherInvoice $invoice) {
//            self::logger()->info('Удаление документов по счету № ' . $invoice->number, $invoice->documents()->get()->toArray());
            $invoice->positions()->delete();
            $invoice->documents()->delete();
//            self::logger()->info('Удаление счета № ' . $invoice->number);
            $invoice->delete();
        });
    }

}

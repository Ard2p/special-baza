<?php

namespace App\Http\Controllers\Avito\Events;

use App\Http\Controllers\Avito\Models\AvitoOrder;
use App\Http\Controllers\Avito\Models\AvitoStat;
use App\Jobs\CreateAvitoOrderJob;
use DB;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Mail;
use Modules\Orders\Entities\Order;
use Modules\Orders\Repositories\OrderRepository;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class OrderChangedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public Order $order;
    public int $oldStatus;
    public int $newStatus;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Order $order, int $newStatus, mixed $cancelReason = null, bool $force = false)
    {

        $logger = new Logger('integration-api');
        $logger->pushHandler(new StreamHandler(
            storage_path('logs/integration-api/' . now()->format('Y-m-d') . '.log'),Logger::DEBUG,true,0777
        ));

        $avitoOrder = AvitoOrder::query()->where('order_id', $order->id)->latest()->first();
        $this->oldStatus = $avitoOrder->status;
        $this->order = $order;
        $this->newStatus = $newStatus;

        if (($newStatus === AvitoOrder::STATUS_CANCELED && $this->oldStatus > 2) || $newStatus !== AvitoOrder::STATUS_CANCELED || $force) {

            $avitoOrder->update([
                'status' => $newStatus,
                'cancel_reason' => $cancelReason
            ]);
        }

        if ((($newStatus === AvitoOrder::STATUS_CREATED && $order->status === 'close') || $newStatus === AvitoOrder::STATUS_CANCELED) && !$force) {

            $logger->debug('Avito cancel order data', [
                'Avito ad id' => $avitoOrder->avito_ad_id,
                'Avito order id' => $avitoOrder->avito_order_id,
                'Order id' => $avitoOrder->order_id,
                'Company branch id' => $avitoOrder->company_branch_id,
            ]);
            $logger->debug('Request body', [$avitoOrder->log->request_body]);
            $logger->debug('Request url', [$avitoOrder->log->request_url]);
            $logger->debug('==================================================================================>');
            if ($order->components->first()->reject_type === 'timeout') {
                $avitoOrder->histories()->where('company_branch_id', $order->company_branch_id)->update([
                    'timeout_cancel' => DB::raw("timeout_cancel + 1")
                ]);
            }
            if ($order->invoices->count() > 0) {
                $sum = $order->invoices->where('type', '!=', 'avito_dotation')->sum('paid_sum');
                $avitoOrder->update([
                    'hold' => $sum
                ]);
            }
            OrderRepository::clearInvoices($order);
            $autoSearch = config('avito.auto_search');
            if($autoSearch) {
                CreateAvitoOrderJob::dispatch($avitoOrder->log->request_body, $avitoOrder->log->request_url, $avitoOrder->order->comment)->onQueue('avito');
            }else{
                $avitoOrder->update([
                    'status' => $newStatus,
                    'cancel_reason' => $cancelReason
                ]);
            }
        }
    }

}

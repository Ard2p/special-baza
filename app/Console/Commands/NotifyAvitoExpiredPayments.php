<?php

namespace App\Console\Commands;

use App\Finance\TinkoffMerchantAPI;
use App\Finance\TinkoffPayment;
use App\Http\Controllers\Avito\Models\AvitoOrder;
use App\Service\Avito\AvitoApiService;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mail;
use Modules\Orders\Jobs\CancelPayment;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class NotifyAvitoExpiredPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'avito:payments_expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check avito orders for expired payments';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->client = new Client();
        $this->logger = new Logger('check-payments');
        $this->logger->pushHandler(new StreamHandler(storage_path('logs/check-avito-payments/' . now()->format('Y-m-d') . '.log')));
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->logger->debug('Start payments checked');
        $avitoOrders = AvitoOrder::query()
            ->where('status', AvitoOrder::STATUS_PROPOSED)
            ->where('pay_reminder', 0)->get();
        foreach ($avitoOrders as $avitoOrder) {
            $this->logger->debug('Order found', $avitoOrder->toArray());
            $order = $avitoOrder->order;
            if (!$order) {
                $this->logger->error('No order for avito_order_id ' . $avitoOrder->id);
                continue;
            }
            $invoice = $order->invoices->first();

            if(!$invoice){
                continue;
            }

            if ($invoice->is_paid === false && now()->diffInHours($invoice->created_at) >= 1) {
                $ssl = config('app.ssl');
                $frontUrl = config('app.front_url');
                $companyBranchId = $order->company_branch->id;
                $companyAlias = $order->company_branch->company->alias;

                $url = "$ssl://$companyAlias.$frontUrl/branch/$companyBranchId/orders/{$order->id}";
                $notifyMails = config('avito.notify_mails');
                Mail::send([], [], function ($message) use ($order, $avitoOrder, $url,$notifyMails) {
                    $message->to($notifyMails)
                        ->subject("Нет оплаты счета по заказу Avito номер $avitoOrder->avito_order_id более 1 часа.")
                        ->setBody("<p>Нет оплаты счета по заказу Avito номер $avitoOrder->avito_order_id более 1 часа.</p><br><p>OrderURL: $url</p>", 'text/html'); // for HTML rich messages
                });
                $avitoOrder->update([
                   'pay_reminder' => 1
                ]);
            }
        }

        $this->logger->debug('Payments checked');
    }
}

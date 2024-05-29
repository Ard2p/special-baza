<?php

namespace App\Console\Commands;

use App\Finance\TinkoffMerchantAPI;
use App\Finance\TinkoffPayment;
use App\Http\Controllers\Avito\Models\AvitoOrder;
use App\Http\Controllers\Avito\Repositories\AvitoRepository;
use App\Service\Avito\AvitoApiService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mail;
use Modules\Orders\Jobs\CancelPayment;
use Modules\RestApi\Entities\Domain;

class CheckAvitoByTimeout extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'avito:timeout_check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check avito orders expired timeout';
    private readonly AvitoRepository $avitoRepository;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->domain = Domain::whereAlias('ru')->first();
        $this->avitoRepository = new AvitoRepository($this->domain);

        $avitoOrders = AvitoOrder::query()
            ->where('status', AvitoOrder::STATUS_CREATED)
            ->where('timeout_reminder', 0)
            ->where('created_at', '<=', now()->subMinutes(10)->format('Y-m-d H:i'))->get();

        foreach ($avitoOrders as $avitoOrder) {
            $order = $avitoOrder->order;
            if (!$order) {
                continue;
            }


            $ssl = config('app.ssl');
            $frontUrl = config('app.front_url');
            $companyBranchId = $order->company_branch->id;
            $companyAlias = $order->company_branch->company->alias;

            $url = "$ssl://$companyAlias.$frontUrl/branch/$companyBranchId/orders/{$order->id}";

            $dtsFrom = Carbon::parse($avitoOrder->start_date_from);
            $dtsTo = Carbon::parse($avitoOrder->start_date_to);
            $offset = $dtsFrom->diffInDays($dtsTo);
            $machinery = collect([]);
            try {

                $machinery = $this->avitoRepository->findAllAvailableMachinery(
                    $avitoOrder->avito_ad_id,
                    $avitoOrder->avito_order_id,
                    $dtsFrom,
                    $avitoOrder->rental_duration,
                    $avitoOrder->rental_duration + $offset
                );
            }catch (\Exception $e){

            }

            $createdAt = Carbon::parse($avitoOrder->created_at)->format("d.m.Y H:i");
            $message = <<<MESSAGE
                                <p>Через 5 минут заканчивается таймаут по сделке Avito номер $avitoOrder->avito_order_id .</p>
                                <br>
                                <p>Время создания сделки: $createdAt</p>
                                <br>
                                <p>OrderURL: <a href="$url">$url</a></p>
MESSAGE;
            try {
                $notifyMails = config('avito.notify_mails');

                Mail::send('email.avito-timeout-notification', [
                    'machinery' => $machinery,
                    'textMessage' => $message,
                ], function ($message) use ($order, $avitoOrder, $url, $notifyMails) {
                    $message->to($notifyMails)
                        ->subject("Окончание таймаута по заказу Avito номер $avitoOrder->avito_order_id через 5 мин.");
                });
            }catch(\Exception $e){

            }

            $avitoOrder->update([
                'timeout_reminder' => 1
            ]);
        }
    }
}

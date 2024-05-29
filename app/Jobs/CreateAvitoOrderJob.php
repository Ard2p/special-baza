<?php

namespace App\Jobs;

use App\Http\Controllers\Avito\Dto\CreateOrderConditions;
use App\Http\Controllers\Avito\Events\OrderChangedEvent;
use App\Http\Controllers\Avito\Requests\CreateOrderRequest;
use App\Http\Controllers\Avito\Services\AvitoService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class CreateAvitoOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private readonly AvitoService $avitoService;

    public $tries = 3;
    public $maxExceptions = 4;
    public $backoff = [10, 60, 120];
    public function tags(){
        return ['avito:create-order'];
    }
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private $request, private $url, private $comment = null)
    {
        logger()->debug('Create job started');
        $this->avitoService = new AvitoService();
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws Exception
     */
    public function handle()
    {
        logger()->debug('Handle started');
        if($this->comment && is_array($this->request)){
            $this->request['comment'] = $this->comment;
        }
        logger()->debug('Handle request', [$this->request]);
        $conditions = new CreateOrderConditions($this->request);
        logger()->debug('Conditions', [$conditions]);
        $conditions->customer->name = $conditions->customer->name ?? "";
        $this->avitoService->createOrder($conditions, $this->url);
    }

    public function failed($event)
    {

        logger()->error('Queue failed!', [$event]);
//        Mail::send([], [], function ($message) use ($event) {
//
//            $ssl = config('app.ssl');
//            $frontUrl = config('app.front_url');
//            $companyBranchId = $event->order->company_branch->id;
//            $companyAlias = $event->order->company_branch->company->alias;
//
//            $url = "$ssl://$companyAlias.$frontUrl/branch/$companyBranchId/orders/{$event->order->id}";
//            $notifyMails = config('avito.notify_mails');
//            $message->to($notifyMails)
//                ->subject("Ошибка отправки статуса в Авито! Avito Order Id: {$event->order->external_id}")
//                ->setBody("<p>Попытка отправки статса: {$event->oldStatus} -- {$event->newStatus}</p><p>Для просмотра перейдите по ссылке: <a href='$url'>$url</a></p>", 'text/html'); // for HTML rich messages
//        });
    }
}

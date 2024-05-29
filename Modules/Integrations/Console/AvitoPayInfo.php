<?php

namespace Modules\Integrations\Console;

use App\Mail\AvitoNotificationEmail;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\Orders\Entities\Order;

class AvitoPayInfo extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'avito:pay-info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description.';


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $orders = Order::query()->where('channel', 'avito')
            ->where('created_at', '>=', now()->subHour())
            ->where('status', Order::STATUS_ACCEPT)
            ->whereDoesntHave('invoices', fn(Builder $builder) => $builder->where('is_paid', true))
            ->get();

        dump($orders->pluck('id'));
        if($orders->isNotEmpty()) {

            $orders->groupBy('company_branch_id')->each(function ($orders, $branch) {

                $branch = CompanyBranch::query()->findOrFail($branch);

                $ssl = config('app.ssl');
                $frontUrl = config('app.front_url');
                $companyBranchId = $branch->id;
                $companyAlias = $branch->company->alias;

                $urls = collect();
                foreach ($orders as $order) {
                    $builder = new \AshAllenDesign\ShortURL\Classes\Builder();
                    $shortURLObject = $builder->destinationUrl("$ssl://$companyAlias.$frontUrl/branch/$companyBranchId/orders/{$order->id}")->make();
                    $shortURL = $shortURLObject->default_short_url;
                    $urls->push("<a href='$shortURL'>$shortURL</a><br>");
                }


                foreach ($branch->employees as $user) {

                    if ($user->sms_notify && config('services.mail.enabled')) {
                        $notifyMails = config('avito.notify_mails');
                        \Mail::to($user->email)
                            ->bcc($notifyMails)
                            ->send(new AvitoNotificationEmail(
                                    "Необходимо отменить неоплаченую сделку авито. Номера сделок: ". $orders->pluck('internal_number')->join(', '),
                                    "<br><br>Для просмотра заказа перейдите по ссылке: " . $urls->join(''),
                                    $branch->support_link
                                )
                            );
                    }
                }
            });

        }
    }

}

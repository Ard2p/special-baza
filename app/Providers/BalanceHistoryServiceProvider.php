<?php

namespace App\Providers;

use App\Directories\TransactionType;
use App\Notifications\BalanceNotification;
use App\User\BalanceHistory;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

class BalanceHistoryServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        BalanceHistory::created(function ($model) {



        //    Notification::send($model->user, (new BalanceNotification($model)));
            //$model->user->notify(new BalanceNotification());
        });
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}

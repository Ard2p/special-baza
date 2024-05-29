<?php

namespace App\Providers;

use App\Finance\FinanceTransaction;
use App\Notifications\Admin\TransactionNotification;
use App\Option;
use Illuminate\Support\ServiceProvider;

class TransactionServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        FinanceTransaction::created(function($model){

            if($model->status == 0){

                $model->notify(new TransactionNotification($model->user));
            }

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

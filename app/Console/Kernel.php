<?php

namespace App\Console;

use App\Console\Commands\CheckOrdersPledges;
use App\Console\Commands\ClearAudits;
use App\Console\Commands\ClearExpiredPayments;
use App\Console\Commands\CreateGoogleBackup;
use App\Console\Commands\DeleteOldCalendar;
use App\Console\Commands\GetExchangeRates;
use App\Console\Commands\GetTelematicData;
use App\Console\Commands\PlanningTechnicalWork;
use App\Console\Commands\TelematicLastPosition;
use App\Console\Commands\UpdateOrderActualValues;
use App\Console\Commands\WatchProposals;
use App\Jobs\SitemapGenerate;
use App\Jobs\UisCollectCalls;
use App\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;
use Modules\AdminOffice\Console\GrabCalls;
use Modules\Integrations\Console\AvitoPayInfo;
use Modules\Integrations\Entities\Beeline\BeelineTelephony;
use Modules\Integrations\Entities\MangoTelephony;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        GetExchangeRates::class,
        ClearAudits::class,
        WatchProposals::class,
        ClearExpiredPayments::class,
        GrabCalls::class,
        DeleteOldCalendar::class,
        GetTelematicData::class,
        TelematicLastPosition::class,
        CreateGoogleBackup::class,
        UpdateOrderActualValues::class,
        CheckOrdersPledges::class,
        AvitoPayInfo::class,
        PlanningTechnicalWork::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {

        $schedule->command('checkAvitoPayments')
            ->everyMinute()
            ->withoutOverlapping();

        $schedule->command('technical-work:plan')
            ->dailyAt('01:00');

        $schedule->command('avito:pay-info')
            ->hourly()
            ->withoutOverlapping();

        $schedule->command('trans:check-orders-pledges')
            ->everyMinute()
            ->withoutOverlapping();

        $schedule->command('trans:get-exchange-rates')->dailyAt('7:05');
        $schedule->command('avito:payments_expired')
            ->everyMinute()
            ->withoutOverlapping();

        $schedule->command('avito:timeout_check')
            ->everyMinute()
            ->withoutOverlapping();

        $schedule->call(function () {

            \DB::table('failed_jobs')->where('failed_at', '<', now()->subDays(7)->format('Y-m-d H:i:s'))->delete();
        })->hourly();


        $schedule->job(new SitemapGenerate())->dailyAt('06:00');

        $schedule->job(new UisCollectCalls())->everyFifteenMinutes();

        if (!app()->environment('local')){
            $schedule->command('update_google_backup')->daily();
        }

        $schedule->command('checkHolds')
            ->everyMinute()
            ->withoutOverlapping();

        $schedule->command('audits:clear')
            ->daily();

        $schedule->command('payments:clear_expired')
            ->everyMinute();

        if (config('app.env') === 'production') {
            /* $schedule->command('calls:grab')
                 ->hourly();*/
        }

        $schedule->call(function () {
            foreach (BeelineTelephony::all() as $telephony) {
                $telephony->registerSubscription();
            }

        })->daily();

        $schedule->call(function () {

            $sipunis = \Modules\Integrations\Entities\SipuniTelephonyAccount::query()->get();
            foreach ($sipunis as $sipuni) {
                $sipuni->getStats(now()->subMinutes(10), now());
            }
        })->everyTenMinutes();

        $schedule->command('calendar:clear-busy')
            ->daily();

        $schedule->command('proposals:close')
            ->hourly();

        $schedule->command('telematic:pull')->dailyAt('01:00');
        $schedule->command('orders:update-overdue')->everyThirtyMinutes()->withoutOverlapping();


        $schedule->command('wialon:update-last-position')->hourly()->withoutOverlapping();


        $schedule->call(function () {
            $accesses = DB::table('oauth_access_tokens')->where('expires_at', '<', now())->get();
            $ids = $accesses->pluck('id');

            DB::table('oauth_refresh_tokens')->whereIn('access_token_id', $ids)->delete();

            DB::table('oauth_access_tokens')->whereIn('id', $ids)->delete();
        })->daily();


        $schedule->call(function () {

            foreach (MangoTelephony::query()->get() as $mango) {
                collect($mango->getStats(now()->subMinutes(10), now()))
                    ->pipe(fn($result) => collect($result['data'][0]['list'] ?? []))
                    ->map(fn($call) => [
                        'context_type' => $call['context_type'],
                        'caller_name' => $call['caller_name'],
                        'caller_number' => $call['caller_number'],
                        'called_number' => $call['called_number'],
                        'recall_status' => $call['recall_status'],
                        'context_status' => $call['context_status'],
                        'entry_id' => $call['entry_id'],
                        'context_start_time' => $call['context_start_time'],
                    ])
                    ->each(fn($data) => $mango->parseCall($data));
            }
        })->everyFiveMinutes();




        //   $schedule->command('backup:clean')->daily()->at('03:00');
        //   $schedule->command('backup:run')->daily()->at('04:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}

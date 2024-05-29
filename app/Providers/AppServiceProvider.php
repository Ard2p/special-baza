<?php

namespace App\Providers;

use App\Option;
use App\Service\RequestBranch;
use App\User;
use Auth;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\CompanyOffice\Entities\Company;
use Modules\Orders\Entities\OrderComponent;
use Modules\RestApi\Entities\Domain;
use Modules\RestApi\Entities\SocialAuth\LinkedIn;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

        JsonResource::withoutWrapping();
        URL::forceScheme('https');
        Date::setToStringFormat('Y-m-d H:i:s');
        Date::serializeUsing(function (Date $date): string {
            return $date->format('Y-m-d H:i:s');
        });
        Carbon::serializeUsing(function (Carbon $carbon): string {
            return $carbon->format('Y-m-d H:i:s');
        });
        $env = config('app.env');
        if (app()->runningInConsole() && $env == 'local') {
            // return;
            $user = User::query()->findOrFail(13);
            $company = Company::query()->with('branches')->where('alias', 'company128')->firstOrFail();
            $branch = $company->branches()->with([
                'employees'
            ])->findOrFail(128);
            Config::set('request_domain', Domain::whereAlias('ru')->first());
            Config::set('request_company', $company);
            Config::set('request_branch', $branch);
            $request = new Request();
            $request->setRouteResolver(function () use ($request) {
                return (new Route('GET', '/test', []))->bind($request);
            });
            $request->merge([
                'contractor_id' => $user->id,
                'customer_id' => 1,
                'position_id' => OrderComponent::first()->id,
            ]);
            $request->headers->add([
                'company' => $company->alias,
                'branch' => $branch->id
            ]);
            $this->app->request = $request;
            app()->singleton(RequestBranch::class, function ($app) use ($company, $branch, $request) {
                return new RequestBranch(function () use ($request) {
                    return $request;
                });
            });

            Auth::guard('web')->loginUsingId($user->id);
            Auth::guard('api')->setUser($user);
        }
        $options = Option::all();


        $menu = [];/*StaticContent::with('subMenuArticles', 'subMenuArticlesSections')
            ->where('menu_show', 1)
            ->orderBy('order', 'asc')
            ->get();*/


        Config::set('global_options', $options);
        Config::set('menu', $menu);
        View::composer('*', function ($view) use ($options, $menu) {
            $random_articles = [];
            $latest_news = [];
            $random_machine = [];
            $view->with('random_articles', $random_articles);
            $view->with('latest_news', $latest_news);
            $view->with('random_machine', $random_machine);
            $view->with('global_options', $options)
                ->with('global_static_contents', $menu);
        });

        //  Model::preventLazyLoading(!$this->app->environment('production'));
        $this->app->singleton(RequestBranch::class, function ($app) {
            return new RequestBranch(fn() => $app['request']);
        });

        \Queue::after(function (JobProcessed $event) {

            $mailTransport = app()->make('mailer')
                ->getSwiftMailer()
                ->getTransport();

            if ($mailTransport instanceof \Swift_SmtpTransport) {
                /** @var \Swift_SmtpTransport $mailTransport */
                $mailTransport->setUsername(env('MAIL_USERNAME'));
                $mailTransport->setPassword(env('MAIL_PASSWORD'));
                // Port and authentication can also be configured... You get the picture
            }

        });
        \Gate::define('ltm-admin-translations', function () {
            return true;
        });
        $this->bootLinkedInSocialite();
    }

    private function bootLinkedInSocialite()
    {
        $socialite = $this->app->make('Laravel\Socialite\Contracts\Factory');
        $socialite->extend(
            'LinkedIn',
            function ($app) use ($socialite) {
                $config = $app['config']['services.linkedin'];
                return $socialite->buildProvider(LinkedIn::class, $config);
            }
        );
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}

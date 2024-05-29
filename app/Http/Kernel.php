<?php

namespace App\Http;

use App\Http\Middleware\AccessCheck;
use App\Http\Middleware\AdminHelperAccess;
use App\Http\Middleware\AvitoTokenCheck;
use App\Http\Middleware\Block;
use App\Http\Middleware\ChooseLanguage;
use App\Http\Middleware\ContentAdmin;
use App\Http\Middleware\DisableEditMode;
use App\Http\Middleware\EmailVerify;
use App\Http\Middleware\Freeze;
use App\Http\Middleware\OnlineUser;
use App\Http\Middleware\RegionalRepresentative;
use App\Http\Middleware\RequestChecker;
use App\Http\Middleware\RequestCompany;
use App\Http\Middleware\TrimPhones;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Session\Middleware\StartSession;
use Modules\AdminOffice\Http\Middleware\Operator;
use Modules\RestApi\Http\Middleware\AppendSeo;
use Modules\RestApi\Http\Middleware\Cors;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \App\Http\Middleware\TrustProxies::class,
        Cors::class,
        RequestCompany::class,
        ChooseLanguage::class,
        OnlineUser::class,
        TrimPhones::class,
        Block::class
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
          //  \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
         // \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
        //    RequestChecker::class,
         //   \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        //   \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            'throttle:60,1',
            'bindings',
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'admin' => \App\Http\Middleware\AdminCheck::class,
        'customer' => \App\Http\Middleware\Customer::class,
        'performer' => \App\Http\Middleware\Performer::class,
        'widget' => \App\Http\Middleware\WebWidget::class,
        'email_verify' => EmailVerify::class,
        'freeze' => Freeze::class,
        'block' => Block::class,
        'no_edit_mode' => DisableEditMode::class,
        'contentAdmin' => ContentAdmin::class,
        'api_cors' => Cors::class,
        'append_seo' => AppendSeo::class,
        'rp_user' => RegionalRepresentative::class,
        'admin_helper_access' => AdminHelperAccess::class,
        'accessCheck' => AccessCheck::class,
        'auth.avito' => AvitoTokenCheck::class,
        'operator' => Operator::class
    ];
}

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option controls the default authentication "guard" and password
    | reset options for your application. You may change these defaults
    | as required, but they're a perfect start for most applications.
    |
    */

    'virtual' => env('AVITO_VIRTUAL', false),
    'virtual_company_id' => env('AVITO_VIRTUAL_COMPANY_ID', 128),
    'auto_search' => env('AVITO_AUTO_SEARCH', false),
    'notify_mails' => [
        'ruslan@trans-baza.ru',
        'dzhivolenkov@trans-baza.ru',
        'izhivolenkov@trans-baza.ru',
        'spiminov@trans-baza.ru',
        'vskononenko@avito.ru',
        'aikhatazhenkov@avito.ru',
    ],
];

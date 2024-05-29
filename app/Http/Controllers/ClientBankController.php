<?php

namespace App\Http\Controllers;


use App\Service\RequestBranch;
use Carbon\Carbon;
use Config;
use Illuminate\Http\Request;
use Modules\CompanyOffice\Entities\CashRegister;
use Modules\CompanyOffice\Entities\CashRegisterOperation;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\RestApi\Entities\Domain;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class ClientBankController extends Controller
{
    private $logger;

    public function __construct()
    {
        $this->logger = new Logger('order');
        $this->logger->pushHandler(new StreamHandler(storage_path('logs/clientbank/tochka/'.Carbon::now()->format('Y-m-d').'.log')));
    }

    public function hook(Request $request, CompanyBranch $branch)
    {
        $token = $request->getContent();
        $this->logger->debug('Incoming payment:',['token' => $token]);

        $company = $branch->company;

        Config::set('request_domain', Domain::whereAlias('ru')->first());
        Config::set('request_branch', $branch);
        Config::set('request_company', $company);

        app()->singleton(RequestBranch::class, function ($app) use ($company, $branch) {
            $request = new Request();
            $request->headers->add([
                'company' => $company->alias,
                'branch' => $branch->id
            ]);
            return new RequestBranch(function () use ($request) {
                return $request;
            });
        });

        $clientBankSetting = $branch->client_bank_settings->where('name', 'tochka')->first();

        $tokenParts = explode(".", $token);
        $tokenHeader = base64_decode($tokenParts[0]);
        $tokenPayload = base64_decode($tokenParts[1]);
        $jwtHeader = json_decode($tokenHeader, true);
        $jwtPayload = json_decode($tokenPayload, true);

        $sum = $jwtPayload['SideRecipient']['amount'];

        $cashRegister = CashRegister::query()->create([
            'sum' => $sum * 100,
            'stock' => 'cashless',
            'type' => 'in',
            'company_branch_id' => $branch->id,
            'comment' => "{$jwtPayload['SideRecipient']['name']} {$jwtPayload['SideRecipient']['inn']} {$jwtPayload['purpose']} Сумма: $sum р.",
            'datetime' => $jwtPayload['date'],
            'is_clientbank' => true,
            'client_bank_setting_id' => $clientBankSetting->id,
        ]);

        CashRegisterOperation::query()->create([
            'company_cash_register_id' => $cashRegister->id,
            'sum' => $cashRegister->sum,
            'type' => CashRegisterOperation::TYPE_PAY_FROM_BANK,
            'client_bank_setting_id' => $clientBankSetting->id,
            'request' => $jwtPayload,
        ]);

        return response()->json(['status' => 'ok']);
    }
}

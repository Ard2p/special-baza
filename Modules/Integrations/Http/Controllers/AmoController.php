<?php

namespace Modules\Integrations\Http\Controllers;

use App\Service\RequestBranch;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Services\CompanyRoles;
use Modules\Integrations\Entities\Amo\AmoLead;
use Modules\Integrations\Services\Amo\AmoService;
use Modules\Integrations\Services\Amo\AmoUserService;

class AmoController extends Controller
{

    private $currentBranch;

    public function __construct(Request $request, RequestBranch $companyBranch)
    {

        if(Str::contains($request->route()->getActionName(), 'auth')) {
            $this->companyBranch = $companyBranch->companyBranch;
            $block = $this->currentBranch->getBlockName(CompanyRoles::BRANCH_CLIENTS);
            $this->middleware("accessCheck:{$block}," . CompanyRoles::ACTION_CREATE)->only(
                [
                    'auth',
                ]);
        }

    }

    /**
     * Получение ссылки для авторизации в AmoCRM
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    function auth(Request $request)
    {

        $amo = new AmoService();

        return response()->json([
            'url' => $amo->getAuthUrl($this->currentBranch->id)
        ]);
    }

    /**
     * Сохранение токена для AmoCRM
     * @param Request $request
     * @return bool|\Illuminate\Http\RedirectResponse
     */
    function setToken(Request $request)
    {
        $amo = new AmoService();


        return $amo->setToken(
            $request->input('code'),
            $request->input('state'),
            $request->input('referer'),
            $request->input('client_id')
        );
    }


    function newLeadHook(Request $request)
    {
      //  logger(json_encode($request->all()));

        $branch_id = Crypt::decrypt($request->input('company_branch_id'));

        $service = new AmoUserService(CompanyBranch::query()->findOrFail($branch_id));



        $inLead = $request->input('leads');

        if (isset($inLead['add'][0]['id'])) {

           // $lock = Cache::lock($inLead['add'][0]['id']);


            try {
//                if ($lock->get()) {
                    $service->addLeadFromRequest($inLead['add'][0]);

//                    $lock->release();
//                }

            }catch (\Exception $exception) {

            //    $lock->release();
                logger($exception->getMessage() . ' ' . $exception->getTraceAsString());

            }


        }

        if (isset($inLead['update'][0]['id'])) {

          //  $lock = Cache::lock($inLead['update'][0]['id']);


            try {
          //      if ($lock->get()) {
                    $service->updateLeadFromRequest($inLead['update'][0]);

               //     $lock->release();
              //  }

            }catch (\Exception $exception) {

            //    $lock->release();

                logger($exception->getMessage() . ' ' . $exception->getTraceAsString());

            }



        }

        return response()->json();
    }
}

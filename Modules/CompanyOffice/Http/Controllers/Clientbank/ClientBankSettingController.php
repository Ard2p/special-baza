<?php

namespace Modules\CompanyOffice\Http\Controllers\Clientbank;

use App\Service\ClentBank\TochkabankService;
use App\Service\RequestBranch;
use DB;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\CompanyOffice\Entities\Company\ClientBankSetting;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Entities\Company\InsSetting;
use Modules\CompanyOffice\Services\CompanyRoles;

class ClientBankSettingController extends Controller
{

    /** @var CompanyBranch */
    private $currentBranch;

    public function __construct(RequestBranch $companyBranch)
    {
        $this->currentBranch = $companyBranch->companyBranch;
//        $block = $this->currentBranch->getBlockName(CompanyRoles::BRANCH_DASHBOARD);
//        $this->middleware("accessCheck:{$block},".CompanyRoles::ACTION_SHOW)->only(
//            [
//                'show',
//                'store',
//                'update',
//                'delete'
//            ]);
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $data['company_branch_id'] = $this->currentBranch->id;
            $setting = ClientBankSetting::query()->create($data);
            if ($setting->name === 'tochka') {
                $service = new TochkabankService();
                if (!$service->createWebhook()) {
                    throw new \Exception('Failed add tochka integration');
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
        return $setting;
    }

    public function get()
    {
        return $this->currentBranch->client_bank_setting;
    }

    public function update(Request $request, ClientBankSetting $setting)
    {
        try {
            DB::beginTransaction();
            $setting->update($request->all());
            if ($setting->name === 'tochka') {
                $service = new TochkabankService();
                if (!$service->createWebhook()) {
                    throw new \Exception('Failed update tochka integration');
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
        return $setting;
    }

    public function destroy(Request $request, ClientBankSetting $setting)
    {
        try {
            $service = new TochkabankService();
            if (!$service->deleteWebhook()) {
                throw new \Exception('Failed update tochka integration');
            }
        } catch (\Exception $e) {
            return $this->sendError($e);
        }
        $setting->delete();
        return response()->json([]);
    }

    /**
     * @param  \Exception  $e
     * @return \Illuminate\Http\JsonResponse
     */
    private function sendError(\Exception $e): \Illuminate\Http\JsonResponse
    {
        Log::error($e->getMessage().'===> File: '.$e->getFile().' '.$e->getLine());
        return \response()->json(['status' => 'fail'], 422);
    }
}

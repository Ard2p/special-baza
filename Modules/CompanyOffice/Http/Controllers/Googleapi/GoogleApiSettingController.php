<?php

namespace Modules\CompanyOffice\Http\Controllers\Googleapi;

use App\Service\Google\CalendarService;
use App\Service\RequestBranch;
use Carbon\Carbon;
use DB;
use Google_Client;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Entities\Company\GoogleApiSetting;
use Modules\CompanyOffice\Entities\Company\GoogleCalendar;

class GoogleApiSettingController extends Controller
{
    private $companyBranch;

    public function __construct(RequestBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch->companyBranch;
    }

    public function store(Request $request, Google_Client $client)
    {
        try {
            logger()->debug('Request', $request->toArray());
            $branch = CompanyBranch::query()->find($request->input('company_branch_id'));
            $redirect_uri = config('services.google.redirect').'?company_branch_id='.$branch->id;
            $client->setRedirectUri($redirect_uri);
            $access_token = $client->fetchAccessTokenWithAuthCode($request->input('code'));
            logger()->debug('Fetch access by code', ['access_token' => $access_token]);
            GoogleApiSetting::query()->updateOrCreate([
                'scope' => $access_token['scope'],
                'refresh_token' => $access_token['refresh_token'],
                'company_branch_id' => $branch->id,
            ], [
                'access_token' => $access_token['access_token'],
                'expires_in' => $access_token['expires_in'],
                'created' => Carbon::parse($access_token['created'])->setTimezone('Europe/Moscow'),
                'company_branch_id' => $branch->id,
                'refresh_token' => $access_token['refresh_token'],
                'scope' => $access_token['scope'],
            ]);

            DB::commit();
            return redirect()->away(config('app.ssl')."://{$branch->company->alias}.".config('app.front_url')."/branch/{$branch->id}/branch-profile");
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e);
        }
    }

    public function getAuthUrl(Google_Client $client)
    {
        $redirect_uri = config('services.google.redirect').'?company_branch_id='.$this->companyBranch->id;
        $client->setRedirectUri($redirect_uri);
        $auth_url = $client->createAuthUrl();
        Log::debug('Auth '.$auth_url);
        return response()->json([
            'redirect_url' => $auth_url
        ]);
    }

    public function get()
    {
        return $this->companyBranch->google_api_settings()->with('calendars')->get()->first();
    }

    public function delete(Request $request, CalendarService $service)
    {
        $apiSettings = $service->revokeToken();
        if($apiSettings){
            $apiSettings->calendars()->delete();
            $apiSettings->delete();
        }
        return response()->json([
            'status' => 'revoked'
        ]);
    }

    public function createCalendar(Request $request, CalendarService $service)
    {
        $branch = $this->companyBranch;

        $res = $service->createCalendar($request->input('summary'));

        return GoogleCalendar::query()->updateOrCreate([
            'company_branch_id' => $branch->id,
            'type' => $request->input('type'),
            'google_api_setting_id' => $branch->google_api_settings->id
        ], [
            'summary' => $res['summary'],
            'google_id' => $res['id'],
            'created' => $res['created'],
        ]);
    }

    public function deleteCalendar(Request $request, CalendarService $service, GoogleCalendar $calendar)
    {
        $res = $service->deleteCalendar($calendar->google_id);
        if ($res) {
            $calendar->delete();
        }
        return $res;
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

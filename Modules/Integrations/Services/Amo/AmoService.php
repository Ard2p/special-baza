<?php


namespace Modules\Integrations\Services\Amo;


use App\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\Integrations\Entities\Amo\AmoAuthToken;

class AmoService
{

    const CLIENT_ID = 'c575bc30-9bc7-4768-a9a7-75d4bb818d83';
    const CLIENT_SECRET = 'VGY7TuKOr5E0GKmKGdxO0LI3oRMDgOswT7cblcvtNgedZi1wnicL2MrHak7l7Ft3';

    public $apiClient;
    public function __construct()
    {

        $this->apiClient = new \AmoCRM\Client\AmoCRMApiClient(self::CLIENT_ID, self::CLIENT_SECRET, route('amo_redirect_url'));
    }

    function getAuthUrl($company_branch_id)
    {


        $authorizationUrl = $this->apiClient->getOAuthClient()->getAuthorizeUrl([
            'state' => Crypt::encrypt($company_branch_id),
            'mode' => 'post_message', //post_message - редирект произойдет в открытом окне, popup - редирект произойдет в окне родителе
        ]);


        return $authorizationUrl;
    }

    function setToken($code, $state, $refer, $client_id)
    {

        if($client_id !== self::CLIENT_ID) {
            logger('Wrong amo client ID' . $client_id);
            return false;
        }

        $this->apiClient->setAccountBaseDomain($refer);

        $accessToken = $this->apiClient->getOAuthClient()->getAccessTokenByCode($code);

        $branch = CompanyBranch::findOrFail(Crypt::decrypt($state));


        $data = [
            'access_token' => $accessToken->getToken(),
            'refresh_token' => $accessToken->getRefreshToken(),
            'base_domain' => $refer,
            'expires_at' => Carbon::createFromTimestamp($accessToken->getExpires()),
        ];

        if($branch->amoCrmAuth) {
            $branch->amoCrmAuth->update($data);
        }else {
            $branch->amoCrmAuth()->save(new AmoAuthToken($data));
        }
       /* $this->apiClient->setAccessToken($accessToken)
            ->setAccountBaseDomain($accessToken->getValues()['baseDomain']);*/

       return redirect()->to($branch->getUrl('branch-profile'));
    }


}
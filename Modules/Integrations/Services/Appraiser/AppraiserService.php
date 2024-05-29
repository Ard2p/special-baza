<?php


namespace Modules\Integrations\Services\Appraiser;


use GuzzleHttp\Client;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;

class AppraiserService
{
    private $url;

    private $currentBranch;

    private $client;


    public function __construct(CompanyBranch $currentBranch)
    {
        $this->currentBranch = $currentBranch;

        $this->url = "https://appraiser.trans-baza.com/api/integration/branch/{$this->currentBranch->id}/";

        $this->client = new Client([
            'base_uri' => $this->url,
            'http_errors' => false,
            'verify' => false,
            'query' => [
                'branch' => $this->currentBranch->id,
            ]

        ]);
    }


    function getMachineryAssesments($machinery_id)
    {
        $assessments = $this->client->get("machineries/{$machinery_id}/assessments");

        return $assessments->getStatusCode() === 200
            ? json_decode($assessments->getBody()->getContents(), true)
            : [];
    }
}
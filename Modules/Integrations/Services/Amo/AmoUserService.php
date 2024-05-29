<?php


namespace Modules\Integrations\Services\Amo;


use AmoCRM\Collections\CatalogElementsCollection;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Filters\CatalogElementsFilter;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Models\CatalogElementModel;
use AmoCRM\Models\CatalogModel;
use AmoCRM\Models\CustomFields\UrlCustomFieldModel;
use AmoCRM\Models\CustomFieldsValues\TextareaCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\TextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\UrlCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\TextareaCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\UrlCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\TextareaCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\UrlCustomFieldValueModel;
use AmoCRM\Models\LeadModel;
use AmoCRM\Models\WebhookModel;
use App\Machines\MachineryModel;
use App\Support\Gmap;
use App\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\ContractorOffice\Entities\Vehicle\Price;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\Lead;
use Modules\Dispatcher\Services\LeadService;
use Modules\Integrations\Entities\Amo\AmoLead;


class AmoUserService
{

    const FIELD_ADDRESS = 'TB_ADDRESS';
    const FIELD_TYPE = 'TB_TYPE';
    const FIELD_TYPE_COUNT = 'TB_TYPE_COUNT';
    const FIELD_DATETIME = 'TB_DATETIME';
    const FIELD_DESCRIPTION = 'TB_DESCRIPTION';
    const FIELD_LINK = 'TB_LINK';

    const WEB_HOOK_LEAD = 'TB_WEB_HOOK_LEAD';

    private $work_types = [
        'Час' => 'hour',
        'Смена' => 'shift',
    ];
    public $client;
    public $companyBranch;

    function getCustomFieldsCodes()
    {
        return [
            self::FIELD_DATETIME,
            self::FIELD_TYPE_COUNT,
            self::FIELD_TYPE,
            self::FIELD_LINK,
            self::FIELD_ADDRESS,
            self::FIELD_DESCRIPTION
        ];
    }

    public function __construct(CompanyBranch $companyBranch)
    {
        $client = new AmoService();


        $token = new AccessToken([

            'access_token' => $companyBranch->amoCrmAuth->access_token,
            'refresh_token' => $companyBranch->amoCrmAuth->refresh_token,
            'expires_in' => $companyBranch->amoCrmAuth->expires_at->timestamp,
        ]);

        $client->apiClient->setAccessToken($token)
            ->setAccountBaseDomain($companyBranch->amoCrmAuth->base_domain)
            ->onAccessTokenRefresh(
                function (AccessTokenInterface $accessToken, string $baseDomain) use ($companyBranch) {

                    $data = [
                        'access_token' => $accessToken->getToken(),
                        'refresh_token' => $accessToken->getRefreshToken(),
                        'expires_at' => Carbon::createFromTimestamp($accessToken->getExpires()),
                    ];
                    $companyBranch->amoCrmAuth->update($data);
                }
            );
        $this->client = $client->apiClient;
        $this->company_branch = $companyBranch;

    }

    function getLeads()
    {
        return $this->client->leads();
    }

    function setIntegration()
    {
        $customFieldsService = $this->client->customFields(\AmoCRM\Helpers\EntityTypesInterface::LEADS);


        $linkCustomFieldValuesModel = new UrlCustomFieldModel();
        $linkCustomFieldValuesModel
            ->setCode(self::FIELD_LINK)
            //->setIsRequired(true)
            ->setIsApiOnly(true)
            ->setName('TRANSBAZA #');

        $addressCustomFieldValuesModel = new \AmoCRM\Models\CustomFields\StreetAddressCustomFieldModel();
        $addressCustomFieldValuesModel
            ->setCode(self::FIELD_ADDRESS)
            ->setIsRequired(true)
            ->setName('Адрес доставки');

        $work_type = new \AmoCRM\Models\CustomFields\SelectCustomFieldModel();
        $work_type
            ->setName('Час/Смена')
            ->setSort(30)
            ->setIsRequired(true)
            ->setCode(self::FIELD_TYPE)
            ->setEnums(
                (new \AmoCRM\Collections\CustomFields\CustomFieldEnumsCollection())
                    ->add(
                        (new \AmoCRM\Models\CustomFields\EnumModel())
                            ->setValue('Час')
                            ->setSort(10)
                    )
                    ->add(
                        (new \AmoCRM\Models\CustomFields\EnumModel())
                            ->setValue('Смена')
                            ->setSort(20)
                    )
            );
        $numeric = new \AmoCRM\Models\CustomFields\NumericCustomFieldModel();
        $numeric->setName('Кол-во часов/смен')->setCode(self::FIELD_TYPE_COUNT)->setSort(40)->setIsRequired(true);

        $dateTime = new \AmoCRM\Models\CustomFields\DateTimeCustomFieldModel();
        $dateTime->setName('Дата и время начала работ')->setCode(self::FIELD_DATETIME)->setSort(50)->setIsRequired(true);

        $collection = new \AmoCRM\Collections\CustomFields\CustomFieldsCollection();

        $collection->add($addressCustomFieldValuesModel);
        $collection->add($numeric);
        $collection->add($work_type);
        $collection->add($dateTime);
        $collection->add($linkCustomFieldValuesModel);

        $customFieldsService->add($collection);

        $webhook = new WebhookModel();
        $webhook->setDestination(
            route('amo_lead_hook', ['company_branch_id' => Crypt::encrypt($this->companyBranch->id)])
        )
            ->setId(self::WEB_HOOK_LEAD)
            ->setSettings([
                'add_lead',
                'update_lead',
            ]);

        try {
            $webhook = $this->client->webhooks()->subscribe($webhook);
        } catch (AmoCRMApiException $e) {
            logger($e->getMessage());
        }

        $this->addMachineryModels();

        return $this;
    }

    function addMachineryModels()
    {

        $allCatalogs = $this->client->catalogs()->get();
        /*        $catalog = new CatalogModel();
                $catalog->setName('Модели TRANSBAZA');
                $catalog->setCatalogType(EntityTypesInterface::PRODUCTS_CATALOG_TYPE_STRING);*/

        $catalog = $allCatalogs->getBy('catalogType', 'products');

        // $this->client->catalogs()->addOne($catalog);


        $catalogElementsService = $this->client->catalogElements($catalog->getId());

        // dd(  $this->client->customFields(EntityTypesInterface::CUSTOM_FIELDS)->get());
        //dd($catalogElementsService->get());

        $catalogElementsCollection = new CatalogElementsCollection();

        foreach (MachineryModel::all() as $item) {

            $catalogElement = new CatalogElementModel();

            $values = new CustomFieldsValuesCollection();

            $values->add(
                (new TextareaCustomFieldValuesModel())
                    ->setFieldCode('SKU')
                    ->setValues(
                        (new TextareaCustomFieldValueCollection())->add(
                            (new TextareaCustomFieldValueModel())->setValue("tb_{$item->alias}"))
                    ));

            $catalogElement->setName($item->name)
                ->setCustomFieldsValues($values);

            $catalogElementsCollection->add($catalogElement);

        }

        $catalogElementsService->add($catalogElementsCollection);


        return $this;
    }


    function removeIntegration()
    {
        /*       $allCatalogs = $this->client->catalogs()->get();
               $catalog = $allCatalogs->getBy('catalogType', 'products');

               $catalogElementsFilter = new CatalogElementsFilter();
               $catalogElementsFilter->setQuery('tb_');

               $catalogElementsService = $this->client->catalogElements($catalog->getId());

               $catalogElements =   $catalogElementsService->get($catalogElementsFilter);*/


        $customFieldsService = $this->client->customFields(\AmoCRM\Helpers\EntityTypesInterface::LEADS);

        $customFieldsCollection = $customFieldsService->get();

        if ($customFieldsCollection) {

            foreach ($this->getCustomFieldsCodes() as $code) {
                $fieldToDelete = $customFieldsCollection->getBy('code', $code);

                if ($fieldToDelete) {
                    $customFieldsService->deleteOne($fieldToDelete);
                }
            }

        }


        $lead_hooks = $this->client->webhooks()->get();

        if ($lead_hooks) {
            foreach ($lead_hooks as $hook) {
                $this->client->webhooks()->unsubscribe($hook);
            }
        }


        return $this;
    }


    function parseLead(LeadModel $lead, $TbLead = null)
    {

        $company = $lead->getCompany();

        $company = $this->client->companies()->getOne($company->getId());

        $customerFields = [
            'email' => '',
            'company_name' => $company->getName(),
            'contact_person' => $company->getName(),
            'phone' => '',
            'company_branch_id' => $this->companyBranch->id,
            'creator_id' => $this->companyBranch->creator_id,
            'domain_id' => $this->companyBranch->domain->id,
        ];

        foreach ($company->getCustomFieldsValues() as $customFieldValuesModel) {


            if ($customFieldValuesModel->getFieldCode() === 'PHONE') {
                $customerFields['phone'] = trimPhone($customFieldValuesModel->getValues()->first()->value);
            }
            if ($customFieldValuesModel->getFieldCode() === 'EMAIL') {
                $customerFields['email'] = $customFieldValuesModel->getValues()->first()->value;
            }

        }


        $customer = Customer::query()
            ->forBranch($this->companyBranch->id)
            ->where('phone', $customerFields['phone'])
            ->first()
            ?: Customer::create($customerFields);

        $leadFields = [
            'customer_name' => $customerFields['contact_person'],
            'title' => 'Заявка AmoCRM',
            'phone' => $customerFields['phone'],
            'email' => $customerFields['email'],
            'address' => '',
            'comment' => '',
            'pay_type' => Price::TYPE_CASHLESS_VAT,
            'city_id' => null,
            'status' => Lead::STATUS_OPEN,
            'region_id' => null,
            'publish_type' => Lead::PUBLISH_MAIN,
            'coordinates' => '',
            'vehicles_categories' => [],
        ];
        $order_type = 'shift';
        $order_duration = 1;
        $date_from = '';


        foreach ($lead->getCustomFieldsValues() as $customFieldValuesModel) {


            switch ($customFieldValuesModel->getFieldCode()) {
                case self::FIELD_ADDRESS:
                    $leadFields['address'] = $customFieldValuesModel->getValues()->first()->value;
                    $leadFields['coordinates'] = Gmap::getCoordinatesByAddress($leadFields['address'], '');
                    break;
                case self::FIELD_DESCRIPTION:
                    $leadFields['comment'] = $customFieldValuesModel->getValues()->first()->value;
                    break;
                case self::FIELD_TYPE:
                    $order_type = $this->work_types[$customFieldValuesModel->getValues()->first()->value];
                    break;
                case self::FIELD_TYPE_COUNT:
                    $order_duration = $customFieldValuesModel->getValues()->first()->value;
                    break;
                case self::FIELD_DATETIME:
                    $date_from = Carbon::createFromTimestamp($customFieldValuesModel->getValues()->first()->value);
                    break;
                case self::FIELD_LINK:
                    $crossLink = $customFieldValuesModel;
                    break;
            }

        }
        foreach ($lead->getCatalogElementsLinks() as $catalogElementModel) {
            $arr = [
                'type_id' => '',
                'order_type' => $order_type,
                'order_duration' => $order_duration,
                'count' => $catalogElementModel->getQuantity(),
            ];

            $catalogElementModel = $this->client->catalogElements($catalogElementModel->getCatalogId())->syncOne($catalogElementModel);

            foreach ($catalogElementModel->getCustomFieldsValues() as $customFieldValuesModel) {
                if ($customFieldValuesModel->getFieldCode() === 'SKU') {
                    $sku = $customFieldValuesModel->getValues()->first()->value;

                    $sku = str_replace('tb_', '', $sku);

                    $model = MachineryModel::query()->whereAlias($sku)->first();

                    if ($model) {
                        $arr['id'] = $model->category_id;
                        $arr['machinery_model_id'] = $model->id;
                        $leadFields['vehicles_categories'][] = $arr;
                    }
                }
            }


        }

        $leadService = new LeadService();

        $leadService->setDateFrom($date_from)
            ->setDispatcherCustomer($customer);

        if (!$TbLead) {
            $leadService
                ->createNewLead($leadFields, $this->companyBranch->id, $this->companyBranch->creator_id);
        } else {
            $leadService->updateLead($TbLead, $leadFields);
        }


        $tbLead = $leadService->getLead();


        if (!$tbLead->integration_unique) {

            $linkCustomFieldValuesModel = (new UrlCustomFieldValuesModel())
                ->setFieldCode(self::FIELD_LINK)
                ->setFieldName('TRANSBAZA #');
            $linkCustomFieldValuesModel
                ->setValues((new UrlCustomFieldValueCollection())
                    ->add((new UrlCustomFieldValueModel())
                        ->setValue($this->companyBranch->getUrl("leads/{$tbLead->id}/info"))));


            $custom_values = $lead->getCustomFieldsValues();
            $custom_values->add($linkCustomFieldValuesModel);
            $this->client->leads()->updateOne($lead);
        }

//dd($custom_values);



        return $tbLead;
    }

    function addLeadFromRequest($requestData)
    {
        $leadService = $this->client->leads();

        $lead = $leadService->getOne($requestData['id'], [\AmoCRM\Models\LeadModel::CATALOG_ELEMENTS, \AmoCRM\Models\LeadModel::CONTACTS]);

        $amoLead = AmoLead::create([
            'amo_id' => $lead->getId(),
            'data' => request()->except('company_branch_id'),
        ]);


        DB::beginTransaction();

        try {

            $tb_lead = $this->parseLead($lead);

            $amoLead->update([
                'lead_id' => $tb_lead->id,
            ]);


            if (!$tb_lead->integration_unique) {
                $tb_lead->update([
                    'integration_unique' => "amo_{$amoLead->amo_id}_{$tb_lead->company_branch_id}"
                ]);
            }


        } catch (\Exception $exception) {

            logger($exception->getMessage() . ' ' . $exception->getTraceAsString());
            DB::rollBack();
            return false;
        }


        DB::commit();
    }

    function updateLeadFromRequest($requestData)
    {
        $leadService = $this->client->leads();

        $amoLead = AmoLead::query()
            //->where('status', AmoLead::STATUS_UNPROCESSED)
            ->where('amo_id', $requestData['id'])->first();

        if (!$amoLead) {

            return $this->addLeadFromRequest($requestData);
        }

        if ($amoLead->lead && ($amoLead->lead->status !== Lead::STATUS_OPEN || $amoLead->status === AmoLead::STATUS_PROCESSED)) {

            return;
        }
        $lead = $leadService->getOne($requestData['id'], [\AmoCRM\Models\LeadModel::CATALOG_ELEMENTS, \AmoCRM\Models\LeadModel::CONTACTS]);

        DB::beginTransaction();

        try {

            $tb_lead = $this->parseLead($lead, $amoLead->lead);

            $amoLead->update([
                'lead_id' => $tb_lead->id,
                //  'status' => AmoLead::STATUS_PROCESSED
            ]);

            if (!$tb_lead->integration_unique) {
                $tb_lead->update([
                    'integration_unique' => "amo_{$amoLead->amo_id}_{$tb_lead->company_branch_id}"
                ]);
            }

            DB::commit();
        } catch (\Exception $exception) {

            logger($exception->getMessage() . ' ' . $exception->getTraceAsString());
            DB::rollBack();

        }

    }
}
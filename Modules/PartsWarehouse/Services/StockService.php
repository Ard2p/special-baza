<?php


namespace Modules\PartsWarehouse\Services;


use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\Integrations\Services\OneC\OneCService;
use Modules\PartsWarehouse\Entities\Stock\Stock;

class StockService
{
    private $companyBranch;
    private $data = [];
    private $mainChildren = [];

    public function __construct(CompanyBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch;
    }

    public function getOnecStocks()
    {
        $service = new OneCService($this->companyBranch);

        $onecStocks = $service->getEntityInfo(Stock::class, '');

        return collect($onecStocks);
    }

    function setData($data)
    {
        $this->data['name'] = $data['name'];
        $this->data['address'] = $data['address'];
        $this->data['coordinates'] = $data['coordinates'];
        $this->data['machinery_base_id'] = $data['machinery_base_id'] ?? null;
        if(!empty($data['onec_uuid'])) {
            $stock = $this->getOnecStocks()->firstWhere('Ref_Key', $data['onec_uuid']);
            $this->data['onec_info'] = $stock;
        }

        $this->mainChildren = $data['children'] ?? [];


        return $this;
    }


    private function createOrUpdateChilds($children, $parent_id)
    {
        foreach ($children as $item) {
            if (empty($item['name'])) {
                continue;
            }

            $fields = $item['name'];
            if (!empty($item['id'])) {
                $stock = Stock::query()->forBranch($this->companyBranch->id)->findOrFail($item['id']);
                $stock->update([
                    'name' => $item['name']
                ]);
            } else {
                $stock = Stock::create([
                    'name' => $item['name'],
                    'parent_id' => $parent_id,
                    'company_branch_id' => $this->companyBranch->id,
                ]);
            }

            if (!empty($item['children'])) {
                $this->createOrUpdateChilds($item['children'], $stock->id);
            }
        }

        return $this;
    }

    function createStock()
    {
        $stock = Stock::create(array_merge($this->data, [
            'company_branch_id' => $this->companyBranch->id
        ]));

        $this->createOrUpdateChilds($this->mainChildren, $stock->id);

        return $stock;
    }

    function updateStock($id)
    {
        $stock = Stock::query()->whereNull('parent_id')->forBranch($this->companyBranch->id)->findOrFail($id);
        $stock->update($this->data);

        $this->createOrUpdateChilds($this->mainChildren, $stock->id);

        return $stock;
    }
}

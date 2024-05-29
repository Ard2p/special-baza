<?php

namespace Modules\Dispatcher\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Entities\Company\SaleContract;
use Modules\ContractorOffice\Entities\Vehicle\Shop\MachinerySaleRequest;
use Modules\Dispatcher\Entities\DispatcherOrder;
use Modules\Dispatcher\Entities\Documents\Contract;

trait ContractTrait {



    function getUploadUrl()
    {
        $companyBranch = CompanyBranch::query()->findOrFail($this->company_branch_id);
        $dir = "companies/{$companyBranch->company_id}/branch-{$companyBranch->id}";

        switch (self::class) {
            case MachinerySaleRequest::class:
                $dir .=  '/documents/sales';
                break;
            default:
                $dir .= '/documents/leads';
        }

        return $dir;
    }

    function getContractInstance()
    {

        switch (self::class) {
            case MachinerySaleRequest::class:
               $instance = new SaleContract();
                break;
            default:
                $instance = new Contract();
        }
        return $instance;
    }

    /**
     * @param $name
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
//    function generateDocUrl($name)
//    {
//
//        $dir = $this->getUploadUrl();
//
//        return url("{$dir}/{$this->id}/{$name}");
//    }

    /**
     * @param null $docName
     * @return string
     */
    function getPath($docName = null)
    {
        $dir = $this->getUploadUrl();

        $path = "{$dir}/{$this->id}" . ($docName ? "/{$docName}" : '');

        return $path;
    }



    function addContract($name, $path)
    {

        if (!Storage::disk()->exists($this->getPath())) {
            Storage::disk()->makeDirectory($this->getPath());
        }

        $uniq = now()->format('d.m.Y H:i');

        $new_name = 'contract_'. generateChpu($name) . "_{$uniq}." . getFileExtensionFromString($path);

        $url = $this->getPath($new_name);
        $move = Storage::disk()->move($path, $url);

        if (!$move) {
            return response()->json(['doc' => [trans('tb_messages.file_not_found')]], 400);
        }
        $cnt = $this->getContractInstance();
        $cnt->fill([
            'title' => $name,
            'url' => $url,
            'company_branch_id' => $this->company_branch_id,
            'creator_id' => Auth::id(),
        ]);
        $document = $this->contract()->save($cnt);

        return $document;
    }

}

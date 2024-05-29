<?php

namespace Modules\Orders\Services;

use App\Machinery;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\ContractorOffice\Entities\Vehicle\Shop\MachinerySale;
use Modules\Dispatcher\Entities\DispatcherOrder;
use Modules\Dispatcher\Entities\Lead;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderDocument;
use Modules\Orders\Entities\Service\ServiceCenter;
use Modules\PartsWarehouse\Entities\Shop\Parts\PartsSale;

trait OrderTrait {


    function getCurrentDocDir()
    {
        $dir = OrderDocument::UPLOAD_DIR;

        return match (self::class) {
            DispatcherOrder::class => "{$dir}_d",
            MachinerySale::class => "{$dir}_m_sale",
            PartsSale::class => "{$dir}_p_sale",
            Lead::class => "{$dir}_lead",
            Machinery::class => "{$dir}_machinery",
            CompanyBranch::class => "{$dir}_branch",
            default => $dir
        };
    }
    /**
     * @param $name
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    function generateDocUrl($name)
    {
        $dir = $this->getCurrentDocDir();

        return url("{$dir}/{$this->id}/{$name}");
    }

    /**
     * @param null $docName
     * @return string
     */
    function getDocPath($docName = null)
    {
        $dir = $this->getCurrentDocDir();

        if($this instanceof DispatcherOrder) {
            $dir = "{$dir}_d";
        }

        if($this instanceof ServiceCenter) {
            $dir = "{$dir}_c";
        }


        $path = "{$dir}/{$this->id}" . ($docName ? "/{$docName}" : '');

        return $path;
    }



    function addDocument($name, $path, $owner_type = null, $type = null, $invoiceId = null, $details = null)
    {
        if (!Storage::disk()->exists($this->getDocPath())) {
            Storage::disk()->makeDirectory($this->getDocPath());
        }

        $uniq = now()->format('d.m.Y H:i');

        $new_name = (str_replace('#', '', $name)) . "_{$uniq}." . getFileExtensionFromString($path);

        $url = $this->getDocPath($new_name);

        $move = Storage::disk()->move($path, $url);

        if (!$move) {
            return response()->json(['doc' => [trans('tb_messages.file_not_found')]], 400);
        }

        $document = $this->documents()->save(new OrderDocument([
            'name' => $name,
            'url' => $url,
            'type' => $type,
            'owner_type' => $owner_type,
            'dispatcher_invoice_id' => $invoiceId,
            'user_id' => Auth::id() ?: $this->creator_id,
            'details' => $details,
        ]));

        return $document;
    }

    function updateDocument(OrderDocument $document, $name, $path, $details = null)
    {
        if (!Storage::disk()->exists($this->getDocPath())) {
            Storage::disk()->makeDirectory($this->getDocPath());
        }

        $uniq = now()->format('d.m.Y H:i');

        $new_name = (str_replace('#', '', $name)) . "_{$uniq}." . getFileExtensionFromString($path);

        $url = $this->getDocPath($new_name);

        $move = Storage::disk()->move($path, $url);

        if (!$move) {
            return response()->json(['doc' => [trans('tb_messages.file_not_found')]], 400);
        }

        $document->update([
            'name' => $name,
            'url' => $url,
            'user_id' => Auth::id() ?: $this->creator_id,
            'details' => $details,
        ]);

        return $document;
    }
}

<?php

namespace Modules\CompanyOffice\Entities\Company;

use App\Overrides\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\CompanyOffice\Services\HasManager;
use Modules\ContractorOffice\Services\Shop\SaleService;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\Lead;
use Modules\Orders\Entities\Order;
use Modules\Orders\Services\OrderDocumentService;
use PhpOffice\PhpWord\TemplateProcessor;

class SaleContract extends Model
{

    use BelongsToCompanyBranch, HasManager;

    protected $table = 'company_sale_contracts';

    protected $fillable = [
        'title',
        'url',
        'prefix',
        'postfix',
        'number',
        'creator_id',
        'company_branch_id',
    ];
    const UPLOAD_DIR = 'uploads/documents/contracts';

    function getOrderContractUrl($order)
    {
       // $order = $this->owner;


        try {
            $template = new TemplateProcessor(Storage::disk()->url($this->url));
        }catch (\Exception $exception) {

            $error = ValidationException::withMessages([
                'errors' => [trans('transbaza_validation.docx_error')]
            ]);

            throw $error;
        }


        if($this->owner->customer instanceof Customer) {
            $service = new SaleService();

            $service->setRequisites($template, $order);
        }

        $file = getFileNameFromPath($this->url);

        $path = config('app.upload_tmp_dir') . "/{$file}";

        $template->saveAs(public_path($path));

        return  url($path);

    }

    function getFullNumberAttribute()
    {
        return "{$this->prefix}{$this->number}{$this->postfix}";
    }

    function owner()
    {
        return $this->morphTo();
    }

    function remove()
    {
        \Storage::disk()->delete($this->url);
        $this->delete();
        return;
    }
}

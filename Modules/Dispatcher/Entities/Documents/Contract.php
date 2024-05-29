<?php

namespace Modules\Dispatcher\Entities\Documents;

use App\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use App\Overrides\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\CompanyOffice\Entities\Company\DocumentsPack;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\CompanyOffice\Services\HasManager;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\Lead;
use Modules\Orders\Entities\Order;
use Modules\Orders\Services\DocumentConverter;
use Modules\Orders\Services\HtmlTemplateProcessor;
use Modules\Orders\Services\OrderDocumentService;
use PhpOffice\PhpWord\TemplateProcessor;

class Contract extends Model
{
    use BelongsToCompanyBranch, HasManager;
    protected $table = 'dispatcher_contracts';

    protected $fillable = [
        'title',
        'url',
        'creator_id',
        'lead_id',
        'order_id',
        'company_branch_id',
    ];

    protected $casts =[
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    const UPLOAD_DIR = 'uploads/documents/leads';

    function getLeadContractUrl($documentPackId = null, $subContractorId = null)
    {
        /** @var Lead $lead */
        $lead = $this->lead;

        $fields = [
            'dateFrom' => $lead->start_date->format('d.m.Y'),
            'dateTo' => $lead->date_to->format('d.m.Y'),
        ];
        $templateName = request()->boolean('withStamp') ? 'default_contract_url_with_stamp' : 'default_contract_url';

        if($this->lead->documentsPack) {
            $this->url = $this->lead->documentsPack->{$templateName};
            $this->save();
        }

        try {
            $template = new TemplateProcessor(Storage::disk()->url($this->url));
        } catch (\Exception $exception) {
            $error = ValidationException::withMessages([
                'errors' => [trans('transbaza_validation.docx_error')]
            ]);

            throw $error;
        }

        if ($this->lead->customer instanceof Customer) {
            $service = new OrderDocumentService(['subContractorId' => $subContractorId]);

            $service->setRequisites($template, $this->lead);
        }

        foreach ($fields as $key => $value) {

            $template->setValue($key, $value);

        }
        $extension = getFileExtensionFromString($this->url);

        $customerName = $this->lead->customer ? $this->lead->customer->company_name : '';

        $title = $subContractorId ? "Подр. {$this->title}" : $this->title;

        $uid = now()->format('d.m.Y H:i');

        $newName = "/{$title} {$customerName}_{$uid}.{$extension}";
        $newName = str_replace('#', '', $newName);

        $tmpPdf =  config('app.upload_tmp_dir') . '/' .  "_{$uid}.{$extension}";

        $path = config('app.upload_tmp_dir') . $newName;

        $template->saveAs(public_path($path));

        Storage::disk()->put($path, Storage::disk('public_disk')->get($path));

        $service = new OrderDocumentService();

        //$docs = $lead->documents()->where('name', 'like', "%{$title}%")->get();
//
        //$docs->each(function ($item) {
        //    $item->delete();
        //});

        Storage::disk('public_disk')->rename($path, $tmpPdf);
         $lead->addDocument($title, $path, null, 'contract');
        $pdfDocument = $service->generatePdf(last(explode('/', $tmpPdf)), $tmpPdf, $title, $lead, null, 'contract');

        Storage::disk('public_disk')->delete($path);

        return Storage::disk()->url($pdfDocument['url']);

    }

    function getOrderContractUrl($id, $subContractorId = null)
    {
        /** @var Order $order */
        $order = $this->lead->orders()->findOrFail($id);

        $fields = [
            'dateFrom' => Carbon::parse($order->date_from)->format('d.m.Y'),
            'dateTo' => $order->dateTo->format('d.m.Y'),
        ];
        $templateName = request()->boolean('withStamp') ? 'default_contract_url_with_stamp' : 'default_contract_url';

        if($this->lead->documentsPack) {
            $this->url = $this->lead->documentsPack->{$templateName};
            $htmlTemplateName = $templateName . '_html';
            $htmlTemplateData = $this->lead->documentsPack->{$htmlTemplateName};
            $this->save();
        }

        if($requestPack = request('documents_pack_id')) {
            $documentsPack = DocumentsPack::query()->forBranch()->find($requestPack);
            if($documentsPack) {
                $this->url = $documentsPack->{$templateName};
                $htmlTemplateName = $templateName . '_html';
                $htmlTemplateData = $documentsPack->{$htmlTemplateName};
            }
        }

        try {

            $template = request()->boolean('preview') ? new HtmlTemplateProcessor($htmlTemplateData) :  new TemplateProcessor(Storage::disk()->url($this->url));
        } catch (\Exception $exception) {
            $error = ValidationException::withMessages([
                'errors' => [trans('transbaza_validation.docx_error')]
            ]);

            throw $error;
        }
        $title = $subContractorId ? "Подр. {$this->title}" : $this->title;
        $title .= ' '.now()->format('d.m.Y H:i');

        if ($this->lead->customer instanceof Customer) {
            $service = new OrderDocumentService(['subContractorId' => $subContractorId], $this->lead->company_branch);

            $service->generateTemplateData($order, $template);
            $service->setRequisites($template, $order);
        }

        foreach ($fields as $key => $value) {

            $template->setValue($key, $value);

        }
        $position = $order->components()->first();
        if($position) {
            $template->setValue('vehicleBaseAddress', $position->worker->base->address ?? '');
            $template->setValue('vehicleBase', $position->worker->base->name ?? '');
            $template->setValue('baseKpp', $position->worker->base->kpp ?? '');
        }
        $extension = getFileExtensionFromString($this->url);
        $customerName = '';
        if ($this->lead->customer) {
            $customerName = $this->lead->customer->company_name;
        } elseif ($this->order && $this->order->customer) {
            $customerName = $this->order->customer->company_name;
        }
        $uid = now()->format('d_m_Y H_i');
        $newName = "{$title} {$customerName}_{$uid}.{$extension}";
        $contract =  $order->customer->generateContract($order->contractorRequisite);

        $name = "Договор  № {$contract->full_number} от " . $contract->created_at->format('d.m.Y')
        . (request()->boolean('withStamp') ? ' (c печатью' : ' (без печати') . ' ' . now($order->company_branch->timezone)->format('d.m.Y H:i') . ')';
        $tmpPdf =  config('app.upload_tmp_dir') . '/' . Str::random('4'). "_{$uid}.{$extension}";
        $newName = str_replace('#', '', $newName);
        $newName = str_replace('"', '', $newName);

        $path = config('app.upload_tmp_dir') . '/' . $newName;

        if(request()->boolean('preview')) {
            return $template->getResult();
        }

        if(\request()->input('preview_data')) {
            $converter = new DocumentConverter();
            $converter->setData(
                $name,
                \request()->input('preview_data')
            );

            $result = $converter->generatePdf();
            Storage::disk()->put($result, Storage::disk('local')->get($result));

            $docs = $order->documents()->where('name', 'like', "%{$name}%")->get();

            $docs->each(function ($item) {
                $item->delete();
            });

            $document = $order->addDocument($name, $result, $subContractorId);

            return Storage::disk()->url($documentPdf['url'] ?? $document['url']);

            //$converter = new OfficeConverter(public_path($path));
            //$converter->convertTo($path);
        }else {
            $template->saveAs(public_path($path));
        }

        Storage::disk()->put($path, Storage::disk('public_disk')->get($path));

        //$docs = $order->documents()->where('name', 'like',"%{$name}%")->get();
//
        //$docs->each(function ($item) {
        //    $item->delete();
        //});

        $document = $order->addDocument($name, $path, $subContractorId, 'contract');

        Storage::disk('public_disk')->rename($path, $tmpPdf);
        /*        Storage::disk()->put($this->url, Storage::disk('tmp')->get($file));

                Storage::disk('tmp')->delete($file);*/
        $pdfDocument = $service->generatePdf(last(explode('/', $tmpPdf)), $tmpPdf, $name, $order, $subContractorId, 'contract');

        Storage::disk('public_disk')->delete($path);

        return Storage::disk()->url($pdfDocument['url']);
        /*$client = new Client(['bae_uri' => config('app.document_handler_service')]);

        $client->post('contract', [
            RequestOptions::JSON => [
                'doc_url' => Storage::disk()->url($this->url),
                'fields'
            ]
        ]);*/
    }

    function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }


    function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    function remove()
    {
        \Storage::disk()->delete($this->url);
        $this->delete();
        return;
    }
}

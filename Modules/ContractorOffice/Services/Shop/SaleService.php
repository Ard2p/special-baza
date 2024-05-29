<?php


namespace Modules\ContractorOffice\Services\Shop;


use App\Machinery;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\ContractorOffice\Entities\Vehicle\Price;
use Modules\ContractorOffice\Entities\Vehicle\Shop\MachinerySale;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderComponent;
use Modules\Orders\Services\OrderDocumentService;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\TemplateProcessor;

class SaleService
{
    private $options;

    public function __construct($options = [])
    {
        $this->options = $options;
    }

    /**
     * @param TemplateProcessor $template
     * @param $order
     * @return $this
     */
    function setRequisites($template, $order)
    {

        $customer = $order->customer;

        $company = $order->company_branch;
        $settings = $company->getSettings();
        if ($settings->documents_head_image) {
            $template->setImageValue('titleImage', Storage::disk()->url($settings->documents_head_image));
        } else {
            $template->setValue('titleImage', '');
        }

        if ($customer->legal_requisites) {
            $template->setValue('customer', "{$customer->legal_requisites->name}, ИНН: {$customer->legal_requisites->inn}, КПП: {$customer->legal_requisites->kpp}, Юридический адрес: {$customer->legal_requisites->register_address}");
        }
        if (!empty($this->options['contact_id'])) {
            $contact = $order->contacts()->find($this->options['contact_id']);
            if($contact) {
                $phone = $contact->phones()->first();
                $phone = $phone ? $phone->phone : '';
            }
            $phone = $phone ?? '';
            $contact = $contact
                ? "{$contact->contact_person}, +{$phone}"
                : "{$order->customer->contact_person}, +{$order->phone}";
            $template->setValue('customer_contact', $contact);

        }

        $contract = $order->contract ?: $order->company_branch->generateSaleContract($order, $order->saleRequest->contract->url, $order->saleRequest->contract->title);

        $template->setValue('contractId', $contract->full_number);

        $template->setValue('contractDate', $contract->created_at->format('d.m.Y'));

        $template->setValue('date', (!empty($this->options['date'])
            ? Carbon::parse($this->options['date'])->format('d.m.Y')
            : now()->format('d.m.Y')));

        $template->setValue('time', (!empty($this->options['time'])
            ? Carbon::parse($this->options['time'])->format('H:i')
            : now()->format('H:i')));


        if ($requisites = $order->company_branch->entity_requisites) {
            $template->setValue('contractor_name', $requisites->name);
            $template->setValue('contractor_address', $requisites->register_address);
            $template->setValue('contractor_phone', $requisites->phone);
        }

        if ($customerRequisites = $customer->legal_requisites) {
            $customerRequisites = [
                'customerActualAddress' => $customerRequisites->actual_address,
                'customerCompanyName' => $customerRequisites->name,
                'customerCompanyNameShort' => $customerRequisites->short_name,
                'customerAddress' => $customerRequisites->register_address,
                'customerEmail' => $customerRequisites->email,
                'customerInn' => $customerRequisites->inn,
                'customerKpp' => $customerRequisites->kpp,
                'customerOgrn' => $customerRequisites->ogrn,
                'customerOkpo' => $customerRequisites->name,
                'customerPhone' => $customerRequisites->phone,
                'customerSignatory' => $customerRequisites->director,

                'customerSignatoryShort' => $customerRequisites->director_short,
                'customerSignatoryGenitive' => $customerRequisites->director_genitive,

                'customerAccount' => $customerRequisites->rs,
                'customerBank' => $customerRequisites->bank,
                'customerCorrespondentAccount' => $customerRequisites->ks,
                'customerBik' => $customerRequisites->bik,

            ];
        }
        if ($contractorRequisites = $order->company_branch->entity_requisites) {
            $contractorRequisites = [
                'contractorActualAddress' => $contractorRequisites->actual_address,
                'contractorCompanyName' => $contractorRequisites->name,
                'contractorCompanyNameShort' => $contractorRequisites->short_name,
                'contractorAddress' => $contractorRequisites->register_address,
                'contractorInn' => $contractorRequisites->inn,
                'contractorKpp' => $contractorRequisites->kpp,
                'contractorEmail' => $contractorRequisites->email,
                'contractorOgrn' => $contractorRequisites->ogrn,
                'contractorOkpo' => $contractorRequisites->name,
                'contractorPhone' => $contractorRequisites->phone,
                'contractorSignatory' => $contractorRequisites->director,

                'contractorSignatoryShort' => $contractorRequisites->director_short,
                'contractorSignatoryGenitive' => $contractorRequisites->director_genitive,

                'contractorAccount' => $contractorRequisites->rs,
                'contractorBank' => $contractorRequisites->bank,
                'contractorCorrespondentAccount' => $contractorRequisites->ks,
                'contractorBik' => $contractorRequisites->bik,

            ];
        }

        $fields = ($contractorRequisites ?? []) + ($customerRequisites ?? []);
        foreach ($fields as $key => $value) {

            $template->setValue($key, $value);

        }

        return $this;
    }

    function generateVehicleCharacteristics(Machinery $vehicle)
    {
        $document_with_table = new PhpWord();
        $section = $document_with_table->addSection();
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '333',
        ]);

        foreach ($vehicle->optional_attributes as $attribute) {
            $table->addRow();
            $table->addCell()->addText($attribute->name);
            $table->addCell()->addText("{$attribute->pivot->value} {$attribute->unit}");
        }

        return $document_with_table;
    }

    function generateApplication(MachinerySale $order)
    {
        $position = $order->operations()->findOrFail($this->options['operation_id']);

        $document_with_table = $this->generateVehicleCharacteristics($position->machine);


        // Create writer to convert document to xml
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($document_with_table, 'Word2007');

// Get all document xml code
        $fullxml = $objWriter->getWriterPart('Document')->write();

// Get only table xml code
        $tablexml = preg_replace('/^[\s\S]*(<w:tbl\b.*<\/w:tbl>).*/', '$1', $fullxml);

        try {

            $settings = $order->company_branch->getSettings();
            $url = $settings->default_machinery_sale_application_url ? Storage::disk()->url($settings->default_machinery_sale_application_url) : public_path('documents/default_application.docx');
            $template = new TemplateProcessor($url);

        }catch (\Exception $exception) {
            logger($exception->getMessage());
            $error = ValidationException::withMessages([
                'errors' => [trans('transbaza_validation.docx_error')]
            ]);

            throw $error;
        }


        $template->setValue('table', '</w:t></w:r></w:p>' . $tablexml . '<w:p><w:r><w:t>');

        $template->setValue('createdAt', $order->created_at->format('d.m.Y'));

        $template->setValue('applicationId', $position->application_id);

        $template->setValue('vehicleName', $position->machine->name);

        $template->setValue('color', $position->machine->colour);
        $template->setValue('vin', $position->machine->vin);
        $template->setValue('year_of_manufacture', $position->machine->yearf);
        $template->setValue('licence_plate', $position->machine->licence_plate);
        $template->setValue('serial_number', $position->machine->serial_number);
        $template->setValue('engine', $position->machine->engine);
        $template->setValue('transmission', $position->machine->transmission);
        $template->setValue('leading_bridge', $position->machine->leading_bridge);



        $template->setValue('category', $position->machine->_type->name);
        $template->setValue('brand', $position->machine->brand ? $position->machine->brand->name : '');
        $template->setValue('model', $position->machine->model ? $position->machine->model->name : '');

        $this->setRequisites($template, $order);
        $this->setComponentData($template, $position);
        $file = "{$position->id}_application.docx";

        $path = config('app.upload_tmp_dir') . "/{$file}";

        $name = trans('contractors/edit.application') ." #{$position->application_id}";

        $docs = $order->documents()->where('name', $name)->get();

        $docs->each(function ($item) {
            $item->delete();
        });

        $template->saveAs(public_path($path));

        Storage::disk()->put($path, Storage::disk('public_disk')->get($path));

        Storage::disk('public_disk')->delete($path);

        $document = $order->addDocument($name, $path);

        return Storage::disk()->url($document['url']);
    }

    /**
     * @param TemplateProcessor $template
     * @param OrderComponent $orderComponent
     * @return OrderDocumentService
     */
    function setComponentData($template, $orderComponent)
    {
        $template->setValue('vehicleMarketPrice', $orderComponent->machine->market_price / 100);
        $template->setValue('currency', (Price::MARKET_PRICE_CURRENCIES[$orderComponent->machine->market_price_currency] ?? ''));
        $template->setValue('vehicleMarketPriceInWords', (new \NumberFormatter("ru", \NumberFormatter::SPELLOUT))->format($orderComponent->machine->market_price / 100));
        $template->setValue('costInWords', (new \NumberFormatter(App::getLocale(), \NumberFormatter::SPELLOUT))->format($orderComponent->cost / 100));
        $template->setValue('vehicleMarketPrice', $orderComponent->cost / 100);

        return $this;
    }


}
<?php


namespace Modules\Dispatcher\Services;


use App\Machinery;
use App\Machines\MachineryModel;
use App\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Services\ContractService;
use Modules\ContractorOffice\Entities\Services\CustomService;
use Modules\ContractorOffice\Entities\System\TariffGrid;
use Modules\ContractorOffice\Entities\Vehicle\DeliveryTariffGrid;
use Modules\ContractorOffice\Entities\Vehicle\Price;
use Modules\Dispatcher\Entities\Lead;
use Modules\Orders\Services\OrderDocumentService;
use PhpOffice\PhpWord\Element\Row;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\Style\Table;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\Writer\Word2007;

class CommercialOfferService
{
    /** @var TemplateProcessor */
    public $template, $companyBranch;

    private $commercialTemplate;

    /** @var Lead */
    private $lead;

    private $fieldsList;
    private $modelsList, $tariffs, $delivery_types, $driver_types, $pay_type, $text = '';

    private $neededDriverTypes = [
        'with_driver',
        'without_driver',
    ];

    private $neededDeliveryTypes = [
        'forward',
        'back',
    ];
    /**
     * @var mixed|string
     */
    private ?string $expiresAt = '';

    public function __construct(
        CompanyBranch $companyBranch,
                      $templateId)
    {
        $this->companyBranch = $companyBranch;
        try {

            $settings = $companyBranch->getSettings();
            $this->commercialTemplate = $companyBranch->commercialOffers()->findOrFail($templateId);
            $url = Storage::disk()->url($this->commercialTemplate->url);
            $this->template = new TemplateProcessor($url);


            if ($settings->documents_head_image) {
                $this->template->setImageValue('titleImage', Storage::disk()->url($settings->documents_head_image));
            } else {
                $this->template->setValue('titleImage', '');
            }


        } catch (\Exception $exception) {


            $error = ValidationException::withMessages([
                'errors' => [trans('transbaza_validation.docx_error')]
            ]);

            throw $error;
        }

    }


    function setAttributes(
        $tariffs = [],
        $delivery_types = [],
        $driver_types = [],
        $pay_type = Price::TYPE_CASHLESS_WITHOUT_VAT,
        $text = '',
    $expiresAt = '')
    {
        $this->tariffs = $tariffs;
        $this->delivery_types = $delivery_types;
        $this->driver_types = $driver_types;
        $this->pay_type = $pay_type;
        $this->text = $text;
        $this->expiresAt = $expiresAt;
    }

    function getOffer()
    {

        $this->setMachineriesList($this->modelsList);
        //  $this->setAboutText();

        $file = uniqid('commercial_offer') . ".docx";
        $path = config('app.upload_tmp_dir') . "/{$file}";
        $this->template->saveAs(public_path($path));
        Storage::disk()->put($path, Storage::disk('public_disk')->get($path));

        if ($this->lead) {
            $service = new OrderDocumentService();

            try {
                $pdfDocument =
                    $service->generatePdf($file, $path, trans('commercial_offer/commercial_offer.commercial_offer'), $this->lead, lovePdf: true);
            } catch (\Exception $exception) {
                logger($exception->getMessage());
            }

            $doc = $this->lead->addDocument(trans('commercial_offer/commercial_offer.commercial_offer'), $path);
        }
        Storage::disk('public_disk')->delete($path);

        return Storage::disk()->url($pdfDocument['url'] ?? $path);
    }

    function setLead(Lead $lead)
    {
        $this->lead = $lead;
    }

    function setContractorRequisites($contractorRequisites)
    {
        if ($contractorRequisites) {
            $contractorRequisites = [
                'contractorCompanyName' => $contractorRequisites->name
                    ?: $contractorRequisites->account_name,
                'contractorAddress'     => $contractorRequisites->register_address,
                'contractorInn'         => $contractorRequisites->inn,
                'contractorKpp'         => $contractorRequisites->kpp,
                'contractorEmail'       => $contractorRequisites->email,
                'contractorOgrn'        => $contractorRequisites->ogrn,
                'contractorOkpo'        => $contractorRequisites->name,
                'contractorPhone'       => $contractorRequisites->phone,
                'contractorSignatory'   => $contractorRequisites->director,

                'contractorSignatoryShort'    => $contractorRequisites->director_short,
                'contractorSignatoryGenitive' => $contractorRequisites->director_genitive,

                'contractorAccount'              => $contractorRequisites->rs,
                'contractorBank'                 => $contractorRequisites->bank,
                'contractorCorrespondentAccount' => $contractorRequisites->ks,
                'contractorBik'                  => $contractorRequisites->bik,

            ];
        }

        $contractorRequisites =
            $contractorRequisites
                ?: [];
        foreach ($contractorRequisites as $key => $value) {

            $this->template->setValue($key, $value);

        }

        return $this;

    }

    function setMachineriesList($models)
    {

        $machineriesList = '';

        $tb_models = MachineryModel::query()->whereIn('id', $models->pluck('id')->toArray())->get();
        foreach ($tb_models as $model) {
            $model->category->localization();

            $machineriesList .= view('CommercialOffer', ['model' => $model])->render();

        }
        $this->setHtml($machineriesList, 'list');
        $width = 300;
        $widthFirst = 50;
        $documentWithServiceTable = new PhpWord();
        $font = new Font();
        $font->setName('Helvetica')->setSize(12);

        $table_style = new Table;
        $table_style->setBorderColor('d1d1d1');
        $table_style->setBorderSize(6);
        $table_style->setUnit(TblWidth::PERCENT);
        $table_style->setWidth(100 * 50);
        $table_style->setLayout(Table::LAYOUT_FIXED);

        $cell_style = [
            'valign' => 'center'
        ];

        $servicesTable = $documentWithServiceTable->addSection()->addTable($table_style);

        $servicesTable->addRow();
        $servicesTable->addCell($widthFirst,$cell_style)->addText('Наименование')->setFontStyle($font)->setBold();

        $documentWithCharacteristicsTable = new PhpWord();
        $characteristicsTable = $documentWithCharacteristicsTable->addSection()->addTable($table_style);

        $characteristicsTable->addRow();
        $characteristicsTable->addCell($widthFirst,$cell_style)->addText('Наименование')->setFontStyle($font)->setBold();
        $services = [];
        $characteristics = [];


        foreach ($models as $model) {

            foreach ($model['services'] as $service) {
                $services[sha1($model['id'] . '_' . $service['key'])] = $service['value'];
            }

            foreach ($model['characteristics'] as $characteristic) {
                $characteristics[sha1($model['id'] . '_' . $characteristic['key'])] = $characteristic['value'];
            }


        }

        foreach ($tb_models as $tbModel) {
            $characteristicsTable->addCell($width,$cell_style)->addText($tbModel->name, [
                'bold' => false,
                'name' => 'Helvetica',
            ],  ['align' => 'center']);
            $servicesTable->addCell($width,$cell_style)->addText($tbModel->name, [
                'bold' => false,
                'name' => 'Helvetica',
            ], ['align' => 'center']);
        }

        foreach ($models as $model) {
            $i = 0;
            foreach ($model['services'] as $service) {
                $servicesTable->addRow();
                $cell = $servicesTable->addCell($width,$cell_style);
                $cell->addText($service['key'], [
                    'bold' => true,
                    'name' => 'Helvetica',
                ]);
                if($i % 2 === 0) {
                    $cell->getStyle()->setBgColor(  'dddddd');
                }
                foreach ($tb_models as $tbModel) {
                    $cell = $servicesTable->addCell($width,$cell_style);

                    if($i % 2 === 0) {
                        $cell->getStyle()->setBgColor(  'dddddd');
                    }
                    $cell->addText($services[sha1($tbModel->id . '_' . $service['key'])],[
                        'bold' => false,
                        'name' => 'Helvetica',
                    ], ['align' => 'center']);
                }
                ++$i;
            }
            $k = 0;
            foreach ($model['characteristics'] as $characteristic) {
                $characteristicsTable->addRow();
                $cell = $characteristicsTable->addCell($width,$cell_style);
                $cell->addText($characteristic['key'], [
                    'bold' => true,
                    'name' => 'Helvetica',
                ]);
                if($k % 2 === 0) {
                    $cell->getStyle()->setBgColor(  'dddddd');
                }
                foreach ($tb_models as $tbModel) {
                    $cell = $characteristicsTable->addCell($width,$cell_style);
                    if($k % 2 === 0) {
                        $cell->getStyle()->setBgColor(  'dddddd');
                    }
                    $cell->addText($characteristics[sha1($tbModel->id . '_' . $characteristic['key'])], [
                        'name' => 'Helvetica',
                        'bold' => false,
                    ], ['align' => 'center']);
                }
                ++$k;
            }
            break;
        }
        $this->template->setComplexBlock("{characteristics}", $characteristicsTable);
        $this->template->setComplexBlock("{services}", $servicesTable);
        $this->template->setValue("name", mb_strtoupper("{$tb_models->first()->category->name}"));

        $this->template->setValue('expiresAt',  Carbon::parse($this->expiresAt)->format('d.m.Y'));
         if ($this->text) {
             $this->template->setValue('text', $this->text);
             //$this->setHtml($this->text, "text");
         }

        if ($this->lead) {
            $serv = new ContractService($this->companyBranch, $this->lead->customer ?? null);

            $value = $serv->getValueByMask($this->commercialTemplate->number);
            $this->template->setValue('number', $value);
        }
        $this->template->setValue('date', now()->format('d.m.Y'));
        // foreach ($models as $model) {
//
//
        //     /** @var MachineryModel $tb_model */
        //     $tb_model = $tb_models->where('id', $model['id'])->first();
        //     //$rentTable = view('CommericalOffer.RentTable', ['vehicle' => $model->machines->first(), 'fmt' => $fmt])->render();
        //     //$attributesTable = view('CommericalOffer.AttributesTable', ['model' => $model, 'fmt' => $fmt])->render();
        //     $imagesTable = view('CommericalOffer.ImagesTable', ['model' => $tb_model])->render();
        //     //$this->setHtml($rentTable, "rentTable{$model->id}");
//
        //     $this->generateModelCharacteristics($characteristicsTable, $model, "characteristics");
        //     $this->generateModelCharacteristics($servicesTable, $model, "services");
//
        //     foreach ($this->neededDriverTypes as $type) {
        //         if (!in_array($type, $this->driver_types)) {
        //             $this->template->setValue("rentTable{$tb_model->id}_{$type}", '');
        //         } else {
        //             //       $this->generateRentalTable($model, $table, $type);
        //         }
//
        //     }
        //     foreach ($this->neededDeliveryTypes as $type) {
        //         $k = "deliveryTable{$tb_model->id}_{$type}";
//
        //         if (!in_array($type, $this->delivery_types)) {
        //             $this->template->setValue($k, '');
        //         } else {
        //             $this->generateDeliveryTable($model, $k, $type);
        //         }
//
        //     }
//
        //     //$this->generateServicesTable($tb_model, "servicesTable{$tb_model->id}");
//
//
        //     $this->template->setComplexBlock("{characteristics}", $characteristicsTable);
        //     $this->template->setComplexBlock("{services}", $servicesTable);
        //     $this->template->setValue("name", "{$tb_model->category->name}");
        //     // $this->setHtml($attributesTable, "attributesTable{$model->id}");
        //    // $this->setHtml($imagesTable, "imagesTable{$tb_model->id}");
        //     if ($tb_model->images) {
        //         foreach ($tb_model->images as $k => $image) {
        //             $this->template->setImageValue("{$tb_model->id}_{$k}", ['path' => Storage::disk()->url($image), 'width' => 400, 'height' => 300]);
        //         }
//
        //     }
        //     break;
        // }
        // if ($this->text) {
        //     $this->template->setValue('text', $this->text);
        //     //$this->setHtml($this->text, "text");
        // }
        // if ($this->lead) {
        //     $service = new OrderDocumentService();
        //     $service->setRequisites($this->template, $this->lead);
        // }
        return $this;
    }

    function generateModelCharacteristics(
        $table,
        $tmodel,
        $key)
    {
        /*    if ($model->characteristics->isEmpty()) {
                $this->template->setValue($key, '');
                return;
            }*/


        foreach ($tmodel[$key] as $attribute) {
            $table->addRow();
            $table->addCell()->addText($attribute['key'], ['size' => 10]);
            $table->addCell()->addText($attribute['value'], ['size' => 10]);
        }

        // $this->generateServicesTable($model, $table);
        // $this->generateRentalTable($tmodel, $table, $type);


        if ($this->lead) {
            $serv = new ContractService($this->companyBranch, $this->lead->customer ?? null);

            $value = $serv->getValueByMask($this->commercialTemplate->number);
            $this->template->setValue('number', $value);
        }


        return $table;
    }

    function generateRentalTable(
        $model,
        $table,
        $type = TariffGrid::WITH_DRIVER)
    {
        /*        $document_with_table = new PhpWord();
                $section = $document_with_table->addSection();
                $table = $section->addTable([
                    'width' => 5000,
                    'borderSize' => 6,
                    'borderColor' => 'd1d1d1',
                    'unit' => TblWidth::PERCENT,
                ]);*/

        $model = MachineryModel::query()->findOrFail($model['id']);
        /** @var TariffGrid $grid */
        $grids =
            TariffGrid::query()->whereIn('unit_compare_id', $this->tariffs)->whereHas('machinery', function ($q) use
            (
                $model
            ) {
                $q->where('model_id', $model['id']);
            })->where('type', $type)->get()->unique('unit_compare_id');


        $fmt =
            numfmt_create($this->companyBranch->company->domain->options['default_locale'], \NumberFormatter::CURRENCY);

        //  $table->addRow();
        //  $table->addCell()->addText($type === TariffGrid::WITH_DRIVER ? trans('vehicle/search_card.rent_with_driver') : trans('vehicle/search_card.rent_without_driver'), ['size' => 16], ['align' => 'center']);

//        $table->addCell()->addText('', ['size' => 14]);
//        $table->addCell()->addText('', ['size' => 14]);
        foreach ($grids as $grid) {
            $pr = $grid->gridPrices->where('price_type', $this->pay_type)->first();

            $value =
                numfmt_format_currency($fmt, $pr->price / 100 / $grid->unitCompare->amount, $grid->machinery->currency);

            $table->addRow();
            //  $table->addCell()->addText($grid->unitCompare->name, ['size' => 13]);
            $table->addCell()->addText('Стоимость аренды', ['size' => 10]);
            $table->addCell()->addText($value, ['size' => 10]);
        }

        //   $this->template->setValue($key, $this->getDocXml($document_with_table));

        return $this;
    }

    function generateDeliveryTable(
        $model,
        $key,
        $type = 'forward')
    {
        $document_with_table = new PhpWord();
        $section = $document_with_table->addSection();
        $table = $section->addTable([
            'width'       => 5000,
            'borderSize'  => 6,
            'borderColor' => 'd1d1d1',
            'unit'        => TblWidth::PERCENT,
        ]);
        $fmt =
            numfmt_create($this->companyBranch->company->domain->options['default_locale'], \NumberFormatter::CURRENCY);

        $deliveryGrids =
            DeliveryTariffGrid::query()->whereHas('machinery', function ($q) use
            (
                $model
            ) {
                $q->where('model_id', $model['id']);
            })->where('type', $type)->get()->unique('machinery_id');

        $fCount = $deliveryGrids->count();

        $table->addRow();
        $table->addCell()->addText($type === 'forward'
            ? ''
            : trans('transbaza_register_order.back_delivery'), ['size' => 16, 'bgColor' => 'dbdbdb'], ['align' => 'center']);
        $table->addCell()->addText('', ['size' => 14]);
        $table->addCell()->addText('', ['size' => 14]);

        $table->addRow();
        $table->addCell()->addText(trans('transbaza_machine_edit.before'), ['size' => 14]);
        $table->addCell()->addText(trans('vehicle/search_card.price'), ['size' => 14]);

        /** @var DeliveryTariffGrid $deliveryGrid */
        foreach ($deliveryGrids->where('type', 'forward') as $deliveryGrid) {


            $pr = $deliveryGrid->grid_prices->where('price_type', $this->pay_type)->first();

            $value = numfmt_format_currency($fmt, $pr->price / 100, $deliveryGrid->machinery->currency);
            //  logger($value);
            $table->addRow();
            $table->addCell()->addText($deliveryGrid->min . ' ' . trans('units.km'), ['size' => 14]);
            $table->addCell()->addText($value . ($deliveryGrid->is_fixed
                    ? ''
                    : "/" . trans('units.km')), ['size' => 14]);
        }


        $this->template->setValue($key, !$fCount
            ? ''
            : $this->getDocXml($document_with_table));

        return $this;
    }

    function generateServicesTable(
        $model,
        $table)
    {
        /*        $document_with_table = new PhpWord();
                $section = $document_with_table->addSection();
                $table = $section->addTable([
                    'width' => 5000,
                    'borderSize' => 6,
                    'borderColor' => 'd1d1d1',
                    'unit' => TblWidth::PERCENT,
                ]);*/
        $fmt =
            numfmt_create($this->companyBranch->company->domain->options['default_locale'], \NumberFormatter::CURRENCY);

        $services =
            CustomService::query()->forBranch()->whereHas('categories', function ($q) use
            (
                $model
            ) {
                $q->where('types.id', $model->category_id);
            })->get();

        $fCount = $services->count();

//        $table->addRow();
//        $table->addCell()->addText('', ['size' => 13], ['align' => 'center']);
//        $table->addCell()->addText('', ['size' => 13]);

        foreach ($services as $service) {

            $value = numfmt_format_currency($fmt, $service->price / 100, 'rub');

            $table->addRow();
            $table->addCell()->addText($service->name, ['size' => 13]);
            $table->addCell()->addText($value, ['size' => 13]);
        }


        //  $this->template->setValue($key, !$fCount ? '' : $this->getDocXml($document_with_table));

        return $this;
    }

    private function getDocXml(
        $doc,
        $in = false)
    {
        try {
            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($doc, 'Word2007');
            $fullxml = $objWriter->getWriterPart('Document')->write();

            preg_match('/<w:tbl>(.*?)<\/w:tbl>/s', $fullxml, $match);


            return '</w:t></w:r></w:p>' . ($in
                    ? $match[1]
                    : $match[0]) . '<w:p><w:r><w:t>';
        } catch (\Exception $exception) {
            \Log::error($exception->getMessage());
            return '';
        }
    }

    private function setHtml(
        $html,
        $key,
        $fullHtml = false)
    {

        $document = new PhpWord();
        $section = $document->addSection();

        Html::addHtml($section, str_replace(['<br>', '<br/>'], '', $html), $fullHtml);

        /** @var Word2007 $objWriter */
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($document, 'Word2007');
        $fullxml = $objWriter->getWriterPart('Document')->write();

        preg_match('/<w:body>(.*?)<\/w:body>/s', $fullxml, $match);

        //$fullxml = preg_replace('/^[\s\S]*(<w:body\b.*<\/w:body>).*/', '$1', $fullxml);
        $fullxml = '</w:t></w:r></w:p>' . $match[1] . '<w:p><w:r><w:t>';
        //  logger($fullxml);
        $this->template->setValue($key, $fullxml);

        return $this;
    }

    function setAboutText()
    {
        $company = $this->companyBranch->company;
        if (!$company->getSettings()->about_page_content) {
            return $this;
        }


        $client = new Client();
        $response = $client->post('https://mailbox-service.kinosk.com/inline-converter', [
            'http_errors'        => false,
            RequestOptions::JSON => [
                'html' => $company->settings->about_page_content,
            ]
        ])->getBody()->getContents();

        $this->setHtml($response, 'aboutText', true);


        return $this;

    }

    /**
     * @param $modelsList
     * @return CommercialOfferService
     */
    public function setModelsList($modelsList): self
    {
        $this->modelsList = $modelsList
            ? collect($modelsList)
            : MachineryModel::query()->whereHas('machines', function ($q) {
                $q->forBranch();
            })->get();

        return $this;
    }

    function setFieldsList($fieldsList)
    {
        $this->fieldsList = collect($fieldsList);

        return $this;
    }

    function setManager(User $user)
    {
        $this->template->setValue('managerName', $user->contact_person);
        $this->template->setValue('managerPhone', $user->phone);
        $this->template->setValue('managerEmail', $user->email);

        return $this;
    }

}


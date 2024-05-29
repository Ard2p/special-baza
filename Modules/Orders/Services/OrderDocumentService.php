<?php

namespace Modules\Orders\Services;

use App\Machinery;
use App\Machines\MachineryModel;
use App\Service\RequestBranch;
use App\User\EntityRequisite;
use App\User\IndividualRequisite;
use Carbon\Carbon;
use File;
use http\Env\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Ilovepdf\Ilovepdf;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Entities\Company\DocumentsPack;
use Modules\CompanyOffice\Services\ContractService;
use Modules\ContractorOffice\Entities\System\TariffGrid;
use Modules\ContractorOffice\Entities\Vehicle\Price;
use Modules\ContractorOffice\Services\Tariffs\TimeCalculation;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\Directories\Contractor;
use Modules\Dispatcher\Entities\DispatcherInvoice;
use Modules\Dispatcher\Entities\Lead;
use Modules\Integrations\Services\OneC\OneCService;
use Modules\Orders\Entities\MachinerySetsOrder;
use Modules\Orders\Entities\MachineryStamp;
use Modules\Orders\Entities\Order;
use Modules\Orders\Entities\OrderComponent;
use Modules\Orders\Entities\OrderComponentIdle;
use Modules\Orders\Entities\OrderComponentService;
use Modules\Orders\Entities\Payments\InvoicePay;
use Modules\Orders\Entities\Service\ServiceCenter;
use Modules\Orders\Entities\UdpRegistry;
use Modules\PartsWarehouse\Entities\Posting;
use Modules\PartsWarehouse\Entities\Shop\Parts\PartsSale;
use Modules\PartsWarehouse\Entities\Stock\Item;
use Modules\PartsWarehouse\Entities\Warehouse\Part;
use Modules\PartsWarehouse\Entities\Warehouse\WarehousePartSet;
use NcJoes\OfficeConverter\OfficeConverter;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Style;
use PhpOffice\PhpWord\Style\Table as TableStyle;
use PhpOffice\PhpWord\Element\TextBox;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\TemplateProcessor;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class OrderDocumentService
{

    private $options;

    private ?DocumentsPack $documentsPack = null;
    private CompanyBranch $companyBranch;
    private bool $withStamp;

    public function __construct($options = [], $companyBranch = null)
    {
        $this->options = $options;
        $this->withStamp = filter_var($options['with_stamp'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->companyBranch = $companyBranch ?? app(RequestBranch::class)->companyBranch;

        if (!empty($options['documents_pack_id'])) {
            $this->documentsPack = DocumentsPack::query()->forBranch()->find($options['documents_pack_id']);
        }
    }

    /**
     * @param TemplateProcessor $template
     * @param Order $order
     * @return $this
     */
    function setRequisites(
        $template,
        $instance
    )
    {

        $subContractor = null;
        $subContractorId = $this->options['subContractorId'] ?? null;

        if ($subContractorId) {
            $subContractor = Contractor::query()->forBranch()->find($subContractorId);
        }
        $customer = $subContractorId
            ? $instance->company_branch
            : ($instance instanceof Order
                ? $instance->lead->customer
                : $instance->customer);


        $company = $instance->company_branch;
        $settings = $company->getSettings();
        // if ($settings->documents_head_image) {
        //     $template->setImageValue('titleImage', Storage::disk()->url($settings->documents_head_image));
        // } else {
        //     $template->setValue('titleImage', '');
        // }
        $template->setValue('titleImage', '');
        if ($customer->legal_requisites) {
            $template->setValue('customer',
                "{$customer->legal_requisites->name}, ИНН: {$customer->legal_requisites->inn}, КПП: {$customer->legal_requisites->kpp}, Юридический адрес: {$customer->legal_requisites->register_address}");
        }
        if (!empty($this->options['contact_id'])) {
            $contact = $instance->contacts()->find($this->options['contact_id']);
        } else {
            $contact = $instance->contacts()->first();
        }

        $template->setValue('currency', $instance->currency->short ?? '');
        if ($contact) {
            $phone = $contact->phones()->first();
            $email = $contact->emails()->first();
            $phone =
                $phone
                    ? $phone->phone
                    : '';
            $email =
                $email
                    ? $email->email
                    : '';
        }
        if (!empty($instance->contacts)) {

            $template->setValue('customerContactPhones', $instance->contacts->flatMap(fn($contact) => $contact->phones)->pluck('phone')->join(', '));

        }
        $phone = $phone ?? '';
        $email = $email ?? '';

        $contact_v = $contact
            ? "{$contact->full_name}, +{$phone}"
            : "{$instance->customer->contact_person}, +" . ($instance instanceof Order
                ? $instance->lead->phone
                : $instance->phone);

        $template->setValue('customer_contact', $contact_v);
        $template->setValue('customerContactPhone', $phone);
        $template->setValue('customerContactPerson', ($contact
            ? $contact->full_name
            : ($instance instanceof Order
                ? $instance->lead->customer->contact_person
                : $instance->customer->contact_person)));
        $template->setValue('customerContactEmail', $email);

        $customerInstance =
            $subContractor
                ?: $instance->customer;
        if (!($customerInstance instanceof CompanyBranch)) {
            $contract =
                $instance instanceof ServiceCenter
                    ? $instance->contract ?? $customerInstance->generateServiceContract($instance->contractorRequisite)
                    : $instance->contract ?? $customerInstance->generateContract($instance->contractorRequisite);

            $template->setValue('contractId', $contract->full_number);
            $template->setValue('contractDate', $contract->created_at->format('d.m.Y'));
        }


        $template->setValue('address', "{$instance->address}");
        $template->setValue('externalId', "{$instance->externalId}");
        $template->setValue('date', (!empty($this->options['date'])
            ? Carbon::parse($this->options['date'])->format('d.m.Y')
            : now()->format('d.m.Y')));

        $template->setValue('time', (!empty($this->options['time'])
            ? Carbon::parse($this->options['time'])->format('H:i')
            : now()->format('H:i')));

        $template->setValue('pageBreak', '<w:p><w:r><w:br w:type="page"/></w:r></w:p>');

        if ($requisites =
            $subContractor
                ? $subContractor->requisites
                : $instance->contractorRequisite) {
            $template->setValue('contractor_name', $requisites->name);
            $template->setValue('contractor_address', $requisites->register_address);
            $template->setValue('contractor_phone', $requisites->phone);
        }

        if ($instance->manager) {
            $template->setValue('managerName', $instance->manager->contact_person);
            $template->setValue('managerPhone', $instance->manager->phone);
            $template->setValue('managerEmail', $instance->manager->email);

        }

        $customerLegalRequisites = null;
        $customerIndividualRequisites = null;
        if ($subContractorId) {
            $customerRequisites = $instance->contractorRequisite;
            if ($customerRequisites instanceof IndividualRequisite) {
                $customerIndividualRequisites = $customerRequisites;
            } else {
                $customerLegalRequisites = $customerRequisites;
            }
        } else {

            $customerLegalRequisites =
                $customerInstance instanceof CompanyBranch
                    ? $customerInstance->entity_requisites->first()
                    : $customer->legal_requisites;
            $customerIndividualRequisites =
                $customerInstance instanceof CompanyBranch
                    ? $customerInstance->individual_requisites->first()
                    : $customer->individual_requisites;
        }
        $signatory = IndividualRequisite::query()->find($this->options['signatory_id'] ?? null);
        if ($signatory) {
            $phone = $signatory->phones()->first();
            $email = $signatory->emails()->first();
            $signatoryPhone =
                $phone
                    ? $phone->phone
                    : '';
            $signatoryEmail =
                $email
                    ? $email->email
                    : '';

        }
        $template->setValue('customerSignatoryPhone', $signatoryPhone ?? '');
        $template->setValue('customerSignatoryEmail', $signatoryEmail ?? '');
        if ($customerLegalRequisites) {

            $customerRequisites = [
                'customerActualAddress' => $customerLegalRequisites->actual_address,
                'customerLegalAddress' => $customerLegalRequisites->legal_address,
                'customerCompanyName' => $customerLegalRequisites->name
                    ?: $customerLegalRequisites->account_name,
                'customerCompanyNameShort' => $customerLegalRequisites->short_name,
                'customerAddress' => $customerLegalRequisites->register_address
                    ?: $customerLegalRequisites->legal_address,
                'customerEmail' => $customerLegalRequisites->email,
                'customerInn' => $customerLegalRequisites->inn
                    ?: $customerLegalRequisites->account,
                'customerKpp' => $customerLegalRequisites->kpp,
                'customerOgrn' => $customerLegalRequisites->ogrn,
                'customerOkpo' => $customerLegalRequisites->okpo,
                'customerPhone' => $customerLegalRequisites->phone,
                'customerSignatory' => $signatory->full_name ?? $customerLegalRequisites->director,

                'customerSignatoryShort' => $customerLegalRequisites->director_short,
                'customerSignatoryGenitive' => $customerLegalRequisites->director_genitive,
                'customerSignatoryPosition' => $customerLegalRequisites->director_position,
                'customerSignatoryPositionGenitive' => $customerLegalRequisites->director_position_genitive,
                'customerSignatoryStatute' => $customerLegalRequisites->charter,

                'customerAccount' => $customerLegalRequisites->rs,
                'customerBank' => $customerLegalRequisites->bank,
                'customerCorrespondentAccount' => $customerLegalRequisites->ks,
                'customerBik' => $customerLegalRequisites->bik,

            ];
        } else {
            if ($customerIndividualRequisites) {


                $customerRequisites = [

                    'customerActualAddress' => $customerIndividualRequisites->register_address,
                    'customerCompanyName' => $customerIndividualRequisites->full_name,
                    'customerCompanyNameShort' => $customerIndividualRequisites->short_name,
                    'customerAddress' => $customerIndividualRequisites->register_address
                        ?: $customerIndividualRequisites->legal_address,
                    'customerEmail' => $customer->email,
                    'customerInn' => $customerIndividualRequisites->inn,
                    'customerKpp' => $customerIndividualRequisites->kp,
                    'customerOgrn' => $customerIndividualRequisites->ogrn,
                    'customerOkpo' => $customerIndividualRequisites->okpo,
                    'customerPhone' => $customer->phone,
                    'customerSignatory' => $customerIndividualRequisites->signatory_name,

                    'customerSignatoryShort' => $customerIndividualRequisites->signatory_short,
                    'customerSignatoryGenitive' => $customerIndividualRequisites->signatory_genitive,

                    'customerAccount' => $customerIndividualRequisites->rs,
                    'customerBank' => $customerIndividualRequisites->bank,
                    'customerCorrespondentAccount' => $customerIndividualRequisites->ks,
                    'customerBik' => $customerIndividualRequisites->bik,
                    'customerFirstName' => $customerIndividualRequisites->firstname,
                    'customerPassportIssuedBy' => $customerIndividualRequisites->issued_by,
                    'customerBirthDate' => $customerIndividualRequisites->birth_date
                        ? Carbon::parse($customerIndividualRequisites->birth_date)->format('d.m.Y')
                        : '',
                    'customerMiddleName' => $customerIndividualRequisites->middlename,
                    'customerSurname' => $customerIndividualRequisites->surname,
                    'customerPassportNumber' => $customerIndividualRequisites->passport_number,
                    'customerPassportDate' => $customerIndividualRequisites->passport_date,
                    'customerOgrnip' => $customerIndividualRequisites->ogrnip,
                    'customerOgrnipDate' => $customerIndividualRequisites->ogrnip_date,

                ];
            }
        }
        if ($customerLegalRequisites && $customerLegalRequisites->bankRequisites && $customerLegalRequisites->bankRequisites->count()) {
            $customerBank = $customerLegalRequisites->bankRequisites->first();

            $customerRequisites['customerBank'] = $customerBank->name;
            $customerRequisites['customerCorrespondentAccount'] = $customerBank->ks;
            $customerRequisites['customerBik'] = $customerBank->bik;
            $customerRequisites['customerAccount'] = $customerBank->rs;
        }
        if ($contractorRequisites =
            $subContractor
                ? $subContractor->requisites
                : $instance->contractorRequisite) {
            $contractorRequisitesArray = [
                'contractorVat' => $contractorRequisites->vat_system === Price::TYPE_CASHLESS_VAT ? 20 : 0,
                'contractorActualAddress' => $contractorRequisites->actual_address,
                'contractorLegalAddress' => $contractorRequisites->legal_address,
                'contractorCompanyName' => $contractorRequisites->name
                    ?: $contractorRequisites->account_name,
                'contractorCompanyNameShort' => $contractorRequisites->short_name,
                'contractorAddress' => $contractorRequisites->register_address,
                'contractorInn' => $contractorRequisites->inn
                    ?: $contractorRequisites->account,
                'contractorKpp' => $contractorRequisites->kpp,
                'contractorEmail' => $contractorRequisites->email,
                'contractorOgrn' => $contractorRequisites->ogrn,
                'contractorOgrnip' => $contractorRequisites->ogrnip,
                'contractorOgrnipDate' => $contractorRequisites->ogrnip_date,
                'contractorOkpo' => $contractorRequisites->okpo,
                'contractorPhone' => $contractorRequisites->phone,
                'contractorSignatory' => $contractorRequisites->director,

                'contractorSignatoryShort' => $contractorRequisites->director_short
                    ?: $contractorRequisites->signatory_short,
                'contractorSignatoryGenitive' => $contractorRequisites->director_genitive
                    ?: $contractorRequisites->signatory_genitive,

                'contractorSignatoryPosition' => $contractorRequisites->director_position,
                'contractorSignatoryPositionGenitive' => $contractorRequisites->director_position_genitive,
                'contractorSignatoryStatute' => $contractorRequisites->charter,
                'contractorSignatoryName' => $contractorRequisites->signatory_name,

                'contractorBank' => $contractorRequisites->bank,
                'contractorCorrespondentAccount' => $contractorRequisites->ks,
                'contractorBik' => $contractorRequisites->bik,
                'contractorAccount' => $contractorRequisites->rs,

                'contractorFirstName' => $contractorRequisites->firstname,
                'contractorMiddleName' => $contractorRequisites->middlename,
                'contractorSurname' => $contractorRequisites->surname,
                'contractorPassportNumber' => $contractorRequisites->passport_number,
                'contractorPassportDate' => $contractorRequisites->passport_date,
            ];
            if ($instance->bankRequisite) {
                $bank = $instance->bankRequisite;
            } elseif ($contractorRequisites->bankRequisite) {
                $bank = $contractorRequisites->bankRequisite;
            } else {
                if ($contractorRequisites->bankRequisites && $contractorRequisites->bankRequisites->count()) {
                    $bank = $contractorRequisites->bankRequisites->first();
                }
            }
            if ($bank ?? false) {
                $contractorRequisitesArray['contractorBank'] = $bank->name;
                $contractorRequisitesArray['contractorCorrespondentAccount'] = $bank->ks;
                $contractorRequisitesArray['contractorBik'] = $bank->bik;
                $contractorRequisitesArray['contractorAccount'] = $bank->rs;
            }
        }

        $fields = ($contractorRequisitesArray ?? []) + ($customerRequisites ?? []);
        foreach ($fields as $key => $value) {

            $template->setValue($key, $value);

        }
        if ($instance->principal) {
            $this->setPrincipalRequesites($template, $instance->principal?->person);
        }

        return $this;
    }

    function setPrincipalRequesites(
        $template,
        $requisites)
    {
        if (!$requisites) {
            return $this;
        }
        $requisitesArray = [
            'principalActualAddress' => $requisites->actual_address,
            'principalLegalAddress' => $requisites->legal_address,
            'principalCompanyName' => $requisites->name
                ?: $requisites->account_name,
            'principalCompanyNameShort' => $requisites->short_name,
            'principalAddress' => $requisites->register_address,
            'principalInn' => $requisites->inn
                ?: $requisites->account,
            'principalKpp' => $requisites->kpp,
            'principalEmail' => $requisites->email,
            'principalOgrn' => $requisites->ogrn,
            'principalOgrnip' => $requisites->ogrnip,
            'principalOgrnipDate' => $requisites->ogrnip_date,
            'principalOkpo' => $requisites->okpo,
            'principalPhone' => $requisites->phone,
            'principalSignatory' => $requisites->director,

            'principalSignatoryShort' => $requisites->director_short
                ?: $requisites->signatory_short,
            'principalSignatoryGenitive' => $requisites->director_genitive
                ?: $requisites->signatory_genitive,

            'principalSignatoryPosition' => $requisites->director_position,
            'principalSignatoryPositionGenitive' => $requisites->director_position_genitive,
            'principalSignatoryStatute' => $requisites->charter,
            'principalSignatoryName' => $requisites->signatory_name,

            'principalBank' => $requisites->bank,
            'principalCorrespondentAccount' => $requisites->ks,
            'principalBik' => $requisites->bik,
            'principalAccount' => $requisites->rs,

            'principalFirstName' => $requisites->firstname,
            'principalMiddleName' => $requisites->middlename,
            'principalSurname' => $requisites->surname,
            'principalPassportNumber' => $requisites->passport_number,
            'principalPassportIssuedBy' => $requisites->issued_by,
            'principalPassportDate' => $requisites->passport_date,
            'principalBirthDate' => $requisites->birth_date
                ? Carbon::parse($requisites->birth_date)->format('d.m.Y')
                : '',
            'principalBirthPlace' => $requisites->birth_place
        ];
        foreach ($requisitesArray as $key => $value) {

            $template->setValue($key, $value);

        }

        return $this;
    }

    function formOrderCash(
        DispatcherInvoice $invoice,
        InvoicePay        $pay,
                          $stamp = false
    )
    {
        $order = $invoice->owner;
        /* if ($pay->type !== 'cash')
             return '';*/
        $templateName =
            $stamp
                ? 'default_cash_order_stamp'
                : 'default_cash_order';
        try {


            $documentsPack =
                $this->documentsPack
                    ?: $order->lead->documentsPack;
            if (!$documentsPack->default_cash_order) {
                return;
            }
            $url =
                ($documentsPack && $documentsPack->{$templateName})
                    ? Storage::disk()->url($documentsPack->{$templateName})
                    : public_path('documents/return_act.docx');
            $template = new TemplateProcessor($url);

        } catch (\Exception $exception) {
            logger($exception->getMessage() . ' ' . $exception->getTraceAsString());
            $error = ValidationException::withMessages([
                'errors' => [trans('transbaza_validation.docx_error')]
            ]);

            throw $error;
        }

        if ($invoice->owner instanceof Order) {
            /** @var OrderComponent $component */
            $component = $invoice->owner->components->first();
            if ($component->machineryBase && $component->machineryBase->companyWorker) {
                $template->setValue('companyWorker', $component->machineryBase->companyWorker->name);

            }
        }

        $template->setValue('paySum', number_format($pay->sum / 100, 2, ',', ' '));
        $template->setValue('paySumInWords',
            (new \NumberFormatter(App::getLocale(), \NumberFormatter::SPELLOUT))->format(round($pay->sum / 100, 0,
                PHP_ROUND_HALF_DOWN)));

        $this->setRequisites($template, $order);

        $template->setValue('d', $pay->date->format('d'));
        $template->setValue('m', $pay->date->format('m'));
        $template->setValue('y', $pay->date->format('Y'));
        $template->setValue('h', $pay->date->format('H'));
        $template->setValue('i', $pay->date->format('i'));

        return $this->getUrlForDocument($template, $order, 'Кассовый ордер ' . $pay->id);


    }

    function formInvoice(
        DispatcherInvoice $invoice,
                          $withStamp = true,
                          $templateName = null,
    )
    {

        $counter = 0;
        $deliveryCost = 0;
        $isPaidService = request('service_type') === 'paid';
        if ($invoice->use_onec_naming && $invoice->company_branch->OneCConnection && $invoice->onec) {
            $service = new OneCService($invoice->company_branch);
        } else {
            $service = null;
        }

        $isOrderInvoice = $invoice->owner instanceof Order;

        $items =
            $isOrderInvoice
                ? $invoice->orderComponents
                : $invoice->leadPositions;
        $invoice->load('receivingFromDonor');
        $donorSum = $invoice->receivingFromDonor->sum('sum');
        if ($donorSum > 0) {
            $totalPositionsCounter = $invoice->orderComponents()->wherePivot('delivery_cost', '>', 0)->count()
                + $invoice->orderComponents()->wherePivot('return_delivery', '>', 0)->count()
                + $invoice->orderComponents()->count();

            $partial = $invoice->sum / $totalPositionsCounter;
        }
        $result = [];
        $invoicePositions =
            $invoice->positions->count()
                ? $invoice->positions
                : $invoice->leadPositions;
        $itemsCount = 0;
        $k = 0;
        $componentsSum = collect();
        foreach ($invoicePositions as $i => $position) {
            if ($position->owner && !$position->owner->worker instanceof WarehousePartSet) {
                $itemsCount++;
            }
            if (!$position->cost_per_unit && $isPaidService) {
                continue;
            }
            $n = $position->name;

            if ($service && $isOrderInvoice) {

                $info = $service->getEntityInfo(Machinery::class, $position->vendor_code);

                //logger($info);
                if ($info) {
                    $n = xml_escape($info['НаименованиеПолное']);
                }
            }
            if ($isOrderInvoice) {

                /*$dateFrom = Carbon::parse($component->pivot->date_from)->format('d.m.Y');
                $dateTo = Carbon::parse($component->pivot->date_to)->format('d.m.Y');*/
                //  $n .= $position->description; //. ($invoice->partial_percent < 100 && $invoice->type === 'custom_calculation' ?  trans('transbaza_finance.prepayment_percent', ['percent' => $invoice->partial_percent]) : '');
            }

            if ($position->owner instanceof Part) {
                $n .= " ($position->amount $position->unit)";
            }

            /*if ($isOrderInvoice) {
                $costPerUnit = $component->pivot->cost_per_unit;
                $orderType = $component->pivot->order_type;
                $orderDuration = $component->pivot->order_duration;
                if ($component->pivot->delivery_cost || $component->pivot->return_delivery) {

                    if ($component->pivot->delivery_cost) {
                        ++$counter;
                        $deliveryCost += $partial ?? $component->pivot->delivery_cost;
                    }
                    if ($component->pivot->return_delivery) {
                        ++$counter;
                        $deliveryCost += $partial ?? $component->pivot->return_delivery;
                    }
                }
            } else {
                $costPerUnit = $component->cost_per_unit;
                $orderType = $component->order_type;
                $orderDuration = $component->order_duration;
                if ($component->delivery_cost || $component->return_delivery) {

                    if ($component->delivery_cost) {
                        ++$counter;
                        $deliveryCost += $component->delivery_cost;
                    }
                    if ($component->return_delivery) {
                        ++$counter;
                        $deliveryCost += $component->return_delivery;
                    }
                }
            }*/

            /*if(!empty($partial)) {
                $costPerUnit = $partial / $orderDuration;
            }*/
            /*   if(!$costPerUnit) {
                   continue;
               }*/
            $costPerUnit = $position->cost_per_unit;
            $orderDuration =
                $position->amount
                    ?: $position->order_duration;
            if ($position->unit) {
                $unit = $position->unit;
            } else {
                $unit = $position->order_type === 'shift' ? 'Смена' : 'Час';
            }

            if (($position->owner && $position->owner instanceof Part)) {
                $orderDuration = $position->part_duration ?? $position->amount;
                $unit = 'Смен';
                $cPU = number_format($costPerUnit * $position->amount / 100, 2, ',', ' ');
                $am = number_format(($partial ?? ($costPerUnit * $position->amount * $orderDuration)) / 100, 2, ',', ' ');
                $componentsSum->push($partial ?? ($costPerUnit * $position->amount * $orderDuration));
            } else {
                $cPU = number_format($costPerUnit / 100, 2, ',', ' ');
                $am = number_format(($partial ?? ($costPerUnit * $orderDuration)) / 100, 2, ',', ' ');
                $componentsSum->push($partial ?? ($costPerUnit * $orderDuration));
            }
            $result[] = [
                'num' => ++$k,
                'name' => $n,
                'vendorCode' => $position->owner->worker->board_number ?? $position->vendor_code,
                'vehicleName' => $position->owner->worker->name ?? null,
                'description' => $position->description,
                // 'vehicleNameWithAddress' => ($position->owner->worker->name ?? null) . " {$position->order->address}",
                'duration' => $orderDuration,
                'unit' => $unit,
                'costPerUnit' => ($position->owner && $position->owner->worker instanceof WarehousePartSet)
                    ? ''
                    : $cPU,
                'amount' => ($position->owner && $position->owner->worker instanceof WarehousePartSet)
                    ? ''
                    : $am
            ];
        }

        $this->addPayed( $invoice, $result);
        $result = $this->addDiscount($invoicePositions[0], $invoice, $result);

        $result = $this->addVat($invoicePositions[0], $invoice, $result);

        try {
            $name = $templateName ?: request('template');

            if ($invoice->owner instanceof PartsSale) {
                $name = 'default_parts_sale_invoice';
            }

            if ($invoice->owner instanceof ServiceCenter) {
                $name = 'default_service_center_invoice';
            }
            $nameHtml = "${name}_html";
            $documentsPack =
                $isOrderInvoice
                    ? $invoice->owner->lead->documentsPack
                    : $invoice->owner->documentsPack;
            $url =
                ($documentsPack && $documentsPack->{$name})
                    ? Storage::disk()->url($documentsPack->{$name})
                    : public_path('documents/return_act.docx');
            $template = request()->boolean('preview')
                ? new HtmlTemplateProcessor($documentsPack->{$nameHtml})
                : new TemplateProcessor($url);

        } catch (\Exception $exception) {
            logger($exception->getMessage(),[
                $exception->getFile(),
                $exception->getLine(),
                $exception->getTrace(),
            ]);
            $error = ValidationException::withMessages([
                'errors' => [trans('transbaza_validation.docx_error')]
            ]);

            throw $error;
        }
        if ($isOrderInvoice) {
            $template->setValue('dateFrom', $invoice->owner->created_at?->format('d.m.Y'));
            $template->setValue('externalId', $invoice->number);

        }
        $template->setValue('rowsCount', count($result));
        $template->setValue('invoiceNumber', $invoice->number);
        $template->setValue('invoiceDate', $invoice->created_at->format('d.m.Y'));
        //$template->setComplexBlock('{table}', $table);
        $template->setValue('itemsCount', $k);
        $totalSum = $this->getTotalSum($invoice);
        $vat = $componentsSum->sum(fn($componentSum) => round(Price::getVat($componentSum, $invoice->owner->company_branch->domain->country->vat) / 100, 2));

        $template->setValue('totalAmount', number_format($totalSum / 100, 2, ',', ' '));
        $template->setValue('amountVat', number_format($vat, 2, ',', ' '));
        $template->setValue('penny',
            round(($invoice->sum / 100) - round($invoice->sum / 100, 0, PHP_ROUND_HALF_DOWN)) * 100);
        $template->setValue('totalAmountInWords',
            (new \NumberFormatter(App::getLocale(), \NumberFormatter::SPELLOUT))->format(round($totalSum / 100, 0,
                PHP_ROUND_HALF_DOWN)));
        if ($invoice->owner instanceof Order && $invoice->owner->isAvitoOrder()) {
            try {
                $template->setImageValue('qrPayment', [
                    "path" => $this->getQr($invoice),
                    'ratio' => true
                ]);

            } catch (Exception $e) {
                $template->setValue('qrPayment', "");
            }
        } else {
            $template->setValue('qrPayment', "");
        }

        try {
            $template->cloneRowAndSetValues('num', $result);
        } catch (\Exception $exception) {

        }

        $this->setRequisites($template, $invoice->owner);

        $file = "{$invoice->id}_invoice.docx";

        $path = config('app.upload_tmp_dir') . "/{$file}";
        $docName = match ($name) {
            'default_invoice_url' => 'Счет',
            'default_invoice_stamp_url' => 'Счет с печатью',
            'default_invoice_contract_url' => 'Счет-договор',
            'default_invoice_contract_url_with_stamp' => 'Счет-договор с печатью',
            default => trans('transbaza_order.order_invoice')
        };
        $name = $docName . " {$invoice->number}";


        if (Storage::disk('public_disk')->exists($path)) {
            Storage::disk('public_disk')->delete($path);
        }
        if(\request()->input('doc_preview')) {
            $converter = new DocumentConverter();
            $converter->setData(
                $name,
                \request()->input('doc_preview')
            );

            $result = $converter->generatePdf();
            Storage::disk()->put($result, Storage::disk('local')->get($result));

            $docs = $invoice->owner->documents()->where('name', 'like', "%{$name}%");

            if ($invoice->type !== 'avito_dotation') {
                $docs = $docs->whereHas('invoice', function ($q) {
                    $q->where('type', '!=', 'avito_dotation');
                });
            }

            $docs->get()->each(function ($item) {
                $item->delete();
            });

            $document = $invoice->owner->addDocument($name, $result, invoiceId: $invoice->id);

            return Storage::disk()->url($documentPdf['url'] ?? $document['url']);

            //$converter = new OfficeConverter(public_path($path));
            //$converter->convertTo($path);
        }else {
            $template->saveAs(public_path($path));
        }

        if($template instanceof TemplateProcessor) {
            Storage::disk()->put($path, Storage::disk('public_disk')->get($path));
        }

        if(request()->boolean('preview')) {

           $data =  [
               'preview' => $template->getResult()//file_get_contents(public_path($htmlPath))
           ];

            return $data;
        }

        if ($isOrderInvoice || $invoice->owner instanceof Lead || $invoice->owner instanceof PartsSale) {
            $docs = $invoice->owner->documents()->where('name', 'like', "%{$name}%");
            if ($invoice->type !== 'avito_dotation') {
                $docs = $docs->whereHas('invoice', function ($q) {
                    $q->where('type', '!=', 'avito_dotation');
                });
            }
            $docs->get()->each(function ($item) {
                $item->delete();
            });

            $document = $invoice->owner->addDocument($name, $path, invoiceId: $invoice->id);

            $documentPdf = $this->generatePdf($file, $path, $name, $invoice->owner, null, $isOrderInvoice && $invoice->owner->isAvitoOrder() ? 'avito_invoice' : null, invoiceId: $invoice->id);

            return Storage::disk()->url($documentPdf['url'] ?? $document['url']);
        }

        Storage::disk('public_disk')->delete($path);

        return Storage::disk()->url($path);

    }

    private function getUrlForDocument(
        $template,
        $instance,
        $name
    )
    {
        $file = uniqid('doc') . ".docx";

        $path = config('app.upload_tmp_dir') . "/{$file}";

        //   $name = trans('transbaza_order.order_invoice') . " {$invoice->number}";


        $template->saveAs(public_path($path));

        Storage::disk()->put($path, Storage::disk('public_disk')->get($path));

        $documentPdf = $this->generatePdf($file, $path, $name, $instance);

        // return Storage::disk()->url($documentPdf['url']);
//
        // Storage::disk('public_disk')->delete($path);

        if ($instance instanceof Order || $instance instanceof Lead) {
            $docs = $instance->documents()->where('name', $name)->get();

            $docs->each(function ($item) {
                $item->delete();
            });

            $document = $instance->addDocument($name, $path);


            return Storage::disk()->url($documentPdf['url']);
        }

        return Storage::disk()->url($path);
    }

    function formWorkerResult(OrderComponent $position)
    {
        $order = $position->order;
        try {


            $documentsPack =
                $this->documentsPack
                    ?: $order->lead->documentsPack;
            if (!$documentsPack->default_worker_result_url) {
                return;
            }
            $type =
                $this->withStamp
                    ? 'default_worker_result_url_with_stamp'
                    : 'default_worker_result_url';
            $url =
                ($documentsPack && $documentsPack->{$type})
                    ? Storage::disk()->url($documentsPack->{$type})
                    : public_path('documents/return_act.docx');
            $template = new TemplateProcessor($url);

        } catch (\Exception $exception) {
            $error = ValidationException::withMessages([
                'errors' => [trans('transbaza_validation.docx_error')]
            ]);

            throw $error;
        }

        $hours = $position->reports->sum('total_hours');

        $template->setValue('applicationId', $position->application_id);
        $template->setValue('orderId', $order->internal_number);
        $template->setValue('externalId', $order->external_id);
        $template->setValue('positionName', $position->worker->name);

        $calcHours =
            $position->order_type === TimeCalculation::TIME_TYPE_HOUR
                ? $position->order_duration
                : $position->order_duration * $position->worker->change_hour;
        $calcPerHour = $position->amount / $calcHours;

        $template->setValue('costPerUnit', $calcPerHour / 100);
        $template->setValue('totalCost', $calcPerHour * $hours / 100);
        $template->setValue('vatCost', ($calcPerHour * $hours) -
            Price::removeVat($calcPerHour * $hours,
                ($position->getContractorRequisite() && $position->getContractorRequisite()->vat_system === Price::TYPE_CASHLESS_VAT
                    ? $order->company_branch->domain->vat
                    : 0)) / 100);
        $template->setValue('hours', $hours);
        $template->setValue('dateFrom', $position->date_from->format('d.m.Y'));
        $template->setValue('dateTo', $position->date_to->format('d.m.Y'));
        $template->setValue('timeFrom', $position->date_from->format('H:i'));
        $template->setValue('timeTo', $position->date_to->format('H:i'));
        $template->setValue('d', now()->format('d'));
        $template->setValue('m', now()->format('m'));
        $template->setValue('y', now()->format('Y'));

        $template->setValue('hoursInWords',
            (new \NumberFormatter(App::getLocale(), \NumberFormatter::SPELLOUT))->format(round($hours, 0,
                PHP_ROUND_HALF_UP)));


        $template->setValue('vehicleName', $position->worker->name);
        $template->setValue('vehicleCategory', $position->worker->_type->name);
        $template->setValue('vehicleCategoryGenitive', $position->worker->_type->name_style);
        $template->setValue('vehicleModel', $position->worker->model->name ?? '');
        $template->setValue('licencePlate', $position->worker->number);
        if ($position->driver) {
            $template->setValue('driverName', $position->driver->name);
        }


        $this->setRequisites($template, $order);

        $file = "{$position->id}_worker_act.docx";


        $path = config('app.upload_tmp_dir') . "/{$file}";

        $name =
            trans('contractors/edit.worker_result_template') . "{$order->internal_number}-{$position->application_id}";

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

    function generatePdf(
        $docFile,
        $docPath,
        $name,
        $ownerInstance,
        $subContractorId = null,
        $type = null,
        $lovePdf = false,
        $invoiceId = null
    )
    {
        $pdfFile = str_replace('.docx', '.pdf', $docFile);
        $pdfPath = str_replace('.docx', '.pdf', $docPath);
        if (!$lovePdf) {
            $converter = new OfficeConverter(public_path($docPath));
            $converter->convertTo($pdfFile);
        } else {
            $ilovepdf = new Ilovepdf('project_public_be44b4324bfef2663cfe2f85a285b8b9_N1eGd4a42a055e7dc0c5e4afdd4e466fa68e9', 'secret_key_659f8dc0ace9a078e0a8601d45997b6b_t5PkS4a0f55ba4ea3ea062a1bd264fafe207e');
            $this->task = $ilovepdf->newTask('officepdf');
            $this->task->addFile(Storage::disk('public_disk')->path($docPath));
            $this->task->execute();
            $this->task->download(dirname(Storage::disk('public_disk')->path($pdfPath)));

        }

        Storage::disk()->put($pdfPath, Storage::disk('public_disk')->get($pdfPath), [
            'Content-Disposition' => 'inline'
        ]);

        $pdfName = $name;

        // $docs = $ownerInstance->documents()->where('name', $pdfName)->get();
//
        // $docs->each(function ($item) {
        //     $item->delete();
        // });

        $document = $ownerInstance->addDocument($pdfName, $pdfPath, $subContractorId, $type, invoiceId: $invoiceId);

        return $document;

    }

    function generateDisagreementAct(Order $order)
    {
        $position = $order->components()->findOrFail($this->options['position_id']);

        try {

            $documentsPack =
                $this->documentsPack
                    ?: $order->lead->documentsPack;
            $type =
                $this->withStamp
                    ? 'default_disagreement_url_with_stamp'
                    : 'default_disagreement_url';
            $url =
                ($documentsPack && $documentsPack->{$type})
                    ? Storage::disk()->url($documentsPack->{$type})
                    : public_path('documents/return_act.docx');
            $template = new TemplateProcessor($url);

        } catch (\Exception $exception) {
            $error = ValidationException::withMessages([
                'errors' => [trans('transbaza_validation.docx_error')]
            ]);

            throw $error;
        }

        $this->setComponentData($template, $position);

        $this->setRequisites($template, $order);

        $file = "{$position->id}_disagreement_act.docx";


        $path = config('app.upload_tmp_dir') . "/{$file}";

        $name = 'Доп. документ' . " {$position->application_id}  {$order->customer->company_name}";

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

    function formReturnAct(Order $order)
    {
        $position = $order->components()->findOrFail($this->options['position_id']);

        $subContractorId = $this->options['subContractorId'] ?? null;

        if ($subContractorId) {
            $position->setSubContractorCalculation(true);
        }
        //Create table
        $document_with_table = new PhpWord();
        $section = $document_with_table->addSection();
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '333',
        ]);

        $table->addRow();
        $table->addCell(1750)->addText(trans('transbaza_machine_edit.equipment'));
        $table->addCell(1750)->addText(trans('contractors/edit.serial_number'));
        $table->addCell(1750)->addText(trans('contractors/edit.tail_number'));
        $table->addCell(1750)->addText(trans('transbaza_machine_edit.last_rent_day'));
        $table->addCell(1750)->addText(trans('transbaza_machine_edit.running_time'));


        $name =
            ($position->worker->brand
                ? $position->worker->brand->name
                : ($position->worker->_type->name ?? '')) . ($position->worker->model
                ? " {$position->worker->model->name}"
                : '');
        $table->addRow();
        $table->addCell()->addText($name);
        $table->addCell()->addText($position->worker->vin
            ?: $position->worker->serial_number);
        $table->addCell()->addText($position->worker->board_number);
        $table->addCell()->addText(Carbon::parse($position->date_to)->format('d.m.Y'));
        $table->addCell()->addText('');


// Create writer to convert document to xml
        $objWriter = IOFactory::createWriter($document_with_table, 'Word2007');

// Get all document xml code
        $fullxml = $objWriter->getWriterPart('Document')->write();

// Get only table xml code
        $tablexml = preg_replace('/^[\s\S]*(<w:tbl\b.*<\/w:tbl>).*/', '$1', $fullxml);

        try {

            $documentsPack =
                $this->documentsPack
                    ?: $order->lead->documentsPack;
            $type =
                $this->withStamp
                    ? 'default_return_act_url_with_stamp'
                    : 'default_return_act_url';

            $url =
                ($documentsPack && $documentsPack->{$type})
                    ? Storage::disk()->url($documentsPack->{$type})
                    : public_path('documents/return_act.docx');
            $template = new TemplateProcessor($url);

        } catch (\Exception $exception) {
            $error = ValidationException::withMessages([
                'errors' => [trans('transbaza_validation.docx_error')]
            ]);

            throw $error;
        }
        $partsTable = $this->getRentPartsTable($position);
        if ($partsTable) {
            try {
                $template->cloneRowAndSetValues('partNum', $partsTable);
            } catch (\Exception $exception) {

            }
        }
        $template->setValue('table', $tablexml);
        $template->setValue('applicationId', $position->application_id);
        $this->setComponentData($template, $position);

        $this->setRequisites($template, $order);

        $date = (!empty($this->options['date'])
            ? Carbon::parse($this->options['date'])->format('Y-m-d')
            : now()->format('Y-m-d'));

        $time = !empty($this->options['time'])
            ? Carbon::parse($this->options['time'])->format('H:i')
            : now()->format('H:i');

        MachineryStamp::createTimestamp($position->worker->id, $position->order_id, 'done', "{$date} {$time}:00",
            $order->coordinates);

        $file = "{$position->id}_return_act.docx";


        $path = config('app.upload_tmp_dir') . "/{$file}";

        $name = trans('contractors/edit.return_act') . " {$position->application_id}  {$order->customer->company_name}";

        $docs = $order->documents()->where('name', $name)->get();

        $docs->each(function ($item) {
            $item->delete();
        });

        $template->saveAs(public_path($path));

        Storage::disk()->put($path, Storage::disk('public_disk')->get($path));

        Storage::disk('public_disk')->delete($path);

        $document = $order->addDocument($name, $path, $subContractorId);

        return Storage::disk()->url($document['url']);

    }

    function generateVehicleCharacteristics(Machinery $vehicle)
    {
        $document_with_table = new PhpWord();
        $section = $document_with_table->addSection();

        $table = $section->addTable([
            'unit' => TblWidth::TWIP,
            'borderSize' => 6,
            'borderColor' => '333',
        ]);

        foreach ($vehicle->optional_attributes as $attribute) {
            $table->addRow();
            $table->addCell()->addText($attribute->current_locale_name);
            $table->addCell()->addText("{$attribute->pivot->value} {$attribute->unit}");
        }

        return $vehicle->optional_attributes->isEmpty()
            ? false
            : $table;
    }

    function generateServicesTable(OrderComponent $component)
    {
        $document_with_table = new PhpWord();
        $section = $document_with_table->addSection();

        $table = $section->addTable([
            'unit' => TblWidth::TWIP,
            'borderSize' => 6,
            'borderColor' => '333',
        ]);

        $order = $component->order;
        $isVatSystem =
            $order->company_branch->getSettings()->price_without_vat && $order->contractorRequisite && $order->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT;

        $currency = $order->currency->short;

        if ($component->cost_per_unit) {
            $table->addRow();
            $table->addCell()->addText(trans('crm_fields.rent'));
            $table->addCell()->addText(number_format(($isVatSystem
                        ? $component->amount_without_vat
                        : $component->amount) / 100, 2, ',', ' ') . " {$currency}");

            $table->addRow();
            $table->addCell()->addText(trans('transbaza_proposal_search.delivery'));
            $table->addCell()->addText(number_format((($isVatSystem
                        ? $component->delivery_cost_without_vat
                        : $component->delivery_cost) / 100), 2, ',', ' ') . " {$currency}");
            $table->addRow();
            $table->addCell()->addText(trans('contractors/edit.reverse_delivery'));
            $table->addCell()->addText(number_format((($isVatSystem
                        ? $component->return_delivery_cost_without_vat
                        : $component->return_delivery_cost) / 100), 2, ',', ' ') . " {$currency}");
        }

        foreach ($component->services as $service) {
            $table->addRow();
            $table->addCell()->addText($service->name);
            $table->addCell()->addText(Price::removeVat(($service->price + $service->value_added) * $service->count,
                    $isVatSystem
                        ? $order->company_branch->domain->vat
                        : 0) / 100 . " {$currency}");
        }

        $table->addRow();
        $table->addCell()->addText(trans('tb_messages.in_total'));
        $table->addCell()->addText(number_format(($component->total_sum_with_services_without_vat / 100), 2, ',',
                ' ') . " {$currency}");


        if ($isVatSystem) {
            $table->addRow();
            $table->addCell()->addText(trans('transbaza_finance.vat') . ' ' . ($isVatSystem
                    ? $order->company_branch->domain->vat
                    : 0) . '%');
            $table->addCell()->addText(number_format((($component->total_sum_with_services - $component->total_sum_with_services_without_vat) / 100),
                    2, ',', ' ') . " {$currency}");

            $table->addRow();
            $table->addCell()->addText(trans('transbaza_finance.with_vat'));
            $table->addCell()->addText(number_format($component->total_sum_with_services / 100, 2, ',',
                    ' ') . " {$currency}");
        }

        return $table;
    }

    function generateApplication(Order $order)
    {
        /** @var OrderComponent $position */
        $position = $order->components()->findOrFail($this->options['position_id']);
        $subContractorId = $this->options['subContractorId'] ?? null;

        if ($subContractorId) {
            $position->setSubContractorCalculation(true);
        }
        try {

            $documentsPack =
                $this->documentsPack
                    ?: $order->lead->documentsPack;
            $type =
                $this->withStamp
                    ? 'default_application_url_with_stamp'
                    : 'default_application_url';
            $url =
                ($documentsPack && $documentsPack->{$type})
                    ? Storage::disk()->url($documentsPack->{$type})
                    : public_path('documents/default_application.docx');
            $template = new TemplateProcessor($url);

        } catch (\Exception $exception) {
            $error = ValidationException::withMessages([
                'errors' => [trans('transbaza_validation.docx_error')]
            ]);

            throw $error;
        }


        $tablexml =
            $position->worker instanceof Machinery
                ? $this->generateVehicleCharacteristics($position->worker)
                : null;

        $partsTable = $this->getRentPartsTable($position);
        if ($partsTable) {
            try {
                $template->cloneRowAndSetValues('partNum', $partsTable);
            } catch (\Exception $exception) {

            }
        }
        $servicesTable = $this->generateServicesTable($position);

        if ($tablexml) {
            $template->setComplexBlock('{table}', $tablexml);
        } else {
            $template->setValue('table', '');
        }

        MachineryStamp::createTimestamp($position->worker_id, $position->order_id,
            'on_the_way', now(), $order->coordinates);
        $template->setValue('createdAt', $position->created_at->format('d.m.Y'));
        $template->setValue('address', $order->address);

        $template->setComplexBlock('{servicesTable}', $servicesTable);
        if ($position->worker instanceof Machinery) {
            $template->setComplexBlock('{tariffTable}', $this->generateTariffTable($position->worker, $position->isVat()));
            $template->setComplexBlock('{characteristicsTable}', $this->generateAttributesTable($position->worker, $position->isVat()));
        }

        $template->setValue('applicationId', $position->application_id);
        $template->setValue('paymentMethod', trans('transbaza_finance.cashless_' . $position->cashless_type));

        $this->setRequisites($template, $order);
        $this->setComponentData($template, $position);
        $file = "{$position->id}_application.docx";

        $path = config('app.upload_tmp_dir') . "/{$file}";

        $name = trans('contractors/edit.application') . " {$position->application_id} {$order->customer->company_name}";

        $docs = $order->documents()
            ->where('name', 'like', "%{$name}%")
            ->where('owner_type', $subContractorId)
            ->get();

        $docs->each(function ($item) {
            $item->delete();
        });

        $template->saveAs(public_path($path));


        Storage::disk()->put($path, Storage::disk('public_disk')->get($path));


        $document = $order->addDocument($name, $path, $subContractorId);

        try {
            $documentPdf = $this->generatePdf($file, $path, $name, $order);

        } catch (\Exception $e) {
            $documentPdf = $document->toArray();
        }

        Storage::disk('public_disk')->delete($path);

        return Storage::disk()->url($documentPdf['url']);

        //   return Storage::disk()->url($document['url']);
    }

    function getSetApplication(Order $order)
    {
        try {

            $documentsPack =
                $this->documentsPack
                    ?: $order->lead->documentsPack;
            $type =
                $this->withStamp
                    ? 'default_set_application_url_with_stamp'
                    : 'default_set_application_url';
            $url =
                ($documentsPack && $documentsPack->{$type})
                    ? Storage::disk()->url($documentsPack->{$type})
                    : public_path('documents/return_act.docx');
            $template = new TemplateProcessor($url);

        } catch (\Exception $exception) {
            $error = ValidationException::withMessages([
                'errors' => [trans('transbaza_validation.docx_error')]
            ]);

            throw $error;
        }
        $template = $this->getActTemplateValues($template, $order);
        /* $template->setValue('setName', $order->machinerySet->name);

         $result = [];
         $marketPrice = 0;
         $costPerUnit = 0;
         $services = '';
         foreach ($order->components as $position) {

             if ($position->worker->model_id && !isset($result[$position->worker->model_id])) {
                 $result[$position->worker->model_id] = [
                     'name'     => $position->worker->model->name,
                     'services' => '',
                     'count'    => 0,
                 ];
             } else if (!isset($result["type_{$position->worker->type}"])) {
                 $result["type_{$position->worker->type}"] = [
                     'name'     => $position->worker->_type->name,
                     'services' => '',
                     'count'    => 0,
                 ];
             }
             foreach ($position->services as $service) {
                 $services .= "{$service->name} <w:br/><w:br/> ";
             }
             foreach ($position->parts as $part) {
                 $services .= "{$part->part->name} {$part->amount} {$part->unit->name} <w:br/> ";
             }
             $costPerUnit = $position->cost_per_unit / 100;

             $marketPrice += $position->worker->market_price / 100;

             if ($position->worker->model_id) {
                 ++$result[$position->worker->model_id]['count'];
             } else {
                 ++$result["type_{$position->worker->type}"]['count'];
             }

         }

         $currentSet = '';

         foreach ($result as $item) {
             $currentSet .= "{$item['name']} {$item['count']} шт. <w:br/> ";
         }
         $currentSet .= $services;
         $template->setValue('currentSet', $currentSet);
         $template->setValue('marketPrice', number_format($marketPrice, 2, ',', ' '));
         $template->setValue('costPerUnit', number_format($costPerUnit, 2, ',', ' '));

         $dateCollection = $order->components->map(function ($v) {
             $v->df = strtotime($v->date_from);
             $v->dt = strtotime($v->date_to);
             return $v;
         });
         $template->setValue('dateFrom', Carbon::createFromTimestamp($dateCollection->min('df'))->format('d.m.Y'));
         $template->setValue('dateTo', Carbon::createFromTimestamp($dateCollection->max('dt'))->format('d.m.Y'));
         $template->setValue('orderId', $order->internal_number);

         $this->setRequisites($template, $order);
         $this->getTotalOrderData($template, $order);*/

        $file = "set_application.docx";

        $path = config('app.upload_tmp_dir') . "/{$file}";

        $name = 'Комплект' . " {$order->internal_number}  {$order->customer->company_name}";

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
     * @param Order $order
     * @return mixed
     * @throws \PhpOffice\PhpWord\Exception\Exception
     */
    function getActTemplateValues(
        $template,
        $order
    )
    {
        //$template->setValue('currentSet', $order->machinerySet->name);
        $resultMarketPrice = 0;
        $result = [];
        /** @var MachinerySetsOrder $set */
        foreach ($order->machinerySets as $k => $set) {

            $workersGroup =
                $order->components->where('machinery_sets_order_id', $set->id)->groupBy(function ($position) {
                    return "{$position->worker->model_id}_{$position->worker->type}";
                });
            $totalMarketPrice = 0;

            if ($order->company_branch_id !== 2103) {
                $result[] = [
                    'setName' => $set->machinerySet->name,
                    'description' => '',
                    'count' => $set->count,
                    'setMarketPrice' => '',
                    'setTotalPrice' => '',//number_format($positions->sum('worker.market_price') / 100, 2, ',', ' '),
                    'setCost' => number_format($set->prices->sum / 100, 2, ',', ' '),
                    'costPerUnit' => '',
                    'shiftsCount' => round($order->components->sum('order_duration') / $order->components->count()),

                ];
            }

            /** @var Collection $workers */
            foreach ($workersGroup as $workerId => $positions) {
                $worker = $positions->first()->worker;
                $totalMarketPrice += $worker->market_price;
                $description = ($worker->description);

                $document = new PhpWord();
                $element = $document->addSection();
                Html::addHtml($element, $description);
                $objWriter = IOFactory::createWriter($document, 'Word2007');

// Get all document xml code
                $fullxml = ($objWriter->getWriterPart('Document')->write());
                $fullxml = preg_replace('/^[\s\S]*(<w:body\b.*<\/w:body>).*/', '$1', $fullxml);
                $resultMarketPrice += $worker->market_price;

                if ($order->company_branch_id !== 2103) {
                    $result[] = [
                        'setName' => $worker->name . '<w:br/><w:br/>' . $fullxml,
                        'description' => '',
                        'count' => " {$positions->count()}",
                        'setMarketPrice' => number_format($worker->market_price / 100, 2, ',', ' '),
                        'setTotalPrice' => '',//number_format($positions->sum('worker.market_price') / 100, 2, ',', ' '),
                        'setCost' => '',//number_format($positions->sum('amount') / 100, 2, ',', ' '),
                        'costPerUnit' => '',
                        'shiftsCount' => '',
                    ];
                }

                $resultParts = [];
                foreach ($positions as $position) {
                    foreach ($position->parts as $part) {
                        if (isset($resultParts["part_{$part->part_id}"])) {
                            $resultParts["part_{$part->part_id}"]['count'] += $part->amount;
                            continue;
                        }
                        $samePart = Item::query()->where('stock_id', $part->stock_id)
                            ->where('part_id', $part->part_id)
                            ->where('owner_type', Posting::class)
                            ->orderBy('id', 'desc')
                            ->first();
                        $marketPrice =
                            $samePart
                                ? $samePart->cost_per_unit
                                : 0;

                        $resultParts["part_{$part->part_id}"] = [
                            'name' => $part->part->name,
                            'count' => $part->amount,
                            'unit' => $part->unit->name,
                            'market_price' => $marketPrice,

                        ];
                    }
                }

                foreach ($resultParts as $part) {
                    $totalMarketPrice += $part['market_price'] * $part['count'];
                    $resultMarketPrice += $part['market_price'] * $part['count'];
                    $result[] = [
                        'setName' => "{$part['name']}",
                        'description' => '',
                        'count' => "{$part['count']}",
                        'setMarketPrice' => number_format($part['market_price'] / 100, 2, ',', ' '),
                        'setTotalPrice' => number_format($part['market_price'] * $part['count'] / 100, 2, ',', ' '),
                        'setCost' => '',
                        'costPerUnit' => '',
                        'shiftsCount' => '',
                    ];
                }
            }

            $result[0]['costPerUnit'] =
                number_format($set->prices->sum / $order->components->first()->order_duration / $set->count / 100, 2,
                    ',', ' ');
            $result[0]['totalMarketPrice'] = number_format($totalMarketPrice / 100, 2, ',', ' ');
        }

        try {
            $template->cloneRowAndSetValues('setName', $result);
        } catch (\Exception $exception) {

        }

        $dateCollection = $order->components->map(function ($v) {
            $v->df = strtotime($v->date_from);
            $v->dt = strtotime($v->date_to);
            return $v;
        });

        $shifts = $order->components->first();

        $delivery = $positions->sum('delivery_cost') + $positions->sum('return_delivery');

        $avgSetCost = ($delivery + $shifts->cost_per_unit) / $order->machinerySets->sum('count') / 100;
        $template->setValue('avgSetCost', number_format($avgSetCost, 2, ',', ' '));
        $template->setValue('avgSetCostWithoutDelivery', number_format($shifts->cost_per_unit / 100, 2, ',', ' '));
        $template->setValue('avgSetCostWithoutDeliveryInWords',
            (new \NumberFormatter(App::getLocale(), \NumberFormatter::SPELLOUT))->format($shifts->cost_per_unit / 100));
        $template->setValue('avgSetCostInWords',
            (new \NumberFormatter(App::getLocale(), \NumberFormatter::SPELLOUT))->format($avgSetCost));
        $template->setValue('resultMarketPrice', number_format($resultMarketPrice / 100, 2, ',', ' '));
        $template->setValue('dateFrom', Carbon::createFromTimestamp($dateCollection->min('df'))->format('d.m.Y'));
        $template->setValue('dateTo', Carbon::createFromTimestamp($dateCollection->max('dt'))->format('d.m.Y'));
        $template->setValue('orderId', $order->internal_number);
        $template->setValue('externalId', $order->external_id);

        $this->setRequisites($template, $order);
        $this->getTotalOrderData($template, $order, true);

        return $template;
    }

    function getSetReturnAct(Order $order)
    {
        try {

            $documentsPack =
                $this->documentsPack
                    ?: $order->lead->documentsPack;
            $type =
                $this->withStamp
                    ? 'default_return_set_act_url_with_stamp'
                    : 'default_return_set_act_url';

            $url =
                ($documentsPack && $documentsPack->{$type})
                    ? Storage::disk()->url($documentsPack->{$type})
                    : public_path('documents/acceptance_report.docx');
            $template = new TemplateProcessor($url);

        } catch (\Exception $exception) {
            $error = ValidationException::withMessages([
                'errors' => [trans('transbaza_validation.docx_error')]
            ]);

            throw $error;
        }

        $template = $this->getActTemplateValues($template, $order);
        $file = "set_return_act.docx";

        $path = config('app.upload_tmp_dir') . "/{$file}";

        $name = 'Комплект' . " {$order->internal_number}  {$order->customer->company_name}";

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

    function getSetAct(Order $order)
    {
        // $positions = $order->components()->get();

        try {

            $documentsPack =
                $this->documentsPack
                    ?: $order->lead->documentsPack;
            $type =
                $this->withStamp
                    ? 'default_set_act_url_with_stamp'
                    : 'default_set_act_url';

            $url =
                ($documentsPack && $documentsPack->{$type})
                    ? Storage::disk()->url($documentsPack->{$type})
                    : public_path('documents/acceptance_report.docx');
            $template = new TemplateProcessor($url);

        } catch (\Exception $exception) {
            $error = ValidationException::withMessages([
                'errors' => [trans('transbaza_validation.docx_error')]
            ]);

            throw $error;
        }

        // $template->setValue('table', $tablexml);
        $template = $this->getActTemplateValues($template, $order);

        /*  $dateCollection = $positions->map(function ($v) {
              $v->df = strtotime($v->date_from);
              $v->dt = strtotime($v->date_to);
              return $v;
          });

          $result = [];

          $k = 0;
          foreach ($positions as $position) {

              $pledge = $position->services->filter(function ($item) {
                  return $item->customService->is_pledge;
              })->first();

              $filteredServices = $position->services->filter(function ($item) {
                  return !$item->customService->is_pledge;
              });

              $result[] = [
                  'num'                => ++$k,
                  'vehicleName'        => $position->worker->name,
                  'vehicleMarketPrice' => number_format($position->worker->market_price / 100, 2, ',', ' '),
                  'vehicleCostPerUnit' => number_format(($position->cost_per_unit + $position->value_added) / 100, 2, ',', ' '),
                  'vehicleCost'        => number_format($position->amount / 100, 2, ',', ' '),
                  'pledge'             => number_format(($pledge
                          ? $pledge->price
                          : 0) / 100, 2, ',', ' '),
              ];
              foreach ($position->parts as $part) {
                  $samePart = Item::query()->where('stock_id', $part->stock_id)
                      ->where('part_id', $part->part_id)
                      ->where('owner_type', Posting::class)
                      ->orderBy('id', 'desc')
                      ->first();
                  $marketPrice =
                      $samePart
                          ? $samePart->cost_per_unit
                          : 0;
                  $result[] = [
                      'num'                => '',
                      'vehicleName'        => $part->part->name,
                      'vehicleMarketPrice' => number_format($marketPrice / 100, 2, ',', ' '),
                      'vehicleCostPerUnit' => '',
                      'vehicleCost'        => '',
                      'pledge'             => '',
                  ];
              }

              if ($position->delivery_cost) {
                  $result[] = [
                      'num'                => '',
                      'vehicleName'        => 'Доставка',
                      'vehicleMarketPrice' => '',
                      'vehicleCostPerUnit' => '',
                      'pledge'             => '',
                      'vehicleCost'        => number_format(($position->delivery_cost / 100), 2, ',', ' '),
                  ];
              }
              if ($position->return_delivery) {

                  $result[] = [
                      'num'                => '',
                      'vehicleName'        => 'Обратная доставка',
                      'vehicleMarketPrice' => '',
                      'vehicleCostPerUnit' => '',
                      'pledge'             => '',
                      'vehicleCost'        => number_format(($position->return_delivery / 100), 2, ',', ' '),
                  ];
              }

              //    if ($type !== 'default_single_act_url') {
              foreach ($filteredServices as $service) {

                  $result[] = [
                      'num'                => '',
                      'vehicleName'        => $service->name,
                      'vehicleMarketPrice' => '',
                      'vehicleCostPerUnit' => '',
                      'pledge'             => '',
                      'vehicleCost'        => number_format($service->price / 100, 2, ',', ' '),
                  ];
              }
              // }
          }

          try {
              $template->cloneRowAndSetValues('vehicleName', $result);
          } catch (\Exception $exception) {

          }


          $template->setValue('dateFrom', Carbon::createFromTimestamp($dateCollection->min('df'))->format('d.m.Y'));
          $template->setValue('dateTo', Carbon::createFromTimestamp($dateCollection->max('dt'))->format('d.m.Y'));
          $template->setValue('setName', $order->machinerySet->name);

          $template->setValue('totalWithServicesVat', number_format((($positions->sum('total_sum_with_services')) / 100), 2, ',', ' '));
          $template->setValue('totalWithServicesVatCostInWords', (new \NumberFormatter(App::getLocale(), \NumberFormatter::SPELLOUT))->format(($positions->sum('total_sum_with_services')) / 100));
          $template->setValue('amountCost', number_format(($positions->sum('total_sum') / 100), 2, ',', ' '));

          $template->setValue('amountCostInWords', (new \NumberFormatter(App::getLocale(), \NumberFormatter::SPELLOUT))->format($positions->sum('total_sum') / 100));
          $template->setValue('orderId', $order->internal_number);


          $this->setRequisites($template, $order);*/

        $file = "{$order->id}_single_act.docx";

        //$this->setComponentData($template, $position);

        $path = config('app.upload_tmp_dir') . "/{$file}";

        $name = 'Акт' . " {$order->internal_number} {$order->customer->company_name}";

        $docs = $order->documents()->where('name', $name)->get();

        $docs->each(function ($item) {
            $item->delete();
        });

        $template->saveAs(public_path($path));

        Storage::disk()->put($path, Storage::disk('public_disk')->get($path));

        $documentPdf = $this->generatePdf($file, $path, $name, $order);

        Storage::disk('public_disk')->delete($path);

        $document = $order->addDocument($name, $path);

        return Storage::disk()->url($documentPdf['url']);


    }

    function formSingleAct(
        Order $order,
              $type = 'default_single_act_url',
              $withStamp = false,
        $preview = false
    )
    {
        $inputPositions = collect($this->options['positions'] ?? []);
        $positions = $order->components()
            ->when($inputPositions->isNotEmpty(), fn($q) => $q->whereIn('id', $inputPositions->keys()))
            ->get();
        $subContractorId = $this->options['subContractorId'] ?? null;
        $dotation = 0;
        try {
            if ($order->isAvitoOrder() && in_array($type, ['default_return_single_act_url', 'default_upd_url'])) {
                $dotation = $order->components()->sum('avito_dotation_sum');
                if ($type === 'default_return_single_act_url') {
                    $docType = 'default_avito_return_act';
                }
                if ($type === 'default_upd_url') {
                    $docType = 'default_avito_upd';
                }
            } else {
                $docType =
                    $withStamp
                        ? $type . '_with_stamp'
                        : $type;
            }

            $documentsPack =
                $this->documentsPack
                    ?: $order->lead->documentsPack;
            $url =
                ($documentsPack && $documentsPack->{$docType})
                    ? Storage::disk()->url($documentsPack->{$docType})
                    : public_path('documents/acceptance_report.docx');
            if($preview) {
                $docTypeHtml = $docType.'_html';
                $documentsPack->{$docTypeHtml};
                $template = new HtmlTemplateProcessor($documentsPack->{$docTypeHtml});
            }else {
                $template = new TemplateProcessor($url);
            }

        } catch (\Exception $exception) {
            logger($exception->getMessage());
            $error = ValidationException::withMessages([
                'errors' => [trans('transbaza_validation.docx_error') . ' ' . $exception->getMessage() . ' ' . $exception->getTraceAsString()]
            ]);

            throw $error;
        }

        // $template->setValue('table', $tablexml);

        $dateCollection = $positions->map(function ($v) {
            $v->df = strtotime($v->date_from);
            $v->dt = strtotime($v->date_to);
            return $v;
        });

        $result = [];

        $k = 0;
        $totalPledge = 0;

        $date = (!empty($this->options['date'])
            ? Carbon::parse($this->options['date'])->format('d.m.Y')
            : now($order->company_branch->timezone)->format('d.m.Y'));

        $time = (!empty($this->options['time'])
            ? Carbon::parse($this->options['time'])->format('H:i:s')
            : now($order->company_branch->timezone)->format('H:i:s'));

        $dateFormatted = Carbon::createFromFormat('d.m.Y', $date);
        $template->setValue('dateD', $dateFormatted->format('d'));
        $template->setValue('dateM', $dateFormatted->format('m'));
        $template->setValue('dateY', $dateFormatted->format('Y'));

        switch ($type) {
            case 'default_single_act_url':
                $status = 'arrival';
                break;
            case 'default_single_application_url':
                $status = 'on_the_way';
                break;
            case 'default_return_single_act_url':
                $status = 'done';
                break;
            default:
                $status = null;
        }

        if ($type === 'default_upd_url') {
            $template->setValue('updNumber', UdpRegistry::getNumber($order, 'upd'));
            $template->setValue('updStatus', $this->options['upd_type'] ?? 1);
            $order->update([
                'tmp_status' => 'upd'
            ]);
        }

        $vat = $order->domain->vat;
        $s = new OrderService();

        foreach ($positions as $position) {
            if (in_array($type, [
                'default_return_single_act_url',
                'default_upd_url',
            ])) {
                if ($position->actual && $position->status !== Order::STATUS_DONE) {
                    $this->throwFactPositionError();
                    return;
                }
            }
            if ($position->worker_type === WarehousePartSet::class) {
                goto parts;
            }
            if ($status) {
                MachineryStamp::createTimestamp($position->worker->id, $position->order_id, $status, "{$date} {$time}",
                    $order->coordinates);
            }

            if ($subContractorId) {
                $position->setSubContractorCalculation(true);
            }

            if ($type === 'default_return_single_act_url') {
                MachineryStamp::createTimestamp($position->worker->id, $position->order_id, 'done', "{$date} {$time}",
                    $order->coordinates);
                //    $s->donePosition($order->components()->find($position->id));
            }


            $pledge = $position->services->filter(function ($item) {
                return $item->customService->is_pledge;
            })->first();

            $filteredServices = $position->services->filter(function ($item) use ($inputPositions, $position) {
                return !$item->customService->is_pledge && in_array($item->id, $inputPositions[$position->id]['services']);
            });
            $totalPledge += $pledge
                ? $pledge->price
                : 0;


            $description = ($position->worker->description);

            $document = new PhpWord();
            $element = $document->addSection();
            Html::addHtml($element, $description);
            $objWriter = IOFactory::createWriter($document, 'Word2007');

// Get all document xml code
            $fullxml = ($objWriter->getWriterPart('Document')->write());
            $fullxml = preg_replace('/^[\s\S]*(<w:body\b.*<\/w:body>).*/', '$1', $fullxml);

            $amount = $position->amount - $position->avito_dotation_sum;
            $amountWithoutVat = Price::removeVat($amount,$vat);

            $costPerUnit = $position->cost_per_unit_doc - $position->avito_dotation_sum;
            $costPerUnitWithoutVat = Price::removeVat($costPerUnit, $vat);

            $result[] = [
                'num' => ++$k,
                'vehicleName' => getMachineryValueByMask(
                    $this->companyBranch->getSettings()->machinery_document_mask, $position->worker,
                    [
                        'address' => $order->address,
                        'attributes' => $position->order->lead->positions->firstWhere('type_id', $position->worker->type)?->category_options?->join(' '),
                        'description' => $position->description,
                        'externalId' => $order->external_id,
                        'createdAt' => $order->created_at?->format('d.m.Y'),
                        'dateFrom' => $position->date_from->format('d.m.Y'),
                        'dateTo' => $position->date_to->format('d.m.Y'),
                        'timeFrom' => $position->date_from->format('H:i'),
                        'timeTo' => $position->date_to->format('H:i'),
                    ]),
                'vehicleNameWithAddress' => "Аренда {$position->worker->name} ({$order->address})",
                'vehicleNameWithAddressDates' => "Аренда {$position->worker->name} ({$order->address}), {$position->date_from?->format('d.m.Y')} - {$position->date_to?->format('d.m.Y')}",
                'vehicleDescription' => $position->description,
                'vehicleSaleCost' => 0, //Todo add seling price,
                'externalId' => $order->external_id,
                'vehicleComment' => $position->comment,
                'vehicleYear' => $position->worker->year,
                'category' => $position->worker->_type->name ?? '',
                'model' => $position->worker->model->name ?? '',
                'brand' => $position->worker->brand->name ?? '',
                'description' => $preview ? $description : $fullxml,
                'count' => 1,
                'shiftsCount' => $position->is_month ? $position->getInvoiceDuration() : round($position->order_duration),
                'rentType' => $position->order_type === 'shift'
                    ? (
                    $this->companyBranch->getSettings()->getActualShiftName($position->worker->change_hour, $position->is_month)
                    )
                    : trans('units.h'),
                'date' => Carbon::parse($date)->format('d.m.Y'),
                'time' => Carbon::createFromFormat('H:i:s', $time)->format('H:i'),
                'serialNumber' => $position->worker->serial_number,
                'dateFrom' => $position->date_from->format('d.m.Y'),
                'dateTo' => $position->date_to?->format('d.m.Y'),
                'timeFrom' => $position->date_from?->format('H:i'),
                'timeTo' => $position->date_to?->format('H:i'),
                'boardNumber' => $position->worker->board_number,
                'minOrder' => $position->worker->min_order,
                'vehicleMarketPrice' => number_format($position->worker->market_price / 100, 2, ',', ' '),
                'vehicleCostPerUnit' => number_format($costPerUnit / 100, 2, ',', ' '),
                'vehicleCostPerUnitWithoutVat' => number_format($costPerUnitWithoutVat/ 100, 2, ',', ' '),
                'vehicleAmount' => number_format($amount / 100, 2, ',', ' '),
                'vehicleAmountWithoutVat' => number_format($amountWithoutVat / 100, 2, ',', ' '),
                'vehicleAmountVat' => number_format(($amount - $amountWithoutVat) / 100, 2, ',', ' '),
                'vehicleCost' => number_format(($position->total_sum - $position->avito_dotation_sum) / 100, 2, ',', ' '),
                'vehicleCostWithoutVat' => number_format(($position->total_sum_with_services_without_vat - $position->avito_dotation_sum) / 100, 2, ',', ' '),
                'pledge' => number_format(($pledge
                        ? $pledge->price
                        : 0) / 100, 2, ',', ' '),
                'amountVat' => number_format(($costPerUnit - $costPerUnitWithoutVat) / 100, 2, ',', ' '),
                'vehicleVat' => number_format(($position->total_sum_with_services - $position->total_sum_with_services_without_vat) / 100, 2, ',', ' '),
                'vehicleCostWithoutVat',
            ];

            if (!in_array($type, [/*'default_return_single_act_url', 'default_single_act_url'*/])) {
                if ($position->delivery_cost) {
                    $result[] = [
                        'num' => ++$k,
                        'vehicleName' => 'Доставка',
                        'vehicleNameWithAddress' => 'Доставка',
                        'vehicleNameWithAddressDates' => 'Доставка',
                        'vehicleDescription' => 'Доставка',
                        'externalId' => $order->external_id,
                        'vehicleMarketPrice' => '',
                        'vehicleCostPerUnit' => number_format(($position->delivery_cost / 100), 2, ',', ' '),
                        'description' => '',
                        'vehicleComment' => '',
                        'vehicleAmountVat' => number_format((($position->delivery_cost - $position->delivery_cost_without_vat) / 100), 2, ',', ' '),
                        'vehicleCostPerUnitWithoutVat' => number_format(($position->delivery_cost_without_vat / 100), 2, ',', ' '),
                        'vehicleAmountWithoutVat' => number_format(($position->delivery_cost_without_vat / 100), 2, ',', ' '),
                        'vehicleYear' => '',
                        'model' => '',
                        'date' => '',
                        'time' => '',
                        'count' => '',
                        'rentType' => 'шт.',
                        'minOrder' => '',
                        'dateFrom' => '',
                        'shiftsCount' => 1,
                        'serialNumber' => '',
                        'boardNumber' => '',
                        'pledge' => '',
                        'dateTo' => '',
                        'timeFrom' => '',
                        'timeTo' => '',
                        'vehicleCostWithoutVat' => number_format(($position->delivery_cost_without_vat / 100), 2, ',', ' '),
                        'vehicleVat' => number_format((($position->delivery_cost - $position->delivery_cost_without_vat) / 100), 2, ',', ' '),
                        'vehicleCost' => number_format(($position->delivery_cost / 100), 2, ',', ' '),
                        'vehicleAmount' => number_format(($position->delivery_cost / 100), 2, ',', ' '),
                    ];
                }
                if ($position->return_delivery) {

                    $result[] = [
                        'num' => ++$k,
                        'vehicleName' => 'Обратная доставка',
                        'vehicleNameWithAddress' => 'Обратная доставка',
                        'vehicleNameWithAddressDates' => 'Обратная доставка',
                        'vehicleComment' => '',
                        'model' => '',
                        'externalId' => $order->external_id,
                        'vehicleDescription' => 'Обратная доставка',
                        'vehicleMarketPrice' => '',
                        'vehicleCostPerUnitWithoutVat' => number_format(($position->return_delivery_without_vat / 100), 2, ',', ' '),
                        'vehicleAmountVat' => number_format((($position->return_delivery - $position->return_delivery_without_vat) / 100), 2, ',', ' '),
                        'vehicleAmountWithoutVat' => number_format(($position->return_delivery_without_vat / 100), 2, ',', ' '),
                        'vehicleYear' => '',
                        'description' => '',
                        'date' => '',
                        'time' => '',
                        'vehicleCostPerUnit' => number_format(($position->return_delivery / 100), 2, ',', ' '),
                        'rentType' => 'шт.',
                        'minOrder' => '',
                        'dateFrom' => '',
                        'pledge' => '',
                        'shiftsCount' => 1,
                        'count' => '',
                        'serialNumber' => '',
                        'boardNumber' => '',
                        'amountVat' => '',
                        'dateTo' => '',
                        'timeFrom' => '',
                        'timeTo' => '',
                        'vehicleCostWithoutVat' => number_format(($position->return_delivery_without_vat / 100), 2, ',', ' '),
                        'vehicleVat' => number_format((($position->return_delivery - $position->return_delivery_without_vat) / 100), 2, ',', ' '),
                        'vehicleCost' => number_format(($position->return_delivery / 100), 2, ',', ' '),
                        'vehicleAmount' => number_format(($position->return_delivery / 100), 2, ',', ' '),
                    ];
                }
            }


            //    if ($type !== 'default_single_act_url') {
            /** @var OrderComponentService $service */
            foreach ($filteredServices as $service) {
                if ($subContractorId) {
                    $service->setSubContractorCalculation(true);
                }
                $serviceCostPerUnit = $service->price_doc / 100;
                $serviceCost = ($service->price_doc) * $service->count / 100;
                $result[] = [
                    'num' => ++$k,
                    'vehicleName' => $service->name,
                    'vehicleNameWithAddress' => $service->name,
                    'vehicleNameWithAddressDates' => $service->name,
                    'vehicleDescription' => $service->name,
                    'vehicleComment' => '',
                    'vehicleMarketPrice' => '',
                    'vehicleCostPerUnitWithoutVat' => '',
                    'vehicleAmountWithoutVat' => '',
                    'description' => '',
                    'vehicleYear' => '',
                    'externalId' => $order->external_id,
                    'date' => '',
                    'time' => '',
                    'model' => '',
                    'rentType' => $service->customService->unit->name ?? '',
                    'minOrder' => '',
                    'pledge' => '',
                    'shiftsCount' => $service->count,
                    'serialNumber' => '',
                    'boardNumber' => '',
                    'amountVat' => '',
                    'dateFrom' => '',
                    'count' => '',
                    'dateTo' => '',
                    'timeFrom' => '',
                    'timeTo' => '',
                    'vehicleCostWithoutVat' => number_format(Price::removeVat($serviceCost, $vat), 2, ',', ' '),
                    'vehicleVat' => number_format($serviceCost - Price::removeVat($serviceCost, $vat), 2, ',', ' '),
                    'vehicleCostPerUnit' => number_format($serviceCostPerUnit, 2, ',', ' '),
                    'vehicleCost' => number_format($serviceCost, 2, ',', ' '),
                    'vehicleAmount' => number_format($serviceCost, 2, ',', ' '),
                ];
            }
            // }
            parts:
            $parts = $this->getRentPartsTable($position, ($inputPositions[$position->id] ?? null) ? ($inputPositions[$position->id]['parts'] ?? []) : []);
            if ($parts) {
                $period = "{$position->date_from->format('d.m.Y')} {$position->date_to->format('d.m.Y')}";
                if(request()->boolean('warehouse_set')){
                    $vehicleAmountSale = 0;
                    $partsCount = 0;

                    foreach ($position->rent_parts->where('type', 'rent') as $i => $rent_part){
                        $partsCount += $rent_part->count;
                        $vehicleAmountSale += $rent_part->company_branches_warehouse_part?->default_sale_cost * $partsCount;
                    }
                    $vehicleAmountSale = number_format($vehicleAmountSale / 100 ?: 0, 2, ',', ' ');
                    $costPerUnit = $position->cost_per_unit_doc - $position->avito_dotation_sum;
                    $costPerUnitWithoutVat = Price::removeVat($costPerUnit, $vat);
                    $amount = $position->amount - $position->avito_dotation_sum;
                    $result[] = [
                        'num' => ++$k,
                        'vehicleName' => $position->worker->name . "($position->order_duration смен.) ({$period})",
                        'vehicleNameWithAddress' => $position->worker->name . "($position->order_duration смен.) ({$period})",
                        'vehicleNameWithAddressDates' => $position->worker->name . "($position->order_duration смен.) ({$period})",
                        'vehicleDescription' => $position->worker->name,
                        'vehicleSaleCost' => 0,
                        'vehicleAmountSale' => $vehicleAmountSale,
                        'partsCount' => $partsCount,
                        'vehicleComment' => '',
                        'vehicleMarketPrice' => '',
                        'vehicleYear' => '',
                        'externalId' => $order->external_id,
                        'description' => '',
                        'model' => '',
                        'date' => '',
                        'time' => '',
                        'rentType' => $position->order_type === 'shift'
                            ? (
                            $this->companyBranch->getSettings()->getActualShiftName($position->worker->change_hour, $position->is_month)
                            )
                            : trans('units.h'),
                        'minOrder' => '',
                        'pledge' => '',
                        'vehicleCostPerUnitWithoutVat' => '',
                        'vehicleAmountWithoutVat' => '',
                        'shiftsCount' => $position->order_duration,
                        'count' => $position->order_duration,
                        'serialNumber' => '',
                        'boardNumber' => '',
                        'amountVat' => '',
                        'dateFrom' => $position->date_from->format('d.m.Y'),
                        'dateTo' => $position->date_to?->format('d.m.Y'),
                        'timeFrom' => $position->date_from?->format('H:i'),
                        'timeTo' => $position->date_to?->format('H:i'),
                        'vehicleCostPerUnit' => number_format($costPerUnit / 100, 2, ',', ' '),
                        'vehicleAmount' => number_format($amount / 100, 2, ',', ' '),
                        'vehicleCost' => number_format(($position->total_sum - $position->avito_dotation_sum) / 100, 2, ',', ' '),
                        'vehicleCostWithoutVat' => number_format(($position->total_sum_with_services_without_vat - $position->avito_dotation_sum) / 100, 2, ',', ' '),
                        'vehicleVat' => number_format(($position->total_sum_with_services - $position->total_sum_with_services_without_vat) / 100, 2, ',', ' '),
                    ];
                }else {
                    foreach ($parts as $part) {
                        $result[] = [
                            'num' => ++$k,//$part['partNum'],
                            'vehicleName' => $part['partName'] . "({$part['partCount']} штук.) ({$period})",
                            'vehicleNameWithAddress' => $part['partName'] . "({$part['partCount']} штук.) ({$period})",
                            'vehicleNameWithAddressDates' => $part['partName'] . "({$part['partCount']} штук.) ({$period})",
                            'vehicleDescription' => $part['partName'],
                            'vehicleSaleCost' => $part['partSaleCost'],
                            'vehicleAmountSale' => $part['vehicleAmountSale'] ,
                            'vehicleComment' => '',
                            'vehicleMarketPrice' => '',
                            'vehicleYear' => '',
                            'externalId' => $order->external_id,
                            'description' => '',
                            'model' => '',
                            'date' => '',
                            'time' => '',
                            'rentType' => $position->order_type === 'shift'
                                ? (
                                $this->companyBranch->getSettings()->getActualShiftName($position->worker->change_hour, $position->is_month)
                                )
                                : trans('units.h'),
                            'minOrder' => '',
                            'pledge' => '',
                            'vehicleCostPerUnitWithoutVat' => '',
                            'vehicleAmountWithoutVat' => '',
                            'shiftsCount' => $part['partDuration'],
                            'count' => $part['partCount'],
                            'serialNumber' => '',
                            'boardNumber' => '',
                            'amountVat' => '',
                            'dateFrom' => $part['dateFrom'] ?? '',
                            'dateTo' => $part['dateTo'] ?? '',
                            'timeFrom' => $part['timeFrom'] ?? '',
                            'timeTo' => $part['timeTo'] ?? '',
                            'vehicleCostWithoutVat' => $part['partCostWithoutVat'],
                            'vehicleVat' => '',
                            'vehicleCostPerUnit' => $part['partCost'],
                            'vehicleCost' => $part['partCostAmount'],
                            'vehicleAmount' => $part['partCostAmount'],
                        ];
                        if ($position->delivery_cost) {
                            $result[] = [
                                'num' => ++$k,
                                'vehicleName' => 'Доставка',
                                'vehicleNameWithAddress' => 'Доставка',
                                'vehicleNameWithAddressDates' => 'Доставка',
                                'vehicleDescription' => 'Доставка',
                                'externalId' => $order->external_id,
                                'vehicleMarketPrice' => '',
                                'vehicleCostPerUnit' => number_format(($position->delivery_cost / 100), 2, ',', ' '),
                                'description' => '',
                                'vehicleComment' => '',
                                'vehicleAmountVat' => number_format((($position->delivery_cost - $position->delivery_cost_without_vat) / 100), 2, ',', ' '),
                                'vehicleCostPerUnitWithoutVat' => number_format(($position->delivery_cost_without_vat / 100), 2, ',', ' '),
                                'vehicleAmountWithoutVat' => number_format(($position->delivery_cost_without_vat / 100), 2, ',', ' '),
                                'vehicleYear' => '',
                                'model' => '',
                                'date' => '',
                                'time' => '',
                                'count' => '',
                                'rentType' => 'шт.',
                                'minOrder' => '',
                                'dateFrom' => '',
                                'shiftsCount' => 1,
                                'serialNumber' => '',
                                'boardNumber' => '',
                                'pledge' => '',
                                'dateTo' => '',
                                'timeFrom' => '',
                                'timeTo' => '',
                                'vehicleCostWithoutVat' => number_format(($position->delivery_cost_without_vat / 100), 2, ',', ' '),
                                'vehicleVat' => number_format((($position->delivery_cost - $position->delivery_cost_without_vat) / 100), 2, ',', ' '),
                                'vehicleCost' => number_format(($position->delivery_cost / 100), 2, ',', ' '),
                                'vehicleAmount' => number_format(($position->delivery_cost / 100), 2, ',', ' '),
                            ];
                        }
                        if ($position->return_delivery) {

                            $result[] = [
                                'num' => ++$k,
                                'vehicleName' => 'Обратная доставка',
                                'vehicleNameWithAddress' => 'Обратная доставка',
                                'vehicleNameWithAddressDates' => 'Обратная доставка',
                                'vehicleComment' => '',
                                'model' => '',
                                'externalId' => $order->external_id,
                                'vehicleDescription' => 'Обратная доставка',
                                'vehicleMarketPrice' => '',
                                'vehicleCostPerUnitWithoutVat' => number_format(($position->return_delivery_without_vat / 100), 2, ',', ' '),
                                'vehicleAmountVat' => number_format((($position->return_delivery - $position->return_delivery_without_vat) / 100), 2, ',', ' '),
                                'vehicleAmountWithoutVat' => number_format(($position->return_delivery_without_vat / 100), 2, ',', ' '),
                                'vehicleYear' => '',
                                'description' => '',
                                'date' => '',
                                'time' => '',
                                'vehicleCostPerUnit' => number_format(($position->return_delivery / 100), 2, ',', ' '),
                                'rentType' => 'шт.',
                                'minOrder' => '',
                                'dateFrom' => '',
                                'pledge' => '',
                                'shiftsCount' => 1,
                                'count' => '',
                                'serialNumber' => '',
                                'boardNumber' => '',
                                'amountVat' => '',
                                'dateTo' => '',
                                'timeFrom' => '',
                                'timeTo' => '',
                                'vehicleCostWithoutVat' => number_format(($position->return_delivery_without_vat / 100), 2, ',', ' '),
                                'vehicleVat' => number_format((($position->return_delivery - $position->return_delivery_without_vat) / 100), 2, ',', ' '),
                                'vehicleCost' => number_format(($position->return_delivery / 100), 2, ',', ' '),
                                'vehicleAmount' => number_format(($position->return_delivery / 100), 2, ',', ' '),
                            ];
                        }
                    }
                }
            }
        }
        while (true) {
            try {
                $template->cloneRowAndSetValues('vehicleName', $result);
                break;
            } catch (\Exception $exception) {
                try {
                    $template->cloneRowAndSetValues('vehicleNameWithAddress', $result);
                } catch (\Exception $exception) {
                    try {
                        $template->cloneRowAndSetValues('num', $result);
                    } catch (\Exception $exception) {
                        break;
                    }
                    break;
                }
                break;
            }
        }
        while (true) {
            try {
                $template->cloneRowAndSetValues('vehicleDescription', $result);
            } catch (\Exception $exception) {
                break;
            }
        }
        if($template instanceof TemplateProcessor) {
            try {

                $this->setMachineriesList($positions->pluck('worker')->filter(fn($worker) => $worker::class === Machinery::class), $template);
                $template->setComplexBlock('{tariffsTable}', $this->generateTariffsTable($positions->pluck('worker')->filter(fn($worker) => $worker::class === Machinery::class), true));
            } catch (\Exception $e) {

            }
        }

        $position = $positions->first();

        if ($position) {
            $template->setValue('vehicleBase', htmlspecialchars($position->worker->base->name ?? ''));
            $template->setValue('vehicleBaseAddress', htmlspecialchars($position->worker->base->address ?? ''));
            $template->setValue('baseKpp', htmlspecialchars($position->worker->base->kpp ?? ''));
        }


        $template->setValue('rowsCount', count($result));
        $template->setValue('totalMarketPrice', number_format($positions->sum('worker.market_price') / 100, 2, ',', ' '));
        $template->setValue('itemsCount', $positions->unique('machinery_id')->count());
        $template->setValue('totalPledge', number_format($totalPledge / 100, 2, ',', ' '));
        $template->setValue('dateFrom', Carbon::createFromTimestamp($dateCollection->min('df'))->format('d.m.Y'));
        $template->setValue('timeFrom', Carbon::createFromTimestamp($dateCollection->min('df'))->format('H:i'));
        $template->setValue('dateTo', Carbon::createFromTimestamp($dateCollection->max('dt'))->format('d.m.Y'));
        $template->setValue('timeTo', Carbon::createFromTimestamp($dateCollection->max('dt'))->format('H:i'));

        $template->setValue('d', now()->format('d'));
        $template->setValue('m', now()->format('m'));
        $template->setValue('y', now()->format('Y'));


        $template->setValue('totalWithServicesWithoutPledge',
            number_format((($positions->sum('total_sum_with_services_without_pledge') - $dotation) / 100), 2, ',', ' '));
        $template->setValue('totalWithServicesWithoutPledgeWithoutVat',
            number_format(((Price::removeVat($positions->sum('total_sum_with_services_without_pledge_without_vat'), $vat) - $dotation) / 100), 2, ',', ' '));

        $total = $positions->sum('total_sum_with_services') - $dotation;
        $template->setValue('totalWithServicesVat',
            number_format(($total / 100), 2, ',', ' '));

        $template->setValue('totalWithServicesOnlyVat',
            number_format((($positions->sum(fn($item) => $total - Price::removeVat($total, $vat))) / 100), 2, ',', ' '));

        $template->setValue('totalWithServicesWithoutVat',
            number_format(((Price::removeVat($total, $vat)) / 100), 2, ',', ' '));
        $template->setValue('totalWithServicesVatCostInWords', (new \NumberFormatter(App::getLocale(),
            \NumberFormatter::SPELLOUT))->format(($positions->sum('total_sum_with_services')) / 100));
        $template->setValue('amountCost', number_format(($positions->sum('total_sum') / 100), 2, ',', ' '));

        $template->setValue('amountCostInWords', (new \NumberFormatter(App::getLocale(),
            \NumberFormatter::SPELLOUT))->format($positions->sum('total_sum') / 100));
        $template->setValue('orderId', $order->internal_number);
        $template->setValue('externalId', $order->external_id);


        $this->setRequisites($template, $order);
        $this->getTotalOrderData($template, $order);
        $file = "{$order->id}_single_act.docx";

        //$this->setComponentData($template, $position);

        $path = config('app.upload_tmp_dir') . "/{$file}";
        $details = $result;
        $name = match ($type) {
            'default_upd_url', 'default_avito_upd' => (($this->options['upd_type'] ?? false)
                ? 'УПД - передаточный документ (акт)'
                : 'УПД - счет-фактура и передаточный документ (акт)'),
            'default_single_act_url' => 'Акт передачи',
            'default_single_application_url' => 'Приложение / Заявка',
            'default_return_single_act_url' => 'Акт возврата',
            'default_single_contract_url' => "Полный договор с приложениями",
            'default_order_claims_url' => "Акт разногласий",
            'default_single_act_services_url' => "Акт оказания услуг",
            default => trans('transbaza_order.common_app')
        };
        $name .= " {$order->internal_number} (";
        $name .= ($withStamp ? 'c печатью' : 'без печати') . ' ' . now($order->company_branch->timezone)->format('d.m.Y H:i') . ')';
        //$docs = $order->documents()->where('name', $name)->where('owner_type', $subContractorId)->get();

        //$docs->each(function ($item) {
        //    $item->delete();
        //});
        if(request()->boolean('preview')) {
            return $template->getResult();
        }
        $docType = match ($type) {
            'default_upd_url' => 'upd',
            default => null
        };
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

            $document = $order->addDocument($name, $result, $subContractorId, type: $docType);

            return Storage::disk()->url($documentPdf['url'] ?? $document['url']);

            //$converter = new OfficeConverter(public_path($path));
            //$converter->convertTo($path);
        }else {
            $template->saveAs(public_path($path));
        }

        if($template instanceof TemplateProcessor) {
            Storage::disk()->put($path, Storage::disk('public_disk')->get($path));
        }

        $documentPdf = $this->generatePdf($file, $path, $name, $order, $subContractorId, $docType, $type === 'default_single_application_url');
        $documentPdf->update([
            'details' => $result
        ]);
        Storage::disk('public_disk')->delete($path);

        $document = $order->addDocument($name, $path, $subContractorId, type: $docType);
        $document->update([
            'details' => $result
        ]);
        return Storage::disk()->url($documentPdf['url']);

        // return Storage::disk()->url($document['url']);
    }

    function generateTemplateData(Order $order, $template)
    {
        $vat = $order->domain->vat;
        $positions = $order->components;
        $k = 0;
        $totalPledge = 0;
        $dotation = 0;
        $preview = \request()->boolean('preview');
        $date = (!empty($this->options['date'])
            ? Carbon::parse($this->options['date'])->format('d.m.Y')
            : now($order->company_branch->timezone)->format('d.m.Y'));

        $time = (!empty($this->options['time'])
            ? Carbon::parse($this->options['time'])->format('H:i:s')
            : now($order->company_branch->timezone)->format('H:i:s'));
        $dateCollection = $order->components->map(function ($v) {
            $v->df = strtotime($v->date_from);
            $v->dt = strtotime($v->date_to);
            return $v;
        });
        foreach ($positions as $position) {
            if ($position->worker_type === WarehousePartSet::class) {
                goto parts;
            }

            $pledge = $position->services->filter(function ($item) {
                return $item->customService->is_pledge;
            })->first();

            $filteredServices = $position->services;
            $totalPledge += $pledge
                ? $pledge->price
                : 0;


            $description = ($position->worker->description);

            $document = new PhpWord();
            $element = $document->addSection();
            Html::addHtml($element, $description);
            $objWriter = IOFactory::createWriter($document, 'Word2007');

// Get all document xml code
            $fullxml = ($objWriter->getWriterPart('Document')->write());
            $fullxml = preg_replace('/^[\s\S]*(<w:body\b.*<\/w:body>).*/', '$1', $fullxml);

            $amount = $position->amount - $position->avito_dotation_sum;
            $amountWithoutVat = Price::removeVat($amount,$vat);

            $costPerUnit = $position->cost_per_unit_doc - $position->avito_dotation_sum;
            $costPerUnitWithoutVat = Price::removeVat($costPerUnit, $vat);

            $result[] = [
                'num' => ++$k,
                'vehicleName' => getMachineryValueByMask(
                    $this->companyBranch->getSettings()->machinery_document_mask, $position->worker,
                    [
                        'address' => $order->address,
                        'attributes' => $position->order->lead->positions->firstWhere('type_id', $position->worker->type)?->category_options?->join(' '),
                        'description' => $position->description,
                        'externalId' => $order->external_id,
                        'createdAt' => $order->created_at?->format('d.m.Y'),
                        'dateFrom' => $position->date_from->format('d.m.Y'),
                        'dateTo' => $position->date_to->format('d.m.Y'),
                        'timeFrom' => $position->date_from->format('H:i'),
                        'timeTo' => $position->date_to->format('H:i'),
                    ]),
                'vehicleNameWithAddress' => "Аренда {$position->worker->name} ({$order->address})",
                'vehicleNameWithAddressDates' => "Аренда {$position->worker->name} ({$order->address}), {$position->date_from?->format('d.m.Y')} - {$position->date_to?->format('d.m.Y')}",
                'vehicleDescription' => $position->description,
                'externalId' => $order->external_id,
                'vehicleComment' => $position->comment,
                'vehicleYear' => $position->worker->year,
                'category' => $position->worker->_type->name ?? '',
                'model' => $position->worker->model->name ?? '',
                'brand' => $position->worker->brand->name ?? '',
                'description' => $preview ? $description : $fullxml,
                'count' => 1,
                'shiftsCount' => $position->is_month ? $position->getInvoiceDuration() : round($position->order_duration),
                'rentType' => $position->order_type === 'shift'
                    ? (
                    $this->companyBranch->getSettings()->getActualShiftName($position->worker->change_hour, $position->is_month)
                    )
                    : trans('units.h'),
                'date' => Carbon::parse($date)->format('d.m.Y'),
                'time' => Carbon::createFromFormat('H:i:s', $time)->format('H:i'),
                'serialNumber' => $position->worker->serial_number,
                'dateFrom' => $position->date_from->format('d.m.Y'),
                'dateTo' => $position->date_to?->format('d.m.Y'),
                'timeFrom' => $position->date_from?->format('H:i'),
                'timeTo' => $position->date_to?->format('H:i'),
                'boardNumber' => $position->worker->board_number,
                'minOrder' => $position->worker->min_order,
                'vehicleMarketPrice' => number_format($position->worker->market_price / 100, 2, ',', ' '),
                'vehicleCostPerUnit' => number_format($costPerUnit / 100, 2, ',', ' '),
                'vehicleCostPerUnitWithoutVat' => number_format($costPerUnitWithoutVat/ 100, 2, ',', ' '),
                'vehicleAmount' => number_format($amount / 100, 2, ',', ' '),
                'vehicleAmountWithoutVat' => number_format($amountWithoutVat / 100, 2, ',', ' '),
                'vehicleAmountVat' => number_format(($amount - $amountWithoutVat) / 100, 2, ',', ' '),
                'vehicleCost' => number_format(($position->total_sum - $position->avito_dotation_sum) / 100, 2, ',', ' '),
                'vehicleCostWithoutVat' => number_format(($position->total_sum_with_services_without_vat - $position->avito_dotation_sum) / 100, 2, ',', ' '),
                'pledge' => number_format(($pledge
                        ? $pledge->price
                        : 0) / 100, 2, ',', ' '),
                'amountVat' => number_format(($costPerUnit - $costPerUnitWithoutVat) / 100, 2, ',', ' '),
                'vehicleVat' => number_format(($position->total_sum_with_services - $position->total_sum_with_services_without_vat) / 100, 2, ',', ' '),
            ];


                if ($position->delivery_cost) {
                    $result[] = [
                        'num' => ++$k,
                        'vehicleName' => 'Доставка',
                        'vehicleNameWithAddress' => 'Доставка',
                        'vehicleNameWithAddressDates' => 'Доставка',
                        'vehicleDescription' => 'Доставка',
                        'externalId' => $order->external_id,
                        'vehicleMarketPrice' => '',
                        'vehicleCostPerUnit' => number_format(($position->delivery_cost / 100), 2, ',', ' '),
                        'description' => '',
                        'vehicleComment' => '',
                        'vehicleAmountVat' => number_format((($position->delivery_cost - $position->delivery_cost_without_vat) / 100), 2, ',', ' '),
                        'vehicleCostPerUnitWithoutVat' => number_format(($position->delivery_cost_without_vat / 100), 2, ',', ' '),
                        'vehicleAmountWithoutVat' => number_format(($position->delivery_cost_without_vat / 100), 2, ',', ' '),
                        'vehicleYear' => '',
                        'model' => '',
                        'date' => '',
                        'time' => '',
                        'count' => '',
                        'rentType' => 'шт.',
                        'minOrder' => '',
                        'dateFrom' => '',
                        'shiftsCount' => 1,
                        'serialNumber' => '',
                        'boardNumber' => '',
                        'pledge' => '',
                        'dateTo' => '',
                        'timeFrom' => '',
                        'timeTo' => '',
                        'vehicleCostWithoutVat' => number_format(($position->delivery_cost_without_vat / 100), 2, ',', ' '),
                        'vehicleVat' => number_format((($position->delivery_cost - $position->delivery_cost_without_vat) / 100), 2, ',', ' '),
                        'vehicleCost' => number_format(($position->delivery_cost / 100), 2, ',', ' '),
                        'vehicleAmount' => number_format(($position->delivery_cost / 100), 2, ',', ' '),
                    ];
                }
                if ($position->return_delivery) {

                    $result[] = [
                        'num' => ++$k,
                        'vehicleName' => 'Обратная доставка',
                        'vehicleNameWithAddress' => 'Обратная доставка',
                        'vehicleNameWithAddressDates' => 'Обратная доставка',
                        'vehicleComment' => '',
                        'model' => '',
                        'externalId' => $order->external_id,
                        'vehicleDescription' => 'Обратная доставка',
                        'vehicleMarketPrice' => '',
                        'vehicleCostPerUnitWithoutVat' => number_format(($position->return_delivery_without_vat / 100), 2, ',', ' '),
                        'vehicleAmountVat' => number_format((($position->return_delivery - $position->return_delivery_without_vat) / 100), 2, ',', ' '),
                        'vehicleAmountWithoutVat' => number_format(($position->return_delivery_without_vat / 100), 2, ',', ' '),
                        'vehicleYear' => '',
                        'description' => '',
                        'date' => '',
                        'time' => '',
                        'vehicleCostPerUnit' => number_format(($position->return_delivery / 100), 2, ',', ' '),
                        'rentType' => 'шт.',
                        'minOrder' => '',
                        'dateFrom' => '',
                        'pledge' => '',
                        'shiftsCount' => 1,
                        'count' => '',
                        'serialNumber' => '',
                        'boardNumber' => '',
                        'amountVat' => '',
                        'dateTo' => '',
                        'timeFrom' => '',
                        'timeTo' => '',
                        'vehicleCostWithoutVat' => number_format(($position->return_delivery_without_vat / 100), 2, ',', ' '),
                        'vehicleVat' => number_format((($position->return_delivery - $position->return_delivery_without_vat) / 100), 2, ',', ' '),
                        'vehicleCost' => number_format(($position->return_delivery / 100), 2, ',', ' '),
                        'vehicleAmount' => number_format(($position->return_delivery / 100), 2, ',', ' '),
                    ];
                }
            //    if ($type !== 'default_single_act_url') {
            /** @var OrderComponentService $service */
            foreach ($filteredServices as $service) {
                $serviceCostPerUnit = $service->price_doc / 100;
                $serviceCost = ($service->price_doc) * $service->count / 100;
                $result[] = [
                    'num' => ++$k,
                    'vehicleName' => $service->name,
                    'vehicleNameWithAddress' => $service->name,
                    'vehicleNameWithAddressDates' => $service->name,
                    'vehicleDescription' => $service->name,
                    'vehicleComment' => '',
                    'vehicleMarketPrice' => '',
                    'vehicleCostPerUnitWithoutVat' => '',
                    'vehicleAmountWithoutVat' => '',
                    'description' => '',
                    'vehicleYear' => '',
                    'externalId' => $order->external_id,
                    'date' => '',
                    'time' => '',
                    'model' => '',
                    'rentType' => $service->customService->unit->name ?? '',
                    'minOrder' => '',
                    'pledge' => '',
                    'shiftsCount' => $service->count,
                    'serialNumber' => '',
                    'boardNumber' => '',
                    'amountVat' => '',
                    'dateFrom' => '',
                    'count' => '',
                    'dateTo' => '',
                    'timeFrom' => '',
                    'timeTo' => '',
                    'vehicleCostWithoutVat' => number_format(Price::removeVat($serviceCost, $vat), 2, ',', ' '),
                    'vehicleVat' => number_format($serviceCost - Price::removeVat($serviceCost, $vat), 2, ',', ' '),
                    'vehicleCostPerUnit' => number_format($serviceCostPerUnit, 2, ',', ' '),
                    'vehicleCost' => number_format($serviceCost, 2, ',', ' '),
                    'vehicleAmount' => number_format($serviceCost, 2, ',', ' '),
                ];
            }
            // }
            parts:
            $parts = $this->getRentPartsTable($position, ($inputPositions[$position->id] ?? null) ? ($inputPositions[$position->id]['parts'] ?? []) : []);
            if ($parts) {
                $period = "{$position->date_from->format('d.m.Y')} {$position->date_to->format('d.m.Y')}";
                foreach ($parts as $part) {
                    $result[] = [
                        'num' => ++$k,//$part['partNum'],
                        'vehicleName' => $part['partName'] . "({$part['partCount']} штук.) ({$period})",
                        'vehicleNameWithAddress' => $part['partName'] . "({$part['partCount']} штук.) ({$period})",
                        'vehicleNameWithAddressDates' => $part['partName'] . "({$part['partCount']} штук.) ({$period})",
                        'vehicleDescription' => $part['partName'],
                        'vehicleComment' => '',
                        'vehicleMarketPrice' => '',
                        'vehicleYear' => '',
                        'externalId' => $order->external_id,
                        'description' => '',
                        'model' => '',
                        'date' => '',
                        'time' => '',
                        'rentType' => $position->order_type === 'shift'
                            ? (
                            $this->companyBranch->getSettings()->getActualShiftName($position->worker->change_hour, $position->is_month)
                            )
                            : trans('units.h'),
                        'minOrder' => '',
                        'pledge' => '',
                        'vehicleCostPerUnitWithoutVat' => '',
                        'vehicleAmountWithoutVat' => '',
                        'shiftsCount' => $part['partDuration'],
                        'count' => $part['partCount'],
                        'serialNumber' => '',
                        'boardNumber' => '',
                        'amountVat' => '',
                        'dateFrom' => $part['dateFrom'] ?? '',
                        'dateTo' => $part['dateTo'] ?? '',
                        'timeFrom' => $part['timeFrom'] ?? '',
                        'timeTo' => $part['timeTo'] ?? '',
                        'vehicleCostWithoutVat' => $part['partCostWithoutVat'],
                        'vehicleVat' => '',
                        'vehicleCostPerUnit' => $part['partCost'],
                        'vehicleCost' => $part['partCostAmount'],
                        'vehicleAmount' => $part['partCostAmount'],
                    ];
                    if ($position->delivery_cost) {
                        $result[] = [
                            'num' => ++$k,
                            'vehicleName' => 'Доставка',
                            'vehicleNameWithAddress' => 'Доставка',
                            'vehicleNameWithAddressDates' => 'Доставка',
                            'vehicleDescription' => 'Доставка',
                            'externalId' => $order->external_id,
                            'vehicleMarketPrice' => '',
                            'vehicleCostPerUnit' => number_format(($position->delivery_cost / 100), 2, ',', ' '),
                            'description' => '',
                            'vehicleComment' => '',
                            'vehicleAmountVat' => number_format((($position->delivery_cost - $position->delivery_cost_without_vat) / 100), 2, ',', ' '),
                            'vehicleCostPerUnitWithoutVat' => number_format(($position->delivery_cost_without_vat / 100), 2, ',', ' '),
                            'vehicleAmountWithoutVat' => number_format(($position->delivery_cost_without_vat / 100), 2, ',', ' '),
                            'vehicleYear' => '',
                            'model' => '',
                            'date' => '',
                            'time' => '',
                            'count' => '',
                            'rentType' => 'шт.',
                            'minOrder' => '',
                            'dateFrom' => '',
                            'shiftsCount' => 1,
                            'serialNumber' => '',
                            'boardNumber' => '',
                            'pledge' => '',
                            'dateTo' => '',
                            'timeFrom' => '',
                            'timeTo' => '',
                            'vehicleCostWithoutVat' => number_format(($position->delivery_cost_without_vat / 100), 2, ',', ' '),
                            'vehicleVat' => number_format((($position->delivery_cost - $position->delivery_cost_without_vat) / 100), 2, ',', ' '),
                            'vehicleCost' => number_format(($position->delivery_cost / 100), 2, ',', ' '),
                            'vehicleAmount' => number_format(($position->delivery_cost / 100), 2, ',', ' '),
                        ];
                    }
                    if ($position->return_delivery) {

                        $result[] = [
                            'num' => ++$k,
                            'vehicleName' => 'Обратная доставка',
                            'vehicleNameWithAddress' => 'Обратная доставка',
                            'vehicleNameWithAddressDates' => 'Обратная доставка',
                            'vehicleComment' => '',
                            'model' => '',
                            'externalId' => $order->external_id,
                            'vehicleDescription' => 'Обратная доставка',
                            'vehicleMarketPrice' => '',
                            'vehicleCostPerUnitWithoutVat' => number_format(($position->return_delivery_without_vat / 100), 2, ',', ' '),
                            'vehicleAmountVat' => number_format((($position->return_delivery - $position->return_delivery_without_vat) / 100), 2, ',', ' '),
                            'vehicleAmountWithoutVat' => number_format(($position->return_delivery_without_vat / 100), 2, ',', ' '),
                            'vehicleYear' => '',
                            'description' => '',
                            'date' => '',
                            'time' => '',
                            'vehicleCostPerUnit' => number_format(($position->return_delivery / 100), 2, ',', ' '),
                            'rentType' => 'шт.',
                            'minOrder' => '',
                            'dateFrom' => '',
                            'pledge' => '',
                            'shiftsCount' => 1,
                            'count' => '',
                            'serialNumber' => '',
                            'boardNumber' => '',
                            'amountVat' => '',
                            'dateTo' => '',
                            'timeFrom' => '',
                            'timeTo' => '',
                            'vehicleCostWithoutVat' => number_format(($position->return_delivery_without_vat / 100), 2, ',', ' '),
                            'vehicleVat' => number_format((($position->return_delivery - $position->return_delivery_without_vat) / 100), 2, ',', ' '),
                            'vehicleCost' => number_format(($position->return_delivery / 100), 2, ',', ' '),
                            'vehicleAmount' => number_format(($position->return_delivery / 100), 2, ',', ' '),
                        ];
                    }
                }
            }
        }
        while (true) {
            try {
                $template->cloneRowAndSetValues('vehicleName', $result);
                break;
            } catch (\Exception $exception) {
                try {
                    $template->cloneRowAndSetValues('vehicleNameWithAddress', $result);
                } catch (\Exception $exception) {
                    try {
                        $template->cloneRowAndSetValues('num', $result);
                    } catch (\Exception $exception) {
                        break;
                    }
                    break;
                }
                break;
            }
        }
        while (true) {
            try {
                $template->cloneRowAndSetValues('vehicleDescription', $result);
            } catch (\Exception $exception) {
                break;
            }
        }
        if($template instanceof TemplateProcessor) {
            try {

                $this->setMachineriesList($positions->pluck('worker')->filter(fn($worker) => $worker::class === Machinery::class), $template);
                $template->setComplexBlock('{tariffsTable}', $this->generateTariffsTable($positions->pluck('worker')->filter(fn($worker) => $worker::class === Machinery::class), true));
            } catch (\Exception $e) {

            }
        }

        $position = $positions->first();

        if ($position) {
            $template->setValue('vehicleBase', htmlspecialchars($position->worker->base->name ?? ''));
            $template->setValue('vehicleBaseAddress', htmlspecialchars($position->worker->base->address ?? ''));
            $template->setValue('baseKpp', htmlspecialchars($position->worker->base->kpp ?? ''));
        }


        $template->setValue('rowsCount', count($result));
        $template->setValue('totalMarketPrice', number_format($positions->sum('worker.market_price') / 100, 2, ',', ' '));
        $template->setValue('itemsCount', $positions->unique('machinery_id')->count());
        $template->setValue('totalPledge', number_format($totalPledge / 100, 2, ',', ' '));
        $template->setValue('dateFrom', Carbon::createFromTimestamp($dateCollection->min('df'))->format('d.m.Y'));
        $template->setValue('timeFrom', Carbon::createFromTimestamp($dateCollection->min('df'))->format('H:i'));
        $template->setValue('dateTo', Carbon::createFromTimestamp($dateCollection->max('dt'))->format('d.m.Y'));
        $template->setValue('timeTo', Carbon::createFromTimestamp($dateCollection->max('dt'))->format('H:i'));

        $template->setValue('d', now()->format('d'));
        $template->setValue('m', now()->format('m'));
        $template->setValue('y', now()->format('Y'));


        $template->setValue('totalWithServicesWithoutPledge',
            number_format((($positions->sum('total_sum_with_services_without_pledge') - $dotation) / 100), 2, ',', ' '));
        $template->setValue('totalWithServicesWithoutPledgeWithoutVat',
            number_format(((Price::removeVat($positions->sum('total_sum_with_services_without_pledge_without_vat'), $vat) - $dotation) / 100), 2, ',', ' '));

        $total = $positions->sum('total_sum_with_services') - $dotation;
        $template->setValue('totalWithServicesVat',
            number_format(($total / 100), 2, ',', ' '));

        $template->setValue('totalWithServicesOnlyVat',
            number_format((($positions->sum(fn($item) => $total - Price::removeVat($total, $vat))) / 100), 2, ',', ' '));

        $template->setValue('totalWithServicesWithoutVat',
            number_format(((Price::removeVat($total, $vat)) / 100), 2, ',', ' '));
        $template->setValue('totalWithServicesVatCostInWords', (new \NumberFormatter(App::getLocale(),
            \NumberFormatter::SPELLOUT))->format(($positions->sum('total_sum_with_services')) / 100));
        $template->setValue('amountCost', number_format(($positions->sum('total_sum') / 100), 2, ',', ' '));

        $template->setValue('amountCostInWords', (new \NumberFormatter(App::getLocale(),
            \NumberFormatter::SPELLOUT))->format($positions->sum('total_sum') / 100));
        $template->setValue('orderId', $order->internal_number);
        $template->setValue('externalId', $order->external_id);
    }

    function formAcceptance(Order $order)
    {

        $position = $order->components()->findOrFail($this->options['position_id']);
        $subContractorId = $this->options['subContractorId'] ?? null;

        if ($subContractorId) {
            $position->setSubContractorCalculation(true);
        }
        $subContractorId = $this->options['subContractorId'] ?? null;

        $date = (!empty($this->options['date'])
            ? Carbon::parse($this->options['date'])->format('Y-m-d')
            : now()->format('Y-m-d'));

        $time = !empty($this->options['time'])
            ? Carbon::parse($this->options['time'])->format('H:i')
            : now()->format('H:i');

        MachineryStamp::createTimestamp($position->worker->id, $order->id, 'arrival', "{$date} {$time}:00",
            $order->coordinates);


        $document_with_table = new PhpWord();
        $section = $document_with_table->addSection();
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '333',
        ]);

        $table->addRow();
        $table->addCell(1750)->addText(trans('transbaza_machine_edit.equipment'));
        $table->addCell(1750)->addText(trans('contractors/edit.serial_number'));
        $table->addCell(1750)->addText(trans('contractors/edit.tail_number'));
        $table->addCell(1750)->addText(trans('transbaza_machine_edit.first_rent_day'));
        $table->addCell(1750)->addText(trans('transbaza_machine_edit.running_time'));

        $name =
            ($position->worker->brand
                ? $position->worker->brand->name
                : $position->worker->_type->name ?? '') . ($position->worker->model
                ? " " . ($position->worker->model->name ?? '')
                : '');

        $table->addRow();
        $table->addCell()->addText($name);
        $table->addCell()->addText($position->worker->vin
            ?: $position->worker->serial_number);
        $table->addCell()->addText($position->worker->board_number);
        $table->addCell()->addText($position->date_from->format('d.m.Y'));
        $table->addCell()->addText('');


// Create writer to convert document to xml
        $objWriter = IOFactory::createWriter($document_with_table, 'Word2007');

// Get all document xml code
        $fullxml = $objWriter->getWriterPart('Document')->write();

// Get only table xml code
        $tablexml = preg_replace('/^[\s\S]*(<w:tbl\b.*<\/w:tbl>).*/', '$1', $fullxml);

        try {

            $documentsPack =
                $this->documentsPack
                    ?: $order->lead->documentsPack;
            $type =
                $this->withStamp
                    ? 'default_acceptance_act_url_with_stamp'
                    : 'default_acceptance_act_url';

            $url =
                ($documentsPack && $documentsPack->{$type})
                    ? Storage::disk()->url($documentsPack->{$type})
                    : public_path('documents/acceptance_report.docx');
            $template = new TemplateProcessor($url);

        } catch (\Exception $exception) {
            $error = ValidationException::withMessages([
                'errors' => [trans('transbaza_validation.docx_error')]
            ]);

            throw $error;
        }


        $partsTable = $this->getRentPartsTable($position);
        if ($partsTable) {
            try {
                $template->cloneRowAndSetValues('partNum', $partsTable);
            } catch (\Exception $exception) {

            }
        }
        $template->setValue('table', $tablexml);

        $this->setRequisites($template, $order);
        $template->setValue('applicationId', $position->application_id);
        $file = "{$position->id}_acceptance_report.docx";

        $this->setComponentData($template, $position);

        $path = config('app.upload_tmp_dir') . "/{$file}";

        $name = trans('contractors/edit.input_act') . " {$position->application_id} {$order->customer->company_name}";

        $docs = $order->documents()->where('name', $name)
            ->where('owner_type', $subContractorId)
            ->get();

        $docs->each(function ($item) {
            $item->delete();
        });

        $template->saveAs(public_path($path));

        Storage::disk()->put($path, Storage::disk('public_disk')->get($path));

        Storage::disk('public_disk')->delete($path);

        $document = $order->addDocument($name, $path, $subContractorId);

        return Storage::disk()->url($document['url']);
    }

    /**
     * @param TemplateProcessor $template
     * @param OrderComponent $orderComponent
     * @return OrderDocumentService
     */
    function setComponentData(
        $template,
        $orderComponent
    )
    {
        $orderComponent->getContractorRequisite();

        $position = $orderComponent;

        $pledge = $position->services->filter(function ($item) {
            return $item->customService->is_pledge;
        })->first();
        $pledge = $pledge
            ? $pledge->price
            : 0;

        $description = $position->worker->description;

        $document = new PhpWord();
        $element = $document->addSection();
        Html::addHtml($element, $description);
        $objWriter = IOFactory::createWriter($document);

// Get all document xml code
        $fullxml = ($objWriter->getWriterPart('Document')->write());
        $fullxml = preg_replace('/^[\s\S]*(<w:body\b.*<\/w:body>).*/', '$1', $fullxml);

        $template->setValue('description', $fullxml);

        $template->setValue('orderId', $orderComponent->order_id);
        $template->setValue('externalId', $orderComponent->external_id);
        $template->setValue('pledge', number_format($pledge / 100, 2, ',', ' '));
        $template->setValue('vehicleMarketPrice',
            number_format($orderComponent->worker->market_price / 100, 2, ',', ' '));
        $template->setValue('vehicleMarketCurrency',
            (Price::getPriceNames()[$orderComponent->worker->market_price_currency] ?? ''));
        $template->setValue('vehicleMarketPriceInWords', (new \NumberFormatter("ru",
            \NumberFormatter::SPELLOUT))->format($orderComponent->worker->market_price / 100));

        $template->setValue('d', now()->format('d'));
        $template->setValue('m', now()->format('m'));
        $template->setValue('y', now()->format('Y'));

        $template->setValue('vehicleName', $position->worker instanceof Machinery
            ? getMachineryValueByMask($this->companyBranch->getSettings()->machinery_document_mask, $position->worker, [
                'address' => $position->order?->address,
                'attributes' => $position->order?->lead->positions->firstWhere('type_id', $position->worker->type)?->category_options?->join(' '),
                'timeFrom' => $position->date_from->format('H:i'),
                'timeTo' => $position->date_to->format('H:i'),
            ])
            : $position->worker->name);
        $template->setValue('vehicleShiftDuration', htmlspecialchars($position->worker->change_hour));
        $template->setValue('vehicleBase', htmlspecialchars($position->worker->base->name ?? ''));
        $template->setValue('vehicleBaseAddress', htmlspecialchars($position->worker->base->address ?? ''));
        $template->setValue('baseKpp', htmlspecialchars($position->worker->base->kpp ?? ''));
        $template->setValue('serialNumber', htmlspecialchars($position->worker->serial_number));
        $template->setValue('boardNumber', htmlspecialchars($position->worker->board_number));
        $template->setValue('dateFrom', $position->date_from->format('d.m.Y'));
        $template->setValue('dateTo', $position->date_to->format('d.m.Y'));
        $template->setValue('timeFrom', $position->date_from->format('H:i'));
        $template->setValue('timeTo', $position->date_to->format('H:i'));
        $template->setValue('orderType', $position->order_type === 'shift'
            ? 'Смен'
            : 'Час');
        $template->setValue('shiftsCount', round($position->order_duration));
        $template->setValue('shiftsCountInWords',
            (new \NumberFormatter(App::getLocale(), \NumberFormatter::SPELLOUT))->format(($position->order_duration)));
        $template->setValue('category', $position->worker->_type->name ?? '');
        $template->setValue('brand', ($position->worker->brand ?? null)
            ? $position->worker->brand->name
            : '');
        $template->setValue('model', ($position->worker->model ?? null)
            ? $position->worker->model->name
            : '');

        $template->setValue('vehicleCostPerUnit', number_format(($position->cost_per_unit_doc) / 100, 2, ',', ' '));
        $template->setValue('vehicleCostPerUnitInWords', (new \NumberFormatter(App::getLocale(),
            \NumberFormatter::SPELLOUT))->format((($position->cost_per_unit_doc) / 100)));

        $template->setValue('vehicleCostPerUnitVat', number_format(($position->cost_per_unit_doc - $position->cost_per_unit_without_vat) / 100, 2, ',', ' '));
        $template->setValue('vehicleCostPerUnitVatInWords', (new \NumberFormatter(App::getLocale(),
            \NumberFormatter::SPELLOUT))->format((($position->cost_per_unit_doc - $position->cost_per_unit_without_vat) / 100)));

        $template->setValue('vehicleCostPerUnitWithoutVat',
            ($position->cost_per_unit_without_vat + $position->value_added_without_vat) / 100);
        $template->setValue('vehicleCostPerUnitWithoutVatInWords', (new \NumberFormatter(App::getLocale(),
            \NumberFormatter::SPELLOUT))->format((($position->cost_per_unit_without_vat + $position->value_added_without_vat) / 100)));

        $template->setValue('vehicleCost', number_format($position->amount / 100, 2, ',', ' '));
        $template->setValue('vehicleCostInWords',
            (new \NumberFormatter(App::getLocale(), \NumberFormatter::SPELLOUT))->format(($position->amount / 100)));

        $vehicleCostVat = round(Price::getVat($position->amount, $orderComponent->company_branch->domain->vat) / 100);
        $template->setValue('vehicleCostVat', number_format($vehicleCostVat, 2, ',', ' '));
        $template->setValue('vehicleCostVatInWords',
            (new \NumberFormatter(App::getLocale(), \NumberFormatter::SPELLOUT))->format($vehicleCostVat));

        $template->setValue('vehicleCostWithoutVat', number_format($position->amount_without_vat / 100, 2, ',', ' '));
        $template->setValue('vehicleCostWithoutVatInWords', (new \NumberFormatter(App::getLocale(),
            \NumberFormatter::SPELLOUT))->format(($position->amount_without_vat / 100)));


        $template->setValue('deliveryCost', number_format(($position->delivery_cost / 100), 2, ',', ' '));
        $template->setValue('deliveryCostInWords', (new \NumberFormatter(App::getLocale(),
            \NumberFormatter::SPELLOUT))->format($position->delivery_cost / 100));

        $template->setValue('deliveryCostVat',
            number_format((Price::getVat($position->delivery_cost, $orderComponent->company_branch->domain->vat) / 100),
                2, ',', ' '));
        $template->setValue('deliveryCostVatInWords', (new \NumberFormatter(App::getLocale(),
            \NumberFormatter::SPELLOUT))->format((Price::getVat($position->delivery_cost,
                $orderComponent->company_branch->domain->vat)) / 100));


        $template->setValue('deliveryCostWithoutVat',
            number_format(($position->delivery_cost_without_vat / 100), 2, ',', ' '));
        $template->setValue('deliveryCostWithoutVatInWords', (new \NumberFormatter(App::getLocale(),
            \NumberFormatter::SPELLOUT))->format($position->delivery_cost_without_vat / 100));


        $template->setValue('returnDeliveryCost', number_format(($position->return_delivery / 100), 2, ',', ' '));
        $template->setValue('returnDeliveryCostInWords', (new \NumberFormatter(App::getLocale(),
            \NumberFormatter::SPELLOUT))->format($position->return_delivery / 100));

        $template->setValue('returnDeliveryCostVat', number_format((Price::getVat($position->return_delivery,
                $orderComponent->company_branch->domain->vat) / 100), 2, ',', ' '));
        $template->setValue('returnDeliveryCostVatInWords', (new \NumberFormatter(App::getLocale(),
            \NumberFormatter::SPELLOUT))->format(Price::getVat($position->return_delivery,
                $orderComponent->company_branch->domain->vat) / 100));

        $template->setValue('returnDeliveryCostWithoutVat',
            number_format(($position->return_delivery_without_vat / 100), 2, ',', ' '));
        $template->setValue('returnDeliveryCostWithoutVatInWords', (new \NumberFormatter(App::getLocale(),
            \NumberFormatter::SPELLOUT))->format($position->return_delivery_without_vat / 100));

        $template->setValue('totalDeliveryCost',
            number_format((($position->return_delivery + $position->delivery_cost) / 100), 2, ',', ' '));
        $template->setValue('totalDeliveryCostInWords', (new \NumberFormatter(App::getLocale(),
            \NumberFormatter::SPELLOUT))->format(($position->return_delivery + $position->delivery_cost) / 100));

        $template->setValue('totalDeliveryCostVat',
            number_format((Price::getVat($position->return_delivery + $position->delivery_cost,
                    $orderComponent->company_branch->domain->vat) / 100), 2, ',', ' '));
        $template->setValue('totalDeliveryCostVatInWords', (new \NumberFormatter(App::getLocale(),
            \NumberFormatter::SPELLOUT))->format(Price::getVat($position->return_delivery + $position->delivery_cost,
                $orderComponent->company_branch->domain->vat) / 100));

        $template->setValue('amountCost', number_format(($position->total_sum / 100), 2, ',', ' '));
        $template->setValue('amountCostInWords',
            (new \NumberFormatter(App::getLocale(), \NumberFormatter::SPELLOUT))->format($position->total_sum / 100));

        $template->setValue('amountVat',
            number_format((($position->total_sum_with_services - $position->total_sum_with_services_without_vat) / 100),
                2, ',', ' '));
        $template->setValue('amountVatCostInWords', (new \NumberFormatter(App::getLocale(),
            \NumberFormatter::SPELLOUT))->format(($position->total_sum_with_services - $position->total_sum_with_services_without_vat) / 100));

        $template->setValue('totalWithServicesVat',
            number_format((($position->total_sum_with_services) / 100), 2, ',', ' '));
        $template->setValue('totalWithServicesVatCostInWords', (new \NumberFormatter(App::getLocale(),
            \NumberFormatter::SPELLOUT))->format(($position->total_sum_with_services) / 100));

        $template->setValue('amountCostWithoutVat',
            number_format(($position->total_sum_without_vat / 100), 2, ',', ' '));
        $template->setValue('amountCostInWordsWithoutVat', (new \NumberFormatter(App::getLocale(),
            \NumberFormatter::SPELLOUT))->format($position->total_sum_without_vat / 100));

        $template->setValue('vat',
            $position->order->contractorRequisite && $position->order->contractorRequisite->vat_system === Price::TYPE_CASHLESS_VAT
                ? $position->worker->company_branch->domain->vat
                : 0);


        return $this;
    }

    function getTotalOrderData(
        $template,
        Order $order,
        $isSet = false
    )
    {

        $subContractorId = $this->options['subContractorId'] ?? null;
        $vat = $order->domain->vat;
        if ($subContractorId) {
            $order->components->each->setSubContractorCalculation(true);
        }
        $template->setValue('externalId', $order->external_id);
        $template->setValue('comment', $order->comment);
        $template->setValue('objectName', $order->lead->object_name);
        $template->setValue('shiftsCount', round($order->components->first()->order_duration));
        $template->setValue('servicesCost',
            number_format(($order->components->sum('services_sum') / 100), 2, ',', ' '));

        $template->setValue('deliveryCost',
            number_format(($order->components->sum('delivery_cost') / 100), 2, ',', ' '));
        $template->setValue('deliveryCostInWords', (new \NumberFormatter(App::getLocale(),
            \NumberFormatter::SPELLOUT))->format($order->components->sum('delivery_cost') / 100));

        $template->setValue('deliveryCostVat', number_format((Price::getVat($order->components->sum('delivery_cost'),
                $order->company_branch->domain->vat) / 100), 2, ',', ' '));
        $template->setValue('deliveryCostVatInWords', (new \NumberFormatter(App::getLocale(),
            \NumberFormatter::SPELLOUT))->format((Price::getVat($order->components->sum('delivery_cost'),
                $order->company_branch->domain->vat)) / 100));

        $template->setValue('sumAmountVat',
            number_format(($order->amount - Price::removeVat($order->amount, $vat)) / 100, 2, ',', ' '), 2, ',', ' ');

        $template->setValue('deliveryCostWithoutVat',
            number_format(($order->components->sum('delivery_cost_without_vat') / 100), 2, ',', ' '));
        $template->setValue('deliveryCostWithoutVatInWords', (new \NumberFormatter(App::getLocale(),
            \NumberFormatter::SPELLOUT))->format($order->components->sum('delivery_cost_without_vat') / 100));


        $template->setValue('returnDeliveryCost',
            number_format(($order->components->sum('return_delivery') / 100), 2, ',', ' '));
        $template->setValue('vehicleAmount',
            number_format(($order->components->sum('amount') / 100), 2, ',', ' '));

        $template->setValue('vehicleAmountWithoutVat',
            number_format(($order->components->sum('amount_without_vat') / 100), 2, ',', ' '));

        $template->setValue('vehicleAmountVat',
            number_format(($order->components->sum(fn($item) => $item->amount - $item->amount_without_vat) / 100), 2, ',', ' '));

        $template->setValue('returnDeliveryCostInWords', (new \NumberFormatter(App::getLocale(),
            \NumberFormatter::SPELLOUT))->format($order->components->sum('return_delivery') / 100));

        $template->setValue('returnDeliveryCostVat',
            number_format((Price::getVat($order->components->sum('return_delivery'),
                    $order->company_branch->domain->vat) / 100), 2, ',', ' '));
        $template->setValue('returnDeliveryCostVatInWords', (new \NumberFormatter(App::getLocale(),
            \NumberFormatter::SPELLOUT))->format(Price::getVat($order->components->sum('return_delivery'),
                $order->company_branch->domain->vat) / 100));

        $template->setValue('returnDeliveryCostWithoutVat',
            number_format(($order->components->sum('return_delivery_without_vat') / 100), 2, ',', ' '));
        $template->setValue('returnDeliveryCostWithoutVatInWords', (new \NumberFormatter(App::getLocale(),
            \NumberFormatter::SPELLOUT))->format($order->components->sum('return_delivery_without_vat') / 100));

        $template->setValue('totalDeliveryCost',
            number_format((($order->components->sum('return_delivery') + $order->components->sum('delivery_cost')) / 100),
                2, ',', ' '));
        $template->setValue('totalDeliveryCostInWords', (new \NumberFormatter(App::getLocale(),
            \NumberFormatter::SPELLOUT))->format(($order->components->sum('return_delivery') + $order->components->sum('delivery_cost')) / 100));

        $template->setValue('totalDeliveryCostVat',
            number_format((Price::getVat($order->components->sum('return_delivery') + $order->components->sum('delivery_cost'),
                    $order->company_branch->domain->vat) / 100), 2, ',', ' '));
        $template->setValue('totalDeliveryCostVatInWords', (new \NumberFormatter(App::getLocale(),
            \NumberFormatter::SPELLOUT))->format(Price::getVat($order->components->sum('return_delivery') + $order->components->sum('delivery_cost'),
                $order->company_branch->domain->vat) / 100));

        $template->setValue('amountCost', number_format(($order->components->sum('total_sum') / 100), 2, ',', ' '));
        $template->setValue('amountCostInWords', (new \NumberFormatter(App::getLocale(),
            \NumberFormatter::SPELLOUT))->format($order->components->sum('total_sum') / 100));

        $template->setValue('amountVat',
            number_format((($order->components->sum('total_sum_with_services') - $order->components->sum('total_sum_with_services_without_vat')) / 100),
                2, ',', ' '));
        $template->setValue('amountVatCostInWords', (new \NumberFormatter(App::getLocale(),
            \NumberFormatter::SPELLOUT))->format(($order->components->sum('total_sum_with_services') - $order->components->sum('total_sum_with_services_without_vat')) / 100));


        $template->setValue('totalWithServicesVat', number_format((($isSet
                ? $order->machinerySets->sum('prices.sum')
                : $order->components->sum('total_sum_with_services')) / 100), 2, ',', ' '));
        $template->setValue('totalWithServicesVatCostInWords', (new \NumberFormatter(App::getLocale(),
            \NumberFormatter::SPELLOUT))->format(($order->components->sum('total_sum_with_services')) / 100));

        $template->setValue('amountCostWithoutVat',
            number_format(($order->components->sum('total_sum_without_vat') / 100), 2, ',', ' '));
        $template->setValue('amountCostInWordsWithoutVat', (new \NumberFormatter(App::getLocale(),
            \NumberFormatter::SPELLOUT))->format($order->components->sum('total_sum_without_vat') / 100));


        $template->setValue('amountCostPerMonth', number_format(($order->components->sum('cost_per_unit') * 30 / 100), 2, ',', ' '));
        $template->setValue('amountCostInWordsPerMonth', (new \NumberFormatter(App::getLocale(),
            \NumberFormatter::SPELLOUT))->format($order->components->sum('cost_per_unit') * 30 / 100));

        $template->setValue('amountVatPerMonth',
            number_format(((($order->components->sum(fn($component) => $component->cost_per_unit * 30 / 100) - $order->components->sum('cost_per_unit')) * 30) / 100),
                2, ',', ' '));
        $template->setValue('amountVatCostInWordsPerMonth', (new \NumberFormatter(App::getLocale(),
            \NumberFormatter::SPELLOUT))->format(($order->components->sum('total_sum_with_services') - $order->components->sum('total_sum_with_services_without_vat')) / 30 / 100));


        $template->setValue('totalWithServicesVatPerMonth', number_format((($isSet
            ? $order->machinerySets->sum('prices.sum')
            : $order->components->sum(fn($component) => $component->cost_per_unit * 30 / 100))), 2, ',', ' '));
        $template->setValue('totalWithServicesVatCostInWordsPerMonth', (new \NumberFormatter(App::getLocale(),
            \NumberFormatter::SPELLOUT))->format(($order->components->sum('total_sum_with_services')) / 30 / 100));

        $template->setValue('amountCostWithoutVatPerMonth',
            number_format(($order->components->sum('total_sum_without_vat') / 30 / 100), 2, ',', ' '));
        $template->setValue('amountCostInWordsWithoutVatPerMonth', (new \NumberFormatter(App::getLocale(),
            \NumberFormatter::SPELLOUT))->format($order->components->sum('total_sum_without_vat') / 30 / 100));

    }

    public function getRentPartsTable(OrderComponent $component, $filter = null)
    {
        if ($component->worker_type !== WarehousePartSet::class) {
            return null;
        }

        $parts = [];

        $vat = $component->order->domain->vat;

        foreach ($component->rent_parts->where('type', 'rent') as $i => $rent_part) {
            if ($filter && !in_array($rent_part->id, $filter)) {
                return;
            }
            $part = $rent_part->company_branches_warehouse_part->part;

            $costPerUnit = $rent_part->cost_per_unit / 100;
            $costPerUnit = Price::addVat($costPerUnit,
                ($component->getContractorRequisite() && $component->getContractorRequisite()->vat_system === Price::TYPE_CASHLESS_VAT
                    ? $component->company_branch->domain->vat
                    : 0));

            $saleCost = $rent_part?->company_branches_warehouse_part?->default_sale_cost / 100 ?: 0;

            $parts[] = [
                'partNum' => ++$i,
                'partSaleCost' =>  number_format($saleCost, 2, ',', ' '),
                'vehicleAmountSale' => number_format(($saleCost * $rent_part->count), 2, ',', ' '),
                'partName' => $rent_part->company_branches_warehouse_part->name
                    ?: $part->name,
                'partCount' => $rent_part->count,
                'partDuration' => $component->order_duration,
                'partUnit' => $part->unit->name ?? '',
                'dateFrom' => $component->date_from->format('d.m.Y') ?? '',
                'dateTo' => $component->date_to->format('d.m.Y') ?? '',
                'timeFrom' => $component->date_from->format('H:i') ?? '',
                'timeTo' => $component->date_to->format('H:i') ?? '',
                'partCost' => number_format($costPerUnit, 2, ',', ''),
                'partCostWithoutVat' => number_format(
                    Price::removeVat($costPerUnit, $vat)
                    , 2, ',', ''),
                'partCostAmount' => number_format($costPerUnit * $rent_part->count * $component->order_duration, 2, ',', ' '),
            ];

        }

        return $parts;
    }

    function generateServiceContract(ServiceCenter $serviceCenter)
    {
        $template = $this->getServiceTemplate('default_service_contract_url', $serviceCenter);

        if(request()->boolean('preview')) {
            return $template->getResult();
        }

        $file = "{$serviceCenter->id}_service_contract.docx";

        $path = config('app.upload_tmp_dir') . "/{$file}";

        $name =
            'Договор сервиса ' . $serviceCenter->customer->company_name;

        return  $this->saveServiceDoc($serviceCenter, $template, $name, $path);
    }

    private function saveServiceDoc(ServiceCenter $serviceCenter, $template, $name, $path)
    {
        $file = last(explode('/', $name));

        if(\request()->input('preview_data')) {
            $converter = new DocumentConverter();
            $converter->setData(
                $name,
                \request()->input('preview_data')
            );

            $result = $converter->generatePdf();
            Storage::disk()->put($result, Storage::disk('local')->get($result));

            $document = $serviceCenter->addDocument($name, $result);

            return Storage::disk()->url($documentPdf['url'] ?? $document['url']);

            //$converter = new OfficeConverter(public_path($path));
            //$converter->convertTo($path);
        }else {
            $template->saveAs(public_path($path));
        }

        if($template instanceof TemplateProcessor) {
            Storage::disk()->put($path, Storage::disk('public_disk')->get($path));
        }

        $document = $serviceCenter->addDocument($name, $path);

        try {
            $documentPdf = $this->generatePdf($file, $path, $name, $serviceCenter);

        } catch (\Exception $e) {
            $documentPdf = $document->toArray();
        }

        Storage::disk('public_disk')->delete($path);

        return Storage::disk()->url($documentPdf['url']);
    }


    function generateServiceServicesAct(ServiceCenter $serviceCenter)
    {
        $template = $this->getServiceTemplate('default_service_services_act', $serviceCenter);
        if(request()->boolean('preview')) {
            return $template->getResult();
        }
        $file = "{$serviceCenter->id}_service_application.docx";

        $path = config('app.upload_tmp_dir') . "/{$file}";

        $name = "Акт оказания услуг сервиса по заказ-наряду №{$serviceCenter->internal_number}";

        return  $this->saveServiceDoc($serviceCenter, $template, $name, $path);
    }

    function generateServiceReturnAct(ServiceCenter $serviceCenter)
    {
        $template = $this->getServiceTemplate('default_service_return_act', $serviceCenter);
        if(request()->boolean('preview')) {
            return $template->getResult();
        }
        $file = "{$serviceCenter->id}_service_application.docx";

        $path = config('app.upload_tmp_dir') . "/{$file}";

        $name = "Квитанция о выдаче по заказ-наряду №{$serviceCenter->internal_number}";

        return  $this->saveServiceDoc($serviceCenter, $template, $name, $path);

    }

    function getServiceTemplate(
        $name,
        ServiceCenter $serviceCenter)
    {
        $preview = \request()->boolean('preview');
        try {

            $documentsPack =
                $this->documentsPack
                    ?: $serviceCenter->documentsPack;
            $type =
                $this->withStamp
                    ? $name . '_with_stamp'
                    : $name;

            $url =
                ($documentsPack && $documentsPack->{$type})
                    ? Storage::disk()->url($documentsPack->{$type})
                    : public_path('documents/default_application.docx');

            if($preview) {
                $docTypeHtml = $name.'_html';
                $documentsPack->{$docTypeHtml};
                $template = new HtmlTemplateProcessor($documentsPack->{$docTypeHtml});
            }else {
                $template = new TemplateProcessor($url);
            }

        } catch (\Exception $exception) {
            $error = ValidationException::withMessages([
                'errors' => [trans('transbaza_validation.docx_error') . ' ' . $exception->getMessage() . ' ' . $exception->getTraceAsString()]
            ]);

            throw $error;
        }

        $this->setRequisites($template, $serviceCenter);

        $partsTable = $this->getServicePartsTable($serviceCenter);

        if ($partsTable) {
            try {
                $template->cloneRowAndSetValues('partName', $partsTable);
            } catch (\Exception $exception) {

            }
        }

        $vehiclesTable = $this->getServiceVehiclesTable($serviceCenter);

        if ($vehiclesTable) {
            try {
                $template->cloneRowAndSetValues('vehicleName', $vehiclesTable);
            } catch (\Exception $exception) {

            }
        }

        $works = $this->getServiceWorksTable($serviceCenter);

        if ($works) {
            try {
                $template->cloneRowAndSetValues('workName', $works);
            } catch (\Exception $exception) {

            }

        }
        $sum = collect($partsTable)->sum('partCostRaw') + collect($works)->sum('workCostRaw');
        $sumAmount = collect($partsTable)->sum('partAmount') + collect($works)->sum('workCount');
        $machinery = $serviceCenter->machinery;
        if ($machinery) {
            $template->setValue('vehicleName', htmlspecialchars($machinery->name));
            $template->setValue('vehicleBase', htmlspecialchars($machinery->base->name ?? ''));
            $template->setValue('vehicleBaseAddress', htmlspecialchars($machinery->base->address ?? ''));
            $template->setValue('serialNumber', htmlspecialchars($machinery->serial_number));
            $template->setValue('boardNumber', htmlspecialchars($machinery->board_number));
            $template->setValue('category', $machinery->_type->name ?? '');
            $template->setValue('brand', $machinery->name ?? null);
            $template->setValue('model', $machinery->model->name ?? null);
        }
        $template->setValue('id', $serviceCenter->internal_number);
        $template->setValue('dateFrom', $serviceCenter->date_from
            ? $serviceCenter->date_from->format('d.m.Y')
            : '');
        $template->setValue('dateTo', $serviceCenter->date_to
            ? $serviceCenter->date_to->format('d.m.Y')
            : '');
        $template->setValue('createdAt', $serviceCenter->created_at
            ? $serviceCenter->created_at->format('d.m.Y')
            : '');
        $template->setValue('serviceNumber', $serviceCenter->internal_number);
        $template->setValue('itemsAmount', $sumAmount);
        $template->setValue('description', $serviceCenter->description);
        $template->setValue('note', $serviceCenter->note);
        $template->setValue('totalSum', number_format($sum, 2, ',', ' '));
        $template->setValue('totalSumInWords', (new \NumberFormatter(App::getLocale(), \NumberFormatter::SPELLOUT))->format(round($sum, 0,
            PHP_ROUND_HALF_DOWN)));
        $template->setValue('master', $serviceCenter->workers()->first()->name ?? '');

        return $template;
    }

    function generateServiceApplication(ServiceCenter $serviceCenter)
    {

        $template = $this->getServiceTemplate('default_service_act_url', $serviceCenter);

        if(request()->boolean('preview')) {
            return $template->getResult();
        }
        $file = "{$serviceCenter->id}_service_application.docx";

        $path = config('app.upload_tmp_dir') . "/{$file}";

        $name = "Квитанция о сдаче по заказ-наряду  №{$serviceCenter->internal_number}";

        return  $this->saveServiceDoc($serviceCenter, $template, $name, $path);

    }

    function getServiceVehiclesTable(ServiceCenter $serviceCenter)
    {
        $result = [];

        foreach (($serviceCenter->client_vehicles ?? []) as $k => $vehicle) {
            if(\request()->input('clientVehicles') && !in_array($k, \request()->input('clientVehicles', []))) {
                continue;
            }
            $df = !empty( $vehicle['date_from']) ? Carbon::parse( $vehicle['date_from'])->format('d.m.Y') : '';
            $dt = !empty( $vehicle['date_to']) ? Carbon::parse( $vehicle['date_to'])->format('d.m.Y') : '';
            $result[] = [
                'vehicleName' => $vehicle['name'],
                'vehicleDescription' => $vehicle['description'] ?? '',
                'vehicleDateFrom' => $df,
                'vehicleDateTo' => $dt,
            ];
        }
        if (!$result) {
            $result[] = [
                'vehicleName' => '',
                'vehicleDescription' => '',
                'vehicleDateFrom' => '',
                'vehicleDateTo' => '',
            ];
        }
        return $result;
    }

    function getServicePartsTable(ServiceCenter $serviceCenter)
    {

        $result = [];

        foreach ($serviceCenter->parts as $part) {
            $result[] = [
                'partName' => $part->part->name,
                'partAmount' => $part->amount,
                'partCostPerUnit' => number_format($part->cost_per_unit / 100, 2, ',', ' '),
                'partCost' => number_format($part->cost_per_unit * $part->amount / 100, 2, ',', ' '),
                'partCostRaw' => $part->cost_per_unit * $part->amount / 100,
                'unitName' => $part->unit->name ?? '',
                'comment' => $part->comment ?? '',
            ];
        }
        if (!$result) {
            $result[] = [
                'partName' => '',
                'partAmount' => 0,
                'partCostPerUnit' => '',
                'partCost' => '',
                'partCostRaw' => 0,
                'unitName' => '',
                'comment' => '',
            ];
        }
        return $result;
    }

    function getServiceWorksTable(ServiceCenter $serviceCenter)
    {
        $result = [];
        foreach ($serviceCenter->works as $work) {

            $result[] = [
                'workName' => $work->name,
                'workCount' => $work->pivot->count,
                'workCost' => number_format($work->pivot->price * $work->pivot->count, 2, ',', ' '),
                'workCostPerUnit' => number_format($work->pivot->price, 2, ',', ' '),
                'workCostRaw' => $work->pivot->price * $work->pivot->count,
                'comment' => $work->pivot->comment ?? '',
            ];
        }
        if (!$result) {
            $result[] = [
                'workName' => '',
                'workCount' => 0,
                'workCost' => '',
                'workCostPerUnit' => '',
                'workCostRaw' => 0,
                'comment' => 0,
            ];
        }

        return $result;
    }

    function throwFactPositionError()
    {
        throw ValidationException::withMessages([
            'errors' => [
                'В сделке имеются фактические значения, проверьте их.
            Если необходимо скорректируйте фактические значения и завершите приложения (кнопка “Завершить по фактическим значениям”).
             Если фактические значения не нужны -удалите их и вернитесь сюда для печати документов.'
            ]
        ]);
    }

    private function generateTariffTable(Machinery $machinery, $isVat)
    {
        $font = new Font();
        $font->setName('Times New Roman')->setSize(10);
        $tableStyle = (new \PhpOffice\PhpWord\Style\Table)->setBorderColor('d1d1d1')
            ->setBorderSize(6)
            ->setUnit(TblWidth::PERCENT)
            ->setWidth(100 * 50)
            ->setLayout(\PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED);

        $tariffTable = (new PhpWord)->addSection()->addTable($tableStyle);

        $grids =
            TariffGrid::query()
                ->whereHas('machinery', fn(Builder $q) => $q->where($q->qualifyColumn('id'), $machinery->id))
                ->get();
        $tariffTable->addRow();
        $tariffTable->addCell(style: ['gridSpan' => 2])->addText($machinery->name, ['size' => 16], ['align' => 'center'])->setFontStyle($font);
        $tariffTable->addRow();
        $tariffTable->addCell()->addText('Период аренды')->setFontStyle($font);
        $tariffTable->addCell()->addText('Стоимость')->setFontStyle($font);
        foreach ($grids->where('unitCompare.type', TimeCalculation::TIME_TYPE_SHIFT) as $grid) {
            $price = $grid->gridPrices->where('price_type', $isVat ? Price::TYPE_CASHLESS_VAT : Price::TYPE_CASHLESS_WITHOUT_VAT)->first();
            $tariffTable->addRow();
            $cell = $tariffTable->addCell();
            $unit = $grid->unitCompare->type === TimeCalculation::TIME_TYPE_SHIFT ? 'суток.' : 'час.';
            $min = $grid->min * $grid->unitCompare->amount;

            $cell->addText("От {$min} {$unit}")->setFontStyle($font);
            $cell = $tariffTable->addCell();
            $cell->addText(number_format($price->price / 100, 2, ',', ' ') . ($isVat ? ' в т.ч. НДС' : ''))
                ->setFontStyle($font);
        }

        return $tariffTable;
    }

    private function generateAttributesTable(Machinery $machinery, $isVat)
    {
        $font = new Font();
        $font->setName('Times New Roman')->setSize(10);

        $tableStyle = (new \PhpOffice\PhpWord\Style\Table)->setBorderColor('d1d1d1')
            ->setBorderSize(6)
            ->setUnit(TblWidth::PERCENT)
            ->setWidth(100 * 50)
            ->setLayout(\PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED);

        $characteristicTable = (new PhpWord)->addSection()->addTable($tableStyle);

        $characteristicTable->addRow();
        $characteristicTable->addCell()->addText($machinery->name, ['size' => 16], ['align' => 'center'])->setFontStyle($font);
        foreach ($machinery->optional_attributes as $attribute) {

            $characteristicTable->addRow();
            $cell = $characteristicTable->addCell();
            $cell->addText($attribute->full_name)->setFontStyle($font);
        }

        return $characteristicTable;
    }

    function setMachineriesList(Collection $machineries, TemplateProcessor $templateProcessor)
    {
        $models = $machineries->map(function (Machinery $machinery) {

            $characteristics = $machinery->optional_attributes->map(function ($characteristic) {
                return [
                    'key' => $characteristic->name,
                    'value' => "{$characteristic->pivot->value} {$characteristic->unit}",
                ];
            });
            $services = $machinery->model->category->services->map(function ($service) {
                return [
                    'key' => $service->name,
                    'value' => $service->price_cash / 100
                ];
            });
            return [
                'id' => $machinery->model->id,
                'services' => $services,
                'characteristics' => $characteristics,
            ];
        });

        $tb_models = MachineryModel::query()->whereIn('id', $models->pluck('id')->toArray())->get();

        $width = null;
        $widthFirst = null;
        $documentWithServiceTable = new PhpWord();

        $table_style = new \PhpOffice\PhpWord\Style\Table;
        $table_style->setBorderColor('d1d1d1');
        $table_style->setBorderSize(6);
        $table_style->setUnit(TblWidth::PERCENT);
        $table_style->setWidth(100 * 50);
        $table_style->setLayout(TableStyle::LAYOUT_FIXED);

        $cell_style = [
            'valign' => 'center'
        ];

        $servicesTable = $documentWithServiceTable->addSection()->addTable($table_style);

        $servicesTable->addRow();
        $servicesTable->addCell($widthFirst, $cell_style)->addText('Наименование')->setFontStyle()->setBold();

        $documentWithCharacteristicsTable = new PhpWord();
        $characteristicsTable = $documentWithCharacteristicsTable->addSection()->addTable($table_style);

        $characteristicsTable->addRow();
        $characteristicsTable->addCell($widthFirst, $cell_style)->addText('Наименование')->setFontStyle()->setBold();
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
            $characteristicsTable->addCell($width, $cell_style)->addText("{$tbModel->brand->name} {$tbModel->name}");
            $servicesTable->addCell($width, $cell_style)->addText("{$tbModel->brand->name} {$tbModel->name}");
        }

        foreach ($models as $model) {
            $i = 0;
            if (!$model['characteristics']) {
                $templateProcessor->setValue("characteristics", '');
                return;
            }
//            foreach ($model['services'] as $service) {
//                $servicesTable->addRow();
//                $cell = $servicesTable->addCell($width,$cell_style);
//                $cell->addText($service['key'], [
//                    'bold' => true,
//                    'name' => $fontName,
//                ]);
//                if($i % 2 === 0) {
//                    $cell->getStyle()->setBgColor(  'dddddd');
//                }
//                foreach ($tb_models as $tbModel) {
//                    $cell = $servicesTable->addCell($width,$cell_style);
//
//                    if($i % 2 === 0) {
//                        $cell->getStyle()->setBgColor(  'dddddd');
//                    }
//                    $cell->addText($services[sha1($tbModel->id . '_' . $service['key'])],[
//                        'bold' => false,
//                        'name' => $fontName,
//                    ], ['align' => 'center']);
//                }
//                ++$i;
//            }
            $k = 0;
            foreach ($model['characteristics'] as $characteristic) {
                $characteristicsTable->addRow();
                $cell = $characteristicsTable->addCell($width, $cell_style);
                $cell->addText($characteristic['key']);
                if ($k % 2 === 0) {
                    $cell->getStyle()->setBgColor('dddddd');
                }
                foreach ($tb_models as $tbModel) {
                    $cell = $characteristicsTable->addCell($width, $cell_style);
                    if ($k % 2 === 0) {
                        $cell->getStyle()->setBgColor('dddddd');
                    }
                    $cell->addText($characteristics[sha1($tbModel->id . '_' . $characteristic['key'])]);
                }
                ++$k;
            }
            break;
        }
        $templateProcessor->setComplexBlock("{characteristics}", $characteristicsTable);
        //  $templateProcessor->setComplexBlock("{services}", $servicesTable);

        return $this;
    }

    private function generateTariffsTable(Collection $machineries, $isVat)
    {
        $font = new Font();
        $font->setName('Times New Roman')->setSize(10);
        $tableStyle = (new \PhpOffice\PhpWord\Style\Table)->setBorderColor('d1d1d1')
            ->setBorderSize(6)
            ->setUnit(TblWidth::PERCENT)
            ->setWidth(100 * 50)
            ->setLayout(\PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED);

        $tariffTable = (new PhpWord)->addSection()->addTable($tableStyle);

        foreach ($machineries as $machinery) {
            $grids =
                TariffGrid::query()
                    ->whereHas('machinery', fn(Builder $q) => $q->where($q->qualifyColumn('id'), $machinery->id))
                    ->get();
            $tariffTable->addRow();
            $tariffTable->addCell(style: ['gridSpan' => 2])->addText($machinery->name, ['size' => 16], ['align' => 'center'])->setFontStyle($font);
            $tariffTable->addRow();
            $tariffTable->addCell()->addText('Период аренды')->setFontStyle($font);
            $tariffTable->addCell()->addText('Стоимость')->setFontStyle($font);
            foreach ($grids->where('unitCompare.type', TimeCalculation::TIME_TYPE_SHIFT) as $grid) {
                $price = $grid->gridPrices->where('price_type', $isVat ? Price::TYPE_CASHLESS_VAT : Price::TYPE_CASHLESS_WITHOUT_VAT)->first();
                $tariffTable->addRow();
                $cell = $tariffTable->addCell();
                $unit = $grid->unitCompare->type === TimeCalculation::TIME_TYPE_SHIFT ? 'суток.' : 'час.';
                $min = $grid->min * $grid->unitCompare->amount;

                $cell->addText("От {$min} {$unit}")->setFontStyle($font);
                $cell = $tariffTable->addCell();
                $cell->addText(number_format($price->price / 100, 2, ',', ' ') . ($isVat ? ' в т.ч. НДС' : ''))
                    ->setFontStyle($font);
            }

        }

        return $tariffTable;
    }

    /**
     * @return string
     */
    public function getQr(DispatcherInvoice $invoice): string
    {
        $date = Carbon::parse($invoice->created_at)->format("d.m.Y");
        $data = [
            "Name" => 'ООО "Технологичные перевозки"',
            "PersonalAcc" => '40702810601600004347',
            "BankName" => 'АО "АЛЬФА-БАНК" г Москва',
            "BIC" => '044525593',
            "CorrespAcc" => '30101810200000000593',
            "Sum" => $invoice->sum,
            "PayeeINN" => '7731321718',
            "KPP" => '772301001',
            "Purpose" => "Оплата по заказу № {$invoice->owner->external_id} от $date",
        ];
        $query = 'ST00012';
        foreach ($data as $key => $v) {
            $query .= "|$key=$v";
        }
        $path = storage_path('app/alfa');
        $filePath = $path.'/qrcode.png';

        File::isDirectory($path) or File::makeDirectory($path, 0775, true, true);
        QrCode::format('png')->size(400)->encoding('UTF-8')->generate($query, $filePath);

        return $filePath;
    }

    /**
     * @param $invoicePositions
     * @param DispatcherInvoice $invoice
     * @param array $result
     * @return array
     */
    public function addDiscount($invoicePositions, DispatcherInvoice $invoice, array $result): array
    {
//        if ($invoice->type === 'avito' && $invoicePositions && $invoicePositions->owner->avito_dotation_sum > 0) {
//            $result[] = [
//                'num' => "",
//                'name' => "",
//                'vendorCode' => "",
//                'vehicleName' => "",
//                'description' => "",
//                'duration' => "",
//                'unit' => "",
//                'costPerUnit' => "Скидка:",
//                'amount' => number_format(($invoicePositions->owner->avito_dotation_sum) / 100, 2, ',', ' ')
//            ];
//        }
        return $result;
    }

    private function addVat($invoicePositions, DispatcherInvoice $invoice, array $result): array
    {
        $requisites = $invoicePositions?->owner?->order?->customer?->legal_requisites;

        if ($invoicePositions?->owner?->order?->isAvitoOrder() && $requisites && strlen(trim($requisites->inn)) <= 10) {
            $result[] = [
                'num' => "",
                'name' => "",
                'vendorCode' => "",
                'vehicleName' => "",
                'description' => "",
                'duration' => "",
                'unit' => "",
                'costPerUnit' => "в т.ч. НДС 20%:",
                'amount' => number_format(($invoice->sum * 20 / 120 ) / 100, 2, ',', ' ')
            ];
        }

        return $result;
    }

    private function addPayed(DispatcherInvoice $invoice, array &$result)
    {
        if ($invoice->type === 'avito') {
            $sum = $invoice->owner->invoices->where('type','!=','avito_dotation')->sum('paid_sum');
            $result[] =  [
                'num' => "",
                'name' => "",
                'vendorCode' => "",
                'vehicleName' => "",
                'description' => "",
                'duration' => "",
                'unit' => "",
                'costPerUnit' => "Оплачено:",
                'amount' => number_format($sum / 100, 2, ',', ' ')
            ];
        }
    }

    private function getTotalSum(DispatcherInvoice $invoice)
    {
        if ($invoice->type === 'avito' && $invoice->sum > $invoice->owner->amount) {
            return $invoice->owner->amount - $invoice->sum;
        }

        return $invoice->sum;
    }

}

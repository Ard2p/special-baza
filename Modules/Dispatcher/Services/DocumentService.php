<?php


namespace Modules\Dispatcher\Services;

use App\User\IndividualRequisite;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Service\RequestBranch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Ilovepdf\Ilovepdf;
use Modules\ContractorOffice\Entities\Vehicle\Price;
use Modules\Dispatcher\Entities\Directories\Contractor;
use NcJoes\OfficeConverter\OfficeConverter;
use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Database\Eloquent\Builder;
use Modules\Dispatcher\Entities\Customer;
use Illuminate\Validation\ValidationException;
use Modules\Orders\Services\DocumentConverter;
use Modules\Orders\Services\OrderDocumentService;
use Modules\Orders\Services\HtmlTemplateProcessor;
use Modules\CompanyOffice\Services\ContractService;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Entities\Company\DocumentsPack;
use Modules\Dispatcher\Entities\Customer\CustomerContract;


class DocumentService
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

    public static function generateContract($request, $contragentEntitie, $companyBranch)
    {
        $type = $request->input('type', $request->input('contract_type', 'rent'));

        $reqData = explode('_', $request->input('requisites'));
        $requisite = $companyBranch->findRequisiteByType($reqData[1], $reqData[0]);

        // dd($requisite);

        //TODO: Проверка на существование договора
        //  $contract = $contragentEntitie->contracts
        //   ->where('requisite_type', get_class($requisite))
        //   ->where('requisite_id', $requisite->id)
        //   ->first();

        $entity = get_class($contragentEntitie);
        $lock = Cache::lock("generate_contract_{$type}_{$entity}_{$contragentEntitie->id}", 10);

        try {
            if ($lock->get()) {

                $settings = $companyBranch->getSettings();

                $serv = new ContractService($companyBranch, $contragentEntitie, $requisite, $type);

                if ($requisite) {
                    $maskTemplate = $type === 'rent' ? $requisite->contract_number_template : $requisite->contract_service_number_template;
                    $maskNameTemplate = $type === 'rent' ? $requisite->contract_default_name : $requisite->contract_service_default_name;
                }
                if (!($maskTemplate ?? false)) {
                    $maskTemplate = $type === 'rent' ? $settings->contract_number_template : $settings->contract_service_number_template;
                    $maskNameTemplate = $type === 'rent' ? $settings->contract_default_name : $settings->contract_service_default_name;

                    if (!$maskTemplate) {
                        throw ValidationException::withMessages(['errors' => "Отсутствует маска договора."]);
                    }
                }

                $value = $serv->getValueByMask($maskTemplate ?? '');
                $contractName = $serv->getValueByMask($maskNameTemplate ?? '');

                $max =
                    CustomerContract::query()->where('type', $type)
                        ->when($requisite, fn(Builder $builder) => $builder->where('requisite_type', get_class($requisite))->where('requisite_id', $requisite->id))
                        ->whereHasMorph('customer', [get_class($contragentEntitie)], function ($q) use ($contragentEntitie) {
                            $q->forBranch($contragentEntitie->company_branch_id);
                        })
                        //->where('customer_id', $customer->id)
                        ->max('number');

                $data['subject_type'] = null; // TODO: заглушка
                $fullNumber = null; // TODO: заглушка
                $internalNumber = $request->input('internal_number');

                $contract = CustomerContract::create([
                    'number' => ($internalNumber ?: $max + 1),
                    'customer_id' => $contragentEntitie->id,
                    'customer_type' => get_class($contragentEntitie),
                    'type' => $type,
                    'current_number' => $fullNumber ?: $value, //TODO: что это за номер пока сделал заглушку
                    'created_at' => $request->input('date', now()),
                    'start_date' => $request->input('start_date', now()),
                    'end_date' => $request->input('end_date', null),
                    'name' => $contractName,
                    'is_active' => $request->input('is_active', true),
                    'subject_type' => $data['subject_type'] ?? 'contract', //TODO: что это за под тип
                    'last_application_id' => 0
                ]);

                if ($requisite) {
                    $contract->requisite()->associate($requisite);
                    $contract->save();
                }

                $lock->release();
            }
        } catch (\Exception $exception) {
            $lock->release();
            logger($exception->getMessage(), $exception->getTrace());
            throw new \InvalidArgumentException();
        }

        return $contract;
    }

    public function getOrderContractUrl($contract, $request)
    {
        $subContractorId = null; //TODO: добавить
        $name = $request->input('name');

        $customer = $contract->customer;

        $fields = [
            'dateFrom' => Carbon::parse($contract->start_date)->format('d.m.Y'),
            'dateTo' => Carbon::parse($contract->end_date)->format('d.m.Y'),
        ];

        $templateName = request()->boolean('withStamp') ? 'default_contract_url_with_stamp' : 'default_contract_url';

        if ($requestPack = request('documents_pack_id')) {
            $documentsPack = DocumentsPack::query()->forBranch()->find($requestPack);
            if ($documentsPack) {
                $url = $documentsPack->{$templateName};
//                $htmlTemplateName = $templateName . '_html';
//                $htmlTemplateData = $documentsPack->{$htmlTemplateName};
            }
        }

        try {
            $template = new TemplateProcessor(Storage::disk()->url($url));
        } catch (\Exception $exception) {
            $error = ValidationException::withMessages([
                'errors' => [trans('transbaza_validation.docx_error')]
            ]);

            throw $error;
        }

        $title = $subContractorId ? "Подр. {$name}" : $name;
        $title .= ' ' . now()->format('d.m.Y H:i');

        if ($customer instanceof Customer) {
            $this->setRequisites($template, $contract);
        }

        foreach ($fields as $key => $value) {
            $template->setValue($key, $value);
        }

        $extension = getFileExtensionFromString($url);
        $customerName = '';
        if ($customer) {
            $customerName = $customer->company_name;
        }

        $uid = now()->format('d_m_Y H_i');
        $newName = "{$title} {$customerName}_{$uid}.{$extension}";

        $name = "Договор  № {$contract->full_number} от " . $contract->created_at->format('d.m.Y')
            . (request()->boolean('withStamp') ? ' (c печатью' : ' (без печати') . ' ' . now($this->companyBranch->timezone)->format('d.m.Y H:i') . ')';
        $tmpPdf = config('app.upload_tmp_dir') . '/' . Str::random('4') . "_{$uid}.{$extension}";
        $newName = str_replace('#', '', $newName);
        $newName = str_replace('"', '', $newName);

        $path = config('app.upload_tmp_dir') . '/' . $newName;

//        Storage::makeDirectory('public/' . $path);

        $template->saveAs(public_path($path));

        Storage::disk()->put($path, Storage::disk('public_disk')->get($path));

        Storage::disk('public_disk')->rename($path, $tmpPdf);

        $pdfDocument = $this->generatePdf(last(explode('/', $tmpPdf)), $tmpPdf, $name, $customer, $subContractorId, 'contract');

        Storage::disk('public_disk')->delete($path);

        return Storage::disk()->url($pdfDocument);
    }

    public function getLeadContractUrl($contract, $lead)
    {
        $subContractorId = null; //TODO: добавить
        $name = 'Договор';

        $customer = $contract->customer;

        $fields = [
            'dateFrom' => Carbon::parse($contract->start_date)->format('d.m.Y'),
            'dateTo' => Carbon::parse($contract->end_date)->format('d.m.Y'),
        ];

        $templateName = request()->boolean('withStamp') ? 'default_contract_url_with_stamp' : 'default_contract_url';

        if ($requestPack = request('documents_pack_id')) {
            $documentsPack = DocumentsPack::query()->forBranch()->find($requestPack);
            if ($documentsPack) {
                $url = $documentsPack->{$templateName};
//                $htmlTemplateName = $templateName . '_html';
//                $htmlTemplateData = $documentsPack->{$htmlTemplateName};
            }
        }

        try {
            $template = new TemplateProcessor(Storage::disk()->url($url));
        } catch (\Exception $exception) {
            $error = ValidationException::withMessages([
                'errors' => [trans('transbaza_validation.docx_error')]
            ]);

            throw $error;
        }
        $title = $subContractorId ? "Подр. {$name}" : $name;
        $title .= ' ' . now()->format('d.m.Y H:i');

        if ($customer instanceof Customer) {
            $this->setRequisites($template, $contract, $lead);
        }

        foreach ($fields as $key => $value) {
            $template->setValue($key, $value);
        }

        $extension = getFileExtensionFromString($url);
        $customerName = '';
        if ($customer) {
            $customerName = $customer->company_name;
        }

        $uid = now()->format('d_m_Y H_i');
        $newName = "{$title} {$customerName}_{$uid}.{$extension}";

        $name = "Договор  № {$contract->full_number} от " . $contract->created_at->format('d.m.Y')
            . (request()->boolean('withStamp') ? ' (c печатью' : ' (без печати') . ' ' . now($this->companyBranch->timezone)->format('d.m.Y H:i') . ')';
        $tmpPdf = config('app.upload_tmp_dir') . '/' . Str::random('4') . "_{$uid}.{$extension}";
        $newName = str_replace('#', '', $newName);
        $newName = str_replace('"', '', $newName);

        $path = config('app.upload_tmp_dir') . '/' . $newName;

        $template->saveAs(public_path($path));

        Storage::disk()->put($path, Storage::disk('public_disk')->get($path));

        $document = $lead->addDocument($name, $path, $subContractorId, 'contract');

        Storage::disk('public_disk')->rename($path, $tmpPdf);

        $pdfDocument = $this->generatePdf(last(explode('/', $tmpPdf)), $tmpPdf, $name, $lead, $subContractorId, 'contract');

        $pdfName = $name;

//            $docs = $lead->documents()->where('name', $pdfName)->get();
//
//            $docs->each(function ($item) {
//                $item->delete();
//            });

        $document = $lead->addDocument($pdfName, $pdfDocument, $subContractorId, 'contract');

        Storage::disk('public_disk')->delete($path);

        return Storage::disk()->url($pdfDocument);
    }

    function setRequisites(
        $template,
        $contract,
        $instance = null
    )
    {
        $subContractor = null;
        $subContractorId = $this->options['subContractorId'] ?? null;

        if ($subContractorId) {
            $subContractor = Contractor::query()->forBranch()->find($subContractorId);
        }
        $customer = $contract->customer;

        $company = $this->companyBranch;
        $settings = $company->getSettings();

        $template->setValue('titleImage', '');
        if ($customer->legal_requisites) {
            $template->setValue('customer',
                "{$customer->legal_requisites->name}, ИНН: {$customer->legal_requisites->inn}, КПП: {$customer->legal_requisites->kpp}, Юридический адрес: {$customer->legal_requisites->register_address}");
        }
        if (!empty($this->options['contact_id'])) {
            $contact = $customer->contacts()->find($this->options['contact_id']);
        } else {
            $contact = $customer->contacts()->first();
        }

//        $template->setValue('currency', $instance->currency->short ?? '');
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
        if (!empty($customer->contacts)) {
            $template->setValue('customerContactPhones', $customer->contacts->flatMap(fn($contact) => $contact->phones)->pluck('phone')->join(', '));
        }

        $phone = $phone ?? '';
        $email = $email ?? '';

        $contact_v = $contact
            ? "{$contact->full_name}, +{$phone}"
            : "{$customer->contact_person}, +" . $customer->phone;

        $template->setValue('customer_contact', $contact_v);
        $template->setValue('customerContactPhone', $phone);
        $template->setValue('customerContactPerson', ($contact
            ? $contact->full_name
            : $customer->contact_person));
        $template->setValue('customerContactEmail', $email);

        $template->setValue('contractId', $contract->current_number);
        $template->setValue('contractDate', $contract->created_at->format('d.m.Y'));

        $template->setValue('address', "{$customer->address}");
        $template->setValue('externalId', "{$customer->externalId}");
        $template->setValue('date', (!empty($this->options['date'])
            ? Carbon::parse($this->options['date'])->format('d.m.Y')
            : now()->format('d.m.Y')));

        $template->setValue('time', (!empty($this->options['time'])
            ? Carbon::parse($this->options['time'])->format('H:i')
            : now()->format('H:i')));

        $template->setValue('pageBreak', '<w:p><w:r><w:br w:type="page"/></w:r></w:p>');

        // TODO: Разобраться что за контрактор $customer->getRequisites();
//        if ($requisites =
//            $subContractor
//                ? $subContractor->requisites
//                : $instance->contractorRequisite) {
//            $template->setValue('contractor_name', $requisites->name);
//            $template->setValue('contractor_address', $requisites->register_address);
//            $template->setValue('contractor_phone', $requisites->phone);
//        }
        // TODO: Разобраться что за манагер
//        if ($instance->manager) {
//            $template->setValue('managerName', $instance->manager->contact_person);
//            $template->setValue('managerPhone', $instance->manager->phone);
//            $template->setValue('managerEmail', $instance->manager->email);
//
//        }

        $customerLegalRequisites = null;
        $customerIndividualRequisites = null;
        // TODO: Разобраться $customer->getRequisites();
//        if ($subContractorId) {
//            $customerRequisites = $instance->contractorRequisite;
//            if ($customerRequisites instanceof IndividualRequisite) {
//                $customerIndividualRequisites = $customerRequisites;
//            } else {
//                $customerLegalRequisites = $customerRequisites;
//            }
//        } else {//
        $customerLegalRequisites = $customer->legal_requisites;
        $customerIndividualRequisites = $customer->individual_requisites;
//        }
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

        $contractorRequisites = null;
        if($instance)
            $contractorRequisites = $instance->contractorRequisite;
        else if($subContractor)
            $contractorRequisites = $subContractor->requisites;

        if ($contractorRequisites) {
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
            if ($customer->bankRequisite) {
                $bank = $customer->bankRequisite;
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

//        if ($instance->principal) {
//            $this->setPrincipalRequesites($template, $instance->principal?->person);
//        }

        return $this;
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

        return $pdfPath;
    }
}


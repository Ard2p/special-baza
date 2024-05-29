<?php


namespace Modules\CompanyOffice\Services;


use App\Helpers\RequestHelper;
use App\User\IndividualRequisite;
use App\User\PrincipalDoc;
use Illuminate\Database\Eloquent\Model;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Entities\Company\Contact;
use Modules\CompanyOffice\Entities\Company\ContactEmail;
use Modules\CompanyOffice\Entities\Company\ContactPhone;

class ContactsService
{

    private $companyBranch;

    static function getValidationRules($single = false)
    {
        $rule = [
            'firstname' => 'required|string|min:3|max:255',
            'middlename' => 'nullable|string|min:3|max:255',
            'surname' => 'nullable|string|min:3|max:255',
           // 'email' => 'nullable|email|max:255',
           // 'phone' => 'required|numeric|digits:' . config('request_domain')->options['phone_digits'],
            'position' => 'nullable|string|max:255',
            'phones' => 'nullable|array',
            'phones.*.phone' => 'nullable|numeric|digits:' . RequestHelper::requestDomain()->options['phone_digits'],
            'emails.*.email' => 'nullable|email|max:255',
             'principals' => 'nullable|array',
            'principals.*.number' => 'required',
            'principals.*.start_date' => 'required|date',
            'principals.*.end_date' => 'required|date|after:start_date',
            'principals.*.scans' => 'nullable|array',
            'principals.*.is_rent' => 'nullable|boolean',
            'principals.*.is_service' => 'nullable|boolean',
            'principals.*.is_part_sale' => 'nullable|boolean',
        ];

        return $single ? $rule : array_combine(array_map(function ($key) {
            return "contacts.*.{$key}";
        }, array_keys($rule)), $rule);
    }
    static function getValidationAttributes($single = false)
    {
        $rule = [
            'contact_person' => 'Контактное лицо',
            'email' => 'Email',
            'phone' => 'Телефон',
            'position' => 'Должность',
        ];

        return $single ? $rule : array_combine(array_map(function ($key) {
            return "contacts.*.{$key}";
        }, array_keys($rule)), $rule);
    }
    public function __construct(CompanyBranch $companyBranch)
    {
        $this->companyBranch = $companyBranch;

    }

    function updateContact($id, $data)
    {
        /** @var IndividualRequisite $contact */
        $contact = IndividualRequisite::query()->where('type', IndividualRequisite::TYPE_PERSON)->forBranch($this->companyBranch->id)->findOrFail($id);
        $contact->update($data);

        $this->setContacts($contact, $data);
        return $contact;
    }

    private function setContacts(IndividualRequisite $contact, $data)
    {
        $ids = $contact->principals()->pluck('id');
        if(!empty($data['principals'])) {

            foreach ($data['principals'] as $principal) {
                if(!empty($principal['id'])) {
                    $ids = $ids->filter(fn($id) => $id !== $principal['id']);
                    $contact->principals()->findOrFail($principal['id'])->update($principal);
                }else {
                    $contact->principals()->save(new PrincipalDoc($principal));
                }
            }
        }
        $contact->principals()->whereIn('id', $ids)->delete();
        if(!empty($data['phones'])) {
            $exclude = [];
            foreach ($data['phones'] as $item) {
                if(!$item) {
                    continue;
                }
                $phone = trimPhone($item['phone']);
                $exclude[] = $phone;
                if($contact->phones()->wherePhone($phone)->exists()) {
                    continue;
                }

                $contact->phones()->save(new ContactPhone(['phone' => $phone]));
            }
        }
        $contact->phones()->whereNotIn('phone', ($exclude ?? []))->delete();

        if(!empty($data['emails'])) {
            foreach ($data['emails'] as $item) {
                if(!$item || empty($item['email'])) {
                    continue;
                }
                $email = strtolower($item['email']);
                $exclude[] = $email;

                if($contact->emails()->whereEmail($email)->exists()) {
                    continue;
                }

                $contact->emails()->save(new ContactEmail(['email' => $email]));
            }
        }
        $contact->emails()->whereNotIn('email', ($exclude ?? []))->delete();


        return $this;
    }

    function createContact($data, Model $owner)
    {
        $data['company_branch_id'] = $this->companyBranch->id;
        $data['type'] = IndividualRequisite::TYPE_PERSON;
        $contact = IndividualRequisite::query()->forBranch($this->companyBranch->id)->find($data['id'] ?? null) ?: new IndividualRequisite();
        $contact->fill($data);
        $contact->save();
        $owner->contacts()->syncWithoutDetaching($contact);
        $this->setContacts($contact, $data);

        $contact->load(['emails', 'phones']);
        return $contact;
    }
}

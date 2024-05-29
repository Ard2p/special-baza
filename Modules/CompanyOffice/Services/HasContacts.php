<?php


namespace Modules\CompanyOffice\Services;


use App\User\IndividualRequisite;
use App\User\PrincipalDoc;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Entities\Company\Contact;

trait HasContacts
{

    function contacts()
    {
        return $this->morphToMany(IndividualRequisite::class, 'owner', 'individual_requisites_contacts_pivot')
            ->withPivot('type')
            ->with(['phones', 'emails', 'principals.person']);
    }


    function addContacts($contacts)
    {
        $companyBranch = $this instanceof CompanyBranch ? $this : $this->company_branch;
        $contactService = new ContactsService($companyBranch);

        $allIds = $this->contacts->pluck('id')->toArray();

        foreach ($contacts as $contact) {

            if (!empty($contact['id'])) {
                $contactModel = $contactService->updateContact($contact['id'], $contact);
                $this->contacts()->syncWithoutDetaching([$contactModel->id]);
                $key = array_search($contact['id'], $allIds);
                if ($key !== false) {
                    unset($allIds[$key]);
                }
            } else {
                $contactModel = $contactService->createContact($contact, $this);
            }
            $principalIds = [];
            if(!empty($contact['principals'])) {

                foreach ($contact['principals'] as $principal) {
                    if(!empty($principal['id'])) {
                       $model = $contactModel->principals()->findOrFail($principal['id']);
                       $model->update($principal);
                    }else {
                        $model = $contactModel->principals()->save(new PrincipalDoc($principal));
                    }
                    $principalIds[] = $model->id;
                }
            }
            $contactModel->principals()->whereNotIn('id', $principalIds)->delete();

        }

        $this->contacts()->detach( $allIds);

        return $this;
    }

    function scopeFilterContactPerson(
        $q,
        $contactPerson = null,
        $phone = null
    ) {
        return $q->whereHas('contacts', function ($q) use (
            $phone,
            $contactPerson
        ) {
            if ($contactPerson) {
                $q->where('contact_person', $contactPerson);
            }
            if (is_numeric($phone)) {

                $q->whereHas('phones', function ($q) use (
                    $phone
                ) {
                    $q->where('phone', 'like', "%$phone%");
                });
            }

        });
    }


}

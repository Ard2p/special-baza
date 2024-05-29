<?php

namespace Modules\PartsWarehouse\Entities;

use App\City;
use App\Support\Region;
use App\User\EntityRequisite;
use App\User\IndividualRequisite;
use Illuminate\Database\Eloquent\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\CompanyOffice\Services\HasContacts;
use Modules\CompanyOffice\Services\HasManager;
use Modules\CorpCustomer\Entities\InternationalLegalDetails;

class PartsProvider extends Model
{

    use BelongsToCompanyBranch, HasManager, HasContacts;

    protected $table = 'warehouse_parts_providers';

    protected $appends = ['domain'];

    protected $fillable = [
        'phone',
        'email',
        'company_name',
        'address',
        'city_id',
        'region_id',
        'creator_id',
        'company_branch_id'
    ];


    function region()
    {
        return $this->belongsTo(Region::class);
    }

    function city()
    {
        return $this->belongsTo(City::class);
    }

    function entity_requisites()
    {
        return $this->morphOne(EntityRequisite::class, 'requisite');
    }

    function getLegalRequisitesAttribute()
    {
        return $this->domain->alias === 'ru' ? $this->entity_requisites : $this->international_legal_requisites;
    }

    function getDomainAttribute()
    {
        return $this->company_branch->domain;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function international_legal_requisites()
    {
        return $this->morphOne(InternationalLegalDetails::class, 'requisite');
    }

    function individual_requisites()
    {
        return $this->morphOne(IndividualRequisite::class, 'requisite');
    }

    function hasRequisite()
    {
        return $this->entity_requisites()->exists() || $this->individual_requisites()->exists() || $this->international_legal_requisites()->exists();
    }

    function getTypeAttribute()
    {
        return $this->hasRequisite()
            ? ($this->entity_requisites()->exists() ? trans('tb_requisites.legal') : trans('tb_requisites.individual'))
            : '';
    }

    function getHasRequisitesAttribute()
    {
        return $this->hasRequisite();
    }

    function addLegalRequisites($data)
    {
        $requisite = $this->domain->alias === 'ru'
            ? $this->entity_requisites()->save((new EntityRequisite($data)))
            : $this->international_legal_requisites()->save(( new InternationalLegalDetails($data)));

        return $requisite;
    }

    function addIndividualRequisites($data)
    {

        $requisite = new IndividualRequisite($data);

        $this->individual_requisites()->save($requisite);

        return $requisite;
    }

}

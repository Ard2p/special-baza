<?php

namespace Modules\Orders\Entities;

use App\Overrides\Model;
use Modules\CompanyOffice\Services\BelongsToCompany;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\CompanyOffice\Services\InternalNumbering;

class UdpRegistry extends Model
{
    use BelongsToCompanyBranch;

    protected $fillable = ['internal_number', 'type'];

    static function getNumber(Model $model, $type = 'upd') {
        $entity = self::query()->where('parent_id', $model->id)
            ->where('parent_type', get_class($model))->whereType($type)->first();
        if(!$entity) {
            $entity = new self();
            $entity->parent()->associate($model);
            $entity->company_branch()->associate($model->company_branch);
            $entity->type = $type;
            $entity->save();

            $entity->refresh();
        }
        return $entity->internal_number;
    }

    protected static function boot()
    {
        parent::boot();

        self::created(function (self $model) {
            $model->setInternalNumber();
        });
    }

    function setInternalNumber()
    {
        $last = self::query()->forBranch($this->company_branch_id)->whereType($this->type)->max('internal_number');

        $this->internal_number = $last + 1;
        $this->save();
    }

    function parent()
    {
        return $this->morphTo();
    }
}

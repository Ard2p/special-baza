<?php


namespace Modules\CompanyOffice\Services;


trait InternalNumbering
{


    protected static function boot()
    {
        parent::boot();

        self::created(function (self $model) {
           $model->setInternalNumber();
        });
    }

    function setInternalNumber()
    {
        $last = self::query()->forBranch($this->company_branch_id)->max('internal_number');

        $this->internal_number = $last + 1;
        $this->save();
    }
}
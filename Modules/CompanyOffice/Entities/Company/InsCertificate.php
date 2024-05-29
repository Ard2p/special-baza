<?php

namespace Modules\CompanyOffice\Entities\Company;

use Illuminate\Database\Eloquent\Model;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\Orders\Entities\OrderComponent;

class InsCertificate extends Model
{
    use BelongsToCompanyBranch;

    public const STATUS_ACTIVE = 1;
    public const STATUS_CLOSED = 2;

    public static function boot()
    {
        parent::boot();

        self::created(function ($model) {
            $model->number = sprintf('%09d', $model->id);
            $model->name = $model->company_branch->ins_setting->contract_number.'/'.$model->number;
            $model->save();
        });
    }

    protected $fillable = [
        'number',
        'name',
        'premium',
        'sum',
        'date_from',
        'date_to',
        'order_worker_id',
        'attachment',
        'status',
        'company_branch_id',
    ];

    public function company_branch()
    {
        return $this->belongsTo(CompanyBranch::class);
    }

    public function order_worker()
    {
        return $this->belongsTo(OrderComponent::class);
    }


}

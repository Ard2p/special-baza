<?php

namespace Modules\Orders\Entities\Service;

use App\Overrides\Model;
use OwenIt\Auditing\Contracts\Auditable;

class ServiceCenterWorksPivot extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    public $timestamps = false;

    protected $table = 'service_center_custom_services';

    public $incrementing = true;

    protected $fillable = [
        'price',
        'count',
        'comment',
    ];

    protected $auditInclude = [
        'comment'
    ];

    protected $with = ['audits.user'];

}

<?php

namespace App\Http\Controllers\Avito\Models;

use App\Http\Controllers\Avito\Repositories\AvitoRepository;
use App\Overrides\Model;
use App\System\OrderableModel;
use App\User;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\CompanyOffice\Services\HasManager;
use Modules\Dispatcher\Entities\Customer;
use Modules\Orders\Entities\Order;
use Modules\Orders\Services\OrderTrait;

class AvitoOrder extends Model
{
    use BelongsToCompanyBranch;

    public const STATUS_CREATED = 1;
    public const STATUS_CANCELED = 2;
    public const STATUS_PROPOSED = 3;
    public const STATUS_PREPAID = 4;
    public const STATUS_FINISHED = 5;
    public const CANCEL_REASON_MACHINERY_NOT_FOUND = 1;
    public const CANCEL_REASON_CUSTOMER_NOT_FOUND = 2;
    public const CANCEL_REASON_GEO_NOT_FOUND = 3;
    public const CANCEL_REASON_SYSTEM_FAILURE = 9;

    public const CUSTOMER_TYPE_PHYSICAL = 1;
    public const CUSTOMER_TYPE_LEGAL = 2;
    public const CUSTOMER_TYPE_IP = 3;

    protected $fillable = [
        'avito_ad_id',
        'avito_order_id',
        'order_id',
        'company_branch_id',
        'coordinate_x',
        'coordinate_y',
        'rent_address',
        'start_date_from',
        'start_date_to',
        'rental_duration',
        'customer_id',
        'contact_id',
        'customer_name',
        'phone',
        'email',
        'inn',
        'status',
        'customer_type',
        'cancel_reason',
        'return_sum',
        'pay_reminder',
        'timeout_reminder',
        'avito_add_price',
        'hold',
    ];

    protected static function boot()
    {
        parent::boot();

        self::updating(function (self $avitoOrder) {
            AvitoRepository::updateHoldHistory($avitoOrder);
        });
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function histories()
    {
        return $this->hasMany(AvitoOrderHistory::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function contact()
    {
        return $this->belongsTo(User::class, 'contact_id');
    }

    public function getCancelReasonMessageAttribute()
    {
        switch ($this->cancel_reason) {
            case self::CANCEL_REASON_MACHINERY_NOT_FOUND:
                return 'Техника не найдена';
            case self::CANCEL_REASON_CUSTOMER_NOT_FOUND:
                return 'Клиент не найден';
            case self::CANCEL_REASON_GEO_NOT_FOUND:
                return 'Адрес не найден';
            case self::CANCEL_REASON_SYSTEM_FAILURE:
                return 'Системная ошибка';
            default:
                return null;
        }
    }
    public function getCustomerTypeNameAttribute()
    {
        switch ($this->customer_type) {
            case self::CUSTOMER_TYPE_PHYSICAL:
                return 'Физ';
            case self::CUSTOMER_TYPE_LEGAL:
                return 'Юр';
            case self::CUSTOMER_TYPE_IP:
                return 'Ип';
            default:
                return null;
        }
    }
    public function getCustomerTypeCodeAttribute()
    {
        switch ($this->customer_type) {
            case self::CUSTOMER_TYPE_PHYSICAL:
                return 'individual';
            case self::CUSTOMER_TYPE_LEGAL:
                return 'legal';
            case self::CUSTOMER_TYPE_IP:
                return 'entity';
            default:
                return null;
        }
    }

    public function log()
    {
        return $this->hasOne(AvitoLog::class)->latestOfMany();
    }
}

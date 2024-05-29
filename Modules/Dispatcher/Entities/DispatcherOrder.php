<?php

namespace Modules\Dispatcher\Entities;

use App\City;
use App\Support\Region;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Modules\Dispatcher\Entities\Directories\Contractor;
use Modules\Orders\Entities\OrderDocument;
use Modules\Orders\Services\ContractTrait;

class DispatcherOrder extends Model
{

    use SoftDeletes, ContractTrait;

    const STATUS_ACCEPT = 'accept';
    const STATUS_CANCEL = 'cancel';
    const STATUS_DONE = 'done';
    const STATUS_CLOSE = 'close';

    protected $fillable = [
        'customer_name',
        'phone',
        'address',
        'comment',
        'status',
        'start_date',
        'city_id',
        'region_id',
        'user_id',
        'customer_id',
        'is_paid',
        'amount',
        'contractor_sum',
    ];

    protected $dates = [
        'start_date'
    ];


    protected $casts = [
        'is_paid' => 'boolean'
    ];

    protected $appends = [ 'documents', 'categories', 'status_lang', 'details_link'];

   // protected $with = ['customer', 'contractor'];


    static function statuses()
    {
        return [
            self::STATUS_CLOSE => trans('transbaza_statuses.proposal_close'),
            self::STATUS_ACCEPT => trans('transbaza_statuses.proposal_in_work'),
            self::STATUS_DONE => trans('transbaza_statuses.proposal_end'),
            self::STATUS_CANCEL => trans('transbaza_statuses.proposal_cancel'),
        ];
    }

    function getStatusLangAttribute()
    {
        return self::statuses()[$this->status];
    }


    function user()
    {
        return $this->belongsTo(User::class);
    }

    function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    function city()
    {
        return $this->belongsTo(City::class);
    }

    function region()
    {
        return $this->belongsTo(Region::class);
    }

    function documents()
    {
        return $this->morphMany(OrderDocument::class, 'order');
    }

    function leads()
    {
        return $this->morphToMany(Lead::class, 'order', 'dispatcher_leads_orders');
    }

    function invoices()
    {
        return $this->hasMany(DispatcherInvoice::class, 'dispatcher_order_id');
    }

    function getInvoicesPaidAttribute()
    {
        return $this->invoices->sum('sum');
    }

    function contractor()
    {
        return $this->belongsTo(Contractor::class);
    }

    function scopeCurrentUser($q)
    {
        return $q->where('user_id', Auth::id());
    }

    function getCategoriesAttribute()
    {
        return $this->lead ? $this->lead->categories : [];
    }

    function contractor_pays()
    {
        return $this->hasMany(ContractorPay::class);
    }


    function getContractorPaidSumAttribute()
    {
        return $this->contractor_pays->sum('sum');
    }

    function canAddContractorPay()
    {
        return $this->contractor_sum > $this->contractor_paid_sum;
    }


    function getDocumentsAttribute()
    {
        return [];
    }

    function complete()
    {
        $this->lead->update([
            'status' => Lead::STATUS_DONE
        ]);

        $this->update([
            'status' => self::STATUS_DONE
        ]);
    }

    function getDetailsLinkAttribute()
    {
        return route('dispatcher_order_pdf_details', $this->id);
    }


    function getLeadAttribute()
    {
        return $this->leads()->first();
    }

    function getCurrencyAttribute()
    {
        return $this->lead ? $this->lead->domain->currency : null;
    }
}

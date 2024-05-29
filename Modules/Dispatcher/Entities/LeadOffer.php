<?php

namespace Modules\Dispatcher\Entities;

use App\Helpers\RequestHelper;
use App\Machinery;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Modules\CompanyOffice\Services\BelongsToCompanyBranch;
use Modules\CompanyOffice\Services\HasManager;
use Modules\Dispatcher\Entities\Directories\Contractor;
use Modules\Orders\Entities\OrderManagement;
use Modules\Orders\Entities\Payment;

class LeadOffer extends Model
{

    use BelongsToCompanyBranch, HasManager;

    protected $table = 'dispatcher_lead_offers';

    protected $fillable = [
        'lead_id',
        'creator_id',
        'company_branch_id',
        'accept'
    ];

    protected $casts = [
        'accept' => 'boolean'
    ];

    protected $appends = ['amount', 'categories'];

    function lead()
    {
        return $this->belongsTo(Lead::class);
    }


    function user()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }


    function positions()
    {
        return $this->hasMany(LeadOfferPosition::class);
    }

    function getAmountAttribute()
    {
        return $this->positions->sum('amount') + $this->positions->sum('value_added');
    }

    function getCategoriesAttribute()
    {
        $categories = $this->positions->pluck('category');

        $unique = $categories->unique('id');

        $unique = $unique->map(function ($item) use ($categories) {
            $item->count = $categories->where('id', $item->id)->count();

            return $item;
        });

        return $unique;
    }

    function getDateFromAttribute()
    {
        $categories = $this->lead->positions->whereIn('type_id', $this->positions->pluck('category_id')->toArray());

        return Carbon::createFromTimestamp($categories->map(function ($category) {

            $category->start_date = $category->date_from->getTimestamp();
            return $category;
        })->min('start_date'));
    }

    function acceptForDispatcher($value_added)
    {
        $orderManage = new OrderManagement([], $this->lead->coordinates);

        $orderManage->prepareFromOffer($this, $value_added);

        $orderManage
            ->setInitiator(Auth::user())
            ->setCustomer($this->lead->company_branch)
            ->setContractor($this->company_branch)
            ->setDateFrom($this->date_from)
            ->setDispatcherOrder()
            ->setDetails([
                'contact_person' => $this->lead->customer_name,
                'address' => $this->lead->address,
                'region_id' => $this->lead->region_id,
                'city_id' => $this->lead->city_id,
                'coordinates' => $this->lead->coordinates,
                'start_time' => $this->date_from->format('H:i'),
            ])
            ->createFromOffer($this, $value_added);

        Payment::create([
            'system' => 'dispatcher',
            'status' => Payment::STATUS_WAIT,
            'currency' => RequestHelper::requestDomain()->currency->code,
            'amount' => $orderManage->created_proposal->amount,
            'creator_id' =>Auth::id(),
            'company_branch_id' => $orderManage->customerCompanyBranch->id,
            'order_id' => $orderManage->created_proposal->id
        ]);

        $orderManage->created_proposal->accept();

        $this->lead->orders()->attach($orderManage->created_proposal);

        $this->update([
            'accept' => true
        ]);

        return  $orderManage->created_proposal;
    }

    /**
     * Подтверждение заявки от исполнителя трансбазы и создание заказа с оплатой.
     * @param $type
     * @return array
     */
    function accept($type)
    {

        $orderManage = new OrderManagement([], $this->lead->coordinates);

        $pay_items = $orderManage->prepareFromOffer($this);

        $orderManage
            ->setInitiator(Auth::user())
            ->setCustomer($this->lead->company_branch)
            ->setContractor($this->company_branch)
            ->setDateFrom($this->date_from)
            ->setDetails([
                'contact_person' => $this->lead->customer_name,
                'address' => $this->lead->address,
                'region_id' => $this->lead->region_id,
                'city_id' => $this->lead->city_id,
                'coordinates' => $this->lead->coordinates,
                'start_time' => $this->date_from->format('H:i'),
            ])
            ->createFromOffer($this);


        $payment = Payment::create([
            'system' => $type,
            'status' => Payment::STATUS_WAIT,
            'currency' => RequestHelper::requestDomain()->currency->code,
            'amount' => $orderManage->created_proposal->amount,
            'creator_id' => Auth::id(),
            'company_branch_id' => $orderManage->customerCompanyBranch->id,
            'order_id' => $orderManage->created_proposal->id
        ]);

        $this->update([
            'accept' => true
        ]);

        return [
            'payment' => $payment,
            'order' => $orderManage->created_proposal,
            'pay_items' => $pay_items
        ];
    }

    /**
     * Проверка и получение доступности техники в предложении.
     * @return \Illuminate\Support\Collection
     */
    function getAvailableVehicles()
    {
        $vehicles = collect();

        foreach ($this->positions->where('worker_type', Machinery::class)->all() as $position) {

            $category = $this->lead->positions->where('type_id', $position['category_id'])->first();

            $date_to = getDateTo($category->date_from, $category->order_type, $category->order_duration);

            $v = Machinery::query()
                ->categoryBrandModel($category->type_id, $category->brand_id, $category->machinery_model_id)
                ->checkAvailable($category->date_from, $date_to, $category->order_type, $category->order_duration)
                ->whereInCircle($this->lead->coords['lat'], $this->lead->coords['lng'])
                ->sharedLock()
                ->find($position['worker_id']);

            if ($v) $vehicles->push($v);
        }

        return $vehicles;
    }


}

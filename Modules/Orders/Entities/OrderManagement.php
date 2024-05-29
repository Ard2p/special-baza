<?php

namespace Modules\Orders\Entities;


use App\Helpers\RequestHelper;
use App\Machinery;

use App\Option;


use App\Support\Gmap;
use App\User;
use Carbon\Carbon;
use http\Exception\InvalidArgumentException;

use Illuminate\Support\Facades\Auth;
use Modules\CompanyOffice\Entities\Company\CompanyBranch;
use Modules\CompanyOffice\Services\CompaniesService;
use Modules\ContractorOffice\Entities\System\Tariff;
use Modules\ContractorOffice\Entities\Vehicle\Price;
use Modules\Dispatcher\Entities\Customer;
use Modules\Dispatcher\Entities\LeadOffer;
use Modules\Profiles\Entities\UserNotification;

class OrderManagement
{

    public $details = [];
    public $created_proposal;
    public $vehicles = [];
    public $required_categories = [];
    public $customerCompanyBranch;
    public $contractorRequisites;
    public $date_from;
    public $date_to;
    public $contractorCompanyBranch;
    public $dispatcherCustomer;
    private $from_dispatcher = false;
    private $initiator;

    public function __construct($required_categories, $coordinates)
    {
        $this->details['coordinates'] = $coordinates;
        $this->required_categories = $this->parseCategories($required_categories);
    }


    /**
     * Подготовка категорий. Проверка на заказ по времени/ либо киллометраж.
     * @param $categories
     * @return \Illuminate\Support\Collection
     */
    function parseCategories($categories)
    {
        $new = collect();
        foreach ($categories as $category) {
            if (!$category['order_waypoints']) {
                $new->push($category);
                continue;
            }
            $route = Gmap::calculateRoute($category['order_waypoints']['coordinates'], $this->details['coordinates']);

            if (!$route) {
                throw new \InvalidArgumentException();
            }
            $category['order_type'] = 'hour';
            $category['order_duration'] = round(($route['duration']['value'] / 60) / 60, 0, PHP_ROUND_HALF_DOWN) + 1;
            $coordinates = explode(',', $category['order_waypoints']['coordinates']);
            $category['order_waypoints'] = [
                'address' => $category['order_waypoints']['address'],
                'coordinates' => [
                    'lat' => $coordinates[0],
                    'lng' => $coordinates[1],
                ],
                'distance' => $route['distance']['value'],
                'duration' => $route['duration']['value'],
            ];

            /*  if (!empty($category['params']) && $v_category->tariffs->isNotEmpty()) {

                  $leadPosition->params = $category['params'];

              }*/
            $new->push($category);
        }
        return $new;
    }

    function prepareVehicles($vehicles, $contractors = [])
    {
        $this->vehicles = $vehicles;
        $items = collect([]);
        $shops = collect([]);
        $sum = 0;
        foreach ($vehicles as $vehicle) {

            $required_category = $this->required_categories->where('type_id', $vehicle->type)->first();

            $order_type_duration = $required_category['order_waypoints']
                ? (
                $vehicle->tariff_type === Tariff::TIME_CALCULATION
                    ? $required_category['order_duration']
                    : round($required_category['order_waypoints']['distance'] / 1000)
                ) : $required_category['order_duration'];

            $cost = $vehicle->calculateCost($required_category['order_type'], $order_type_duration, Price::TYPE_CASHLESS_WITHOUT_VAT, $required_category['order_params']);

            $delivery = $vehicle->calculateDeliveryCost(
                $required_category['order_waypoints']
                    ? implode(',', $required_category['order_waypoints']['coordinates'])
                    : $this->details['coordinates']);
            if (is_null($delivery)) {
                Throw new InvalidArgumentException("wrong delivery for #{$vehicle->id}", 500);
            }
            /* $items->push([
                 'Name' => $vehicle->name,
                 'Price' => ($order_type === 'shift' ?  $vehicle->sum_day : $vehicle->sum_hour),
                 'Quantity' => $duration,
                 'Amount' => $cost,
                 'Tax' => 'vat20',
             ]);
             if ($delivery > 0) {
                 $items->push([
                     'Name' => "Доставка {$vehicle->name}",
                     'Price' => $delivery,
                     'Quantity' => 1,
                     'Amount' => $delivery,
                     'Tax' => 'vat20',
                 ]);
             }*/

            $sum += $delivery + $cost;
        }

        $items->push([
            'Name' => 'Оказание услуг по предоставлению автотранспорта, строительной техники и механизмов',
            'Price' => $sum,
            'Quantity' => 1,
            'Amount' => $sum,
            'Tax' => 'vat20',
        ]);
        $integration = $this->contractorCompanyBranch->integration();
        $shop_id = $integration
            ? $integration->tinkoff_shop_id

            : (config('app.env') === 'production' ? '274569' : '700000544');//'700000038';
        $shops->push([
            'ShopCode' => $shop_id,
            'Amount' => $sum,
            'Fee' => $integration ? round($sum * 0.15) : 0,
        ]);

        $this->details['amount'] = $sum;


        return ['items' => $items->toArray(), 'shops' => $shops->toArray()];
    }

    function prepareFromOffer(LeadOffer $offer, $value_added = [])
    {
        $shop_id = config('app.env') === 'production' ? '274569' : '700000544';
        $items = collect([]);
        $shops = collect([]);

        $value_added = collect($value_added)->unique('position_id');

        $amount = $offer->amount + numberToPenny($value_added->sum('value_added'));

        $items->push([
            'Name' => 'Оказание услуг по предоставлению автотранспорта, строительной техники и механизмов.',
            'Price' => $amount,
            'Quantity' => 1,
            'Amount' => $amount,
            'Tax' => 'vat20',
        ]);
        $shops->push([
            'ShopCode' => $shop_id,
            'Amount' => $amount,
            'Fee' => 0,
        ]);

        $this->details['amount'] = $amount;


        return ['items' => $items->toArray(), 'shops' => $shops->toArray()];

    }

    function setVehicles($vehicles)
    {
        $this->vehicles = $vehicles;

        return $this;
    }

    function setDispatcherOrder()
    {
        $this->from_dispatcher = true;

        return $this;
    }

    function setAmount($amount)
    {
        $this->details['amount'] = $amount;

        return $this;
    }

    function setDateFrom(Carbon $date_from)
    {
        $this->date_from = $date_from;

        return $this;
    }

    function setCustomer(CompanyBranch $companyBranch)
    {
        $this->customerCompanyBranch = $companyBranch;

        return $this;
    }

    function setContractor(CompanyBranch $companyBranch)
    {
        $this->contractorCompanyBranch = $companyBranch;

        return $this;
    }

    function setContractorRequisites($contractorRequisites)
    {
        $this->contractorRequisites = $contractorRequisites;

        return $this;
    }

    function setDispatcherCustomer(Customer $customer)
    {
        $this->dispatcherCustomer = $customer;

        return $this;
    }

    function setDetails($details)
    {
        if (!is_object($details)) {
            $details = (object)$details;
        }
        $this->details['address'] = $details->address;
        $this->details['contact_person'] = $details->contact_person;
        $this->details['region_id'] = $details->region_id ?: 0;
        $this->details['city_id'] = $details->city_id ?: 0;
        $this->details['coordinates'] = $details->coordinates;
        $this->details['start_time'] = $details->start_time;
        $this->details['comment'] = $details->comment ?? '';


        return $this;
    }

    function getDetails()
    {
        return $this->details;
    }

    function createProposalForPayment()
    {

        $this->createProposal(Order::STATUS_HOLD)
            ->addVehiclesInOrder();
        //-''>setRepresentativeCommission();
    }

    function createDispatcherOder()
    {
        $this->createProposal(Order::STATUS_HOLD);
    }


    /**
     * Создание заказа из предложения, которое поступило к заявке
     * Привзяка подрядчиков исполнителя и техники к созданному заказу
     * @param LeadOffer $offer
     * Добавленные стоимости для диспетчерского заказа
     * @param array $valueAdded
     * @return $this
     */
    function createFromOffer(LeadOffer $offer, $valueAdded = [])
    {
        $valueAdded = collect($valueAdded);

        $this->createProposal(Order::STATUS_HOLD);
        $order = $this->created_proposal;

        foreach ($offer->positions as $position) {
            $category = $offer->lead->categories->where('id', $position->category_id)->first();

            $duration = $category->pivot->order_duration;

            $date_from = Carbon::parse($category->pivot->date_from);

            if ($position->worker instanceof Machinery) {

                $order->attachCustomVehicles($position->worker->id, $position->amount, $date_from, $category->pivot->order_type, $duration, 0, 0,
                    $category->pivot->waypoints,
                    $category->pivot->params);

            } else {

                $order->attachDispatcherContractor($position->worker->id, $position->category_id, $position->amount, $date_from, $category->pivot->order_type, $duration, 0, $position->waypoints, $position->params);

                /** Привзяка добавленной стоимости из предложения. */

                $va = new ValueAdded([
                    'amount' => $position->value_added, //Стоимость в копейках уже заранее указана
                    'order_id' => $order->id,
                    'owner_id' => $offer->company_branch->id,

                ]);
                $va->worker()->associate($position->worker);
                $va->save();
            }

            /** Привзяка добавленной стоимости конечного диспетчера. */
            if ($va_dispatcher = $valueAdded->where('position_id', $position->id)->first()) {

                $va_dispatcher = new ValueAdded([
                    'amount' => numberToPenny($va_dispatcher['value_added']), //Перевод в копейки
                    'order_id' => $order->id,
                    'owner_id' => $this->created_proposal->company_branch->id,

                ]);
                $va_dispatcher->worker()->associate($position->worker);
                $va_dispatcher->save();
            }

            $this->created_proposal->types()->attach($position->category_id, ['brand_id' => 0, 'comment' => ('')]);


        }
        return $this;
    }


    private function createProposal($status = Order::STATUS_OPEN, $details = null)
    {
        if ($details) {
            $this->setDetails($details);
        }

        $this->created_proposal = Order::create([
            'type' => $this->from_dispatcher ? 'dispatcher' : 'client',
            'amount' => $this->details['amount'] ?? 0,
            'company_branch_id' => $this->customerCompanyBranch->id,
            'creator_id' => $this->initiator->id,
            'user_id' => Auth::id(),
            'date_from' => $this->date_from,
            'contact_person' => $this->details['contact_person'],
            'region_id' => $this->details['region_id'],
            'city_id' => $this->details['city_id'],
            'address' => trim($this->details['address']),
            'coordinates' => $this->details['coordinates'],
            'start_time' => $this->details['start_time'],
            'comment' => $this->details['comment'],
            'channel' => request()->channel,
            'status' => $status,
            'domain_id' => RequestHelper::requestDomain()->id,
            'contractor_id' => $this->contractorCompanyBranch->id,
            'system_commission' => Option::get('system_commission')
        ]);

        $this->created_proposal->customer()->associate($this->dispatcherCustomer ?: $this->customerCompanyBranch);

        if($this->contractorRequisites) {
            $this->created_proposal->contractorRequisite()->associate($this->contractorRequisites);
        }

        foreach ($this->vehicles as $vehicle) {

            $category = $this->required_categories->where('type_id', $vehicle->type)->first();
            $this->created_proposal->types()->attach($vehicle->type,
                [
                    'brand_id' => ($vehicle->brand ? $vehicle->brand->id : 0),
                    'comment' => (''),
                    'waypoints' => $category ? ($category['order_waypoints'] ? json_encode($category['order_waypoints']) : null) : null,
                    'params' => $category ? ( $category['order_params'] ? json_encode($category['order_params']) : null) : null,
                ]);
        }


        return $this;
    }


    function addVehiclesInOrder()
    {

        foreach ($this->vehicles as $vehicle) {

            $required_category = $this->required_categories->where('type_id', $vehicle->type)->first();

            $order_type_duration = $required_category['order_waypoints']
                ? (
                $vehicle->tariff_type === Tariff::TIME_CALCULATION
                    ? $required_category['order_duration']
                    : round($required_category['order_waypoints']['distance'] / 1000)
                ) : $required_category['order_duration'];

            $this->created_proposal
                ->attachCustomVehicles(
                    $vehicle->id,
                    $vehicle->calculateCost($required_category['order_type'], $order_type_duration, Price::TYPE_CASHLESS_WITHOUT_VAT, $required_category['order_params']),
                    $required_category['date_from'] ?? $this->date_from,
                    $required_category['order_type'],
                    $required_category['order_duration'],
                    $vehicle->calculateDeliveryCost($this->details['coordinates']),
                    0,
                    $required_category['order_waypoints'],
                    $required_category['order_params']
                );
        }

        return $this;
    }



    /*private function createOffer()
    {
        $this->created_offer = Offer::create([
            'user_id' => $this->contractorUser->id,
            'proposal_id' => $this->created_proposal->id,
            'sum' => $this->created_proposal->sum,
            'is_win' => 1,
            'comment' => '',
        ]);
        $attach = [];
        foreach ($this->vehicles as $machine) {
            $attach[$machine->id] = ['sum' => $machine->pivot->amount];
        }

        $this->created_offer->machines()->attach($attach);

        return $this;
    }*/


    /*    function createEmptyTimestamp()
        {
            $contractor_timestamps = new Proposal\ContractorTimestamps([]);
            $this->created_proposal->contractor_timestamps()->save($contractor_timestamps);
            return $this;
        }*/
    /**
     * @param mixed $initiator
     * @return OrderManagement
     */
    public function setInitiator(User $initiator)
    {
        $this->initiator = $initiator;

        return $this;
    }

    private function setRepresentativeCommission()
    {
        $options = config('global_options');
        //$this->created_proposal->regional_representative_id = $this->contractorUser->regional_representative_id;
        $system_commission = $this->created_proposal->system_commission;
        $representative_commission = $options->where('key', 'representative_commission')->first()->value ?? 0; // Option::find('representative_commission')->value ?? 0;

        foreach ($this->created_proposal->vehicles as $vehicle) {
            OrderComponent::query()
                ->where('order_id', $this->created_proposal->id)
                ->where('machinery_id', $vehicle->id)
                ->update([
                    'regional_representative_commission' => (($vehicle->user->regional_representative && ($vehicle->user->regional_representative->commission->enable ?? false))
                        ? (($system_commission > $vehicle->user->regional_representative->commission->percent)
                            ? $vehicle->user->regional_representative->commission->percent
                            : $system_commission)
                        : (($representative_commission < $system_commission)
                            ? $representative_commission
                            : $system_commission))
                ]);
        }

        return $this;
    }
}

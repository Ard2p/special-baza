<?php

namespace App\Service;

use App\City;
use App\Directories\TransactionType;
use App\Machines\Brand;
use App\Machines\Type;
use App\Marketing\EmailLink;
use App\Marketing\SmsLink;
use App\Option;
use App\Role;
use App\Support\Gmap;
use App\Support\Region;
use App\User;
use App\User\BalanceHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class OrderService
{
    private $request;
    private $big_search = false;
    private $errors = [];
    private $user;
    private $current_proposal;
    private $current_region;
    private $current_city;
    private $validator;

    private $date_start;
    private $datetime_start;
    private $date_end;
    private $search_container;
    private $days;
    private $time_type;

    private $merge_request = [];

    private $proposal_sum;

    private $offer;


    public $needle_machines;
    public $coordinates;
    public $search_collection;
    public $created_proposal;
    public $created_offer;
    public $needle_user;


    private $parse_format = 'Y/m/d H:i';


    public function __construct(Request $request = null)
    {
        $request =
            $request
                ?: request();

        if ($request->filled('phone')) {
            $request->merge([
                'phone' => User::trimPhone($request->phone)
            ]);
        }
        if ($request->filled('time')) {
            $request->merge([
                'date' => $request->date . ' ' . $request->time
            ]);
            if ($request->filled('date_end'))
                $request->merge([
                    'date_end' => $request->date_end . ' ' . $request->time
                ]);
        }

        $check = explode(' ', $request->date);
        if (isset($check[2])) {
            $request->merge([
                'date' => $check[0] . ' ' . $check[1]
            ]);
        }
        $check = explode(' ', $request->date_end);

        if (isset($check[2]) && $request->filled('date_end')) {
            $request->merge([
                'date_end' => $check[0] . ' ' . $check[1]
            ]);
        }

        if ($request->filled('sum')) {
            $request->merge([
                'sum' => round(str_replace(',', '.', $request->sum) * 100)
            ]);
        }

        $this->request = $request;

        if (!request()->filled('days')) {
            $this->parseDays();
        }
    }

    function parseDays()
    {
        try {
            $this->date_start =
                Carbon::createFromFormat($this->parse_format, $this->request->input('date')); //->startOfDay();

            $this->datetime_start = Carbon::createFromFormat($this->parse_format, $this->request->input('date'));

            if (!$this->request->filled('date_end')) {

                switch ($this->time_type) {
                    case 'hour':
                        $this->date_end =
                            (clone $this->date_start)->addHour($this->request->days
                                ?: 1);
                        break;
                    case 'change':
                    case 'day':
                        $this->date_end =
                            (clone $this->date_start)->addDay($this->request->days
                                ?: 1);
                        break;
                    case 'week':
                        $this->date_end =
                            (clone $this->date_start)->addDay($this->request->days
                                ?: 1);
                        break;
                    default:
                        $this->date_end =
                            (clone $this->date_start)->addDay($this->request->days
                                ?: 1);
                        break;
                }

            }
            if (!$this->date_end) {
                $this->date_end =
                    Carbon::createFromFormat($this->parse_format, $this->request->input('date_end'))->endOfDay();
            }


            $this->days = $this->date_end->diffInDays($this->date_start);
            $this->days =
                $this->days
                    ?: 1;
            $this->request->merge(['days' => $this->days]);
        } catch (\Exception $e) {

        }

    }

    function getStartDate()
    {
        return $this->date_start;
    }

    function getEndDate()
    {
        return $this->date_end;
    }


    function getDays()
    {
        return $this->days;
    }

    function getValidator()
    {
        return $this->validator;
    }


    function validateErrors()
    {
        $this->validator = $this->validateOrderCreate($this->request->all());//;
        $errors = $this->validator->errors()->getMessages();
        if ($errors) {
            $this->errors = $errors;
            return $this;
        }
        $this->current_region = Region::findOrFail($this->request->input('region'));

        $this->current_city = $this->current_region->cities()->find($this->request->input('city_id'));

        if (!$this->current_city && $this->request->filled('city_id')) {
            $errors['city_id'][] = 'Город не найден в выбраном регионе';
        }
        if ($this->big_search) {
            if (!(json_decode($this->request->big_container, true))) {
                $errors['modals'][] = 'Не выбрана требуемая техника!';
            }
        }
        $this->errors = $errors;

        return $this;
    }

    function search()
    {
        $this->parseDays();

        $collection =
            $this->big_search
                ? $this->bigSearch($this->request)
                : $this->simpleSearch($this->request);

        $regionName = $this->current_region->name;
        $gmap = new Gmap();


        $coordinates = $gmap->getGeometry('Россия ' . $regionName . ' ' . $this->request->input('address'));


        $system_commission = (Option::find('system_commission')->value ?? 0) / 100;
        $request = $this->request;
        foreach ($collection as $user) {
            //  dd($user->needle);die;
            $_sum = 0;
            $user->needle->each(function (
                $v,
                $k) use
            (
                &
                $_sum,
                $request
            ) {
                $_sum += $v->sum_day * $request->days;
                return true;
            });//->sum('sum_day') * $request->days / 100;
            $user->current_sum = $_sum;
            $user->current_commission = $user->current_sum * $system_commission / 100;

        }

        $this->search_collection = $collection;
        $this->coordinates = $coordinates;

        return $this;
    }

    function setNeedleUser($id)
    {
        $this->needle_user = $this->search_collection->where('id', $id)->first();
        $this->needle_machines = $this->needle_user->needle;
        return $this;
    }

    function getOrderSum()
    {
        return $this->needle_user->current_sum;
    }


    function mergeRequest(array $array)
    {

        foreach ($array as $key => $item) {
            $this->merge_request[$key] = $item;
        }

        return $this;
    }

    private function validateOrderCreate($data)
    {
        $request = $this->request;
        $rules = [
            'region'                 => 'required|integer|exists:regions,id',
            'city_id'                => 'required|exists:cities,id',
            'planned_duration_hours' => 'integer|min:1',
            'date'                   => 'required|date|after:' . Carbon::now()->format('Y-m-d H:i'),
            'days'                   => 'required|integer|min:1|',
            'type'                   => 'required|integer',
            'address'                => 'required|string|max:255',
            'sum'                    => 'numeric|min:0',
            'machine_type'           => 'required|in:machine,equipment',
        ];

        if ($request->filled('big_search')) {
            $this->big_search = true;
            $rules = array_merge(['big_container' => 'required|json'], $rules);
            unset($rules['type']);
            if (!$request->has('days')) {
                unset($rules['days']);
            }

        }

        $rules = array_merge($rules, $this->merge_request);


        return Validator::make($data, $rules, [
            'region.required'       => trans('transbaza_proposal.validate_region'),
            'region.integer'        => trans('transbaza_proposal.validate_region'),
            'city_id.integer'       => trans('transbaza_proposal.validate_city'),
            'date.required'         => trans('transbaza_proposal.validate_date'),
            'time.required'         => trans('transbaza_proposal.validate_time'),
            'date.date'             => trans('transbaza_proposal.validate_date_format'),
            'date.date_format'      => trans('transbaza_proposal.validate_date_format'),
            'date.after'            => trans('transbaza_proposal.validate_date_after'),
            'days.required'         => trans('transbaza_proposal.validate_days'),
            'days.integer'          => trans('transbaza_proposal.validate_days_int'),
            'days.min'              => trans('transbaza_proposal.validate_days_min'),
            'type.integer|required' => trans('transbaza_proposal.validate_type'),
            'address.required'      => trans('transbaza_proposal.validate_address'),
            'comment.required'      => trans('transbaza_proposal.validate_comment'),
            'comment.max'           => trans('transbaza_proposal.validate_comment_max'),
            'sum.min'               => trans('transbaza_proposal.validate_sum_min'),
            'sum.numeric'           => trans('transbaza_proposal.validate_sum_numeric'),
            'time_type.integer'     => trans('transbaza_proposal.validate_time_type'),
            'sum.required'          => trans('transbaza_proposal.validate_sum'),
            'phone.required'        => trans('transbaza_proposal.validate_phone'),
            'phone.integer'         => trans('transbaza_proposal.validate_phone'),
            'phone.min'             => trans('transbaza_proposal.validate_phone'),
            'email.integer'         => trans('transbaza_proposal.validate_email'),
            'email.email'           => trans('transbaza_proposal.validate_email'),
            'city_id.required'      => trans('transbaza_proposal.validate_city'),
            'type.integer'          => trans('transbaza_proposal.validate_type'),
            'type.required'         => trans('transbaza_proposal.validate_type'),
            'amount.min'            => trans('transbaza_proposal.validate_amount'),

            'type_id.required' => trans('transbaza_proposal.validate_type'),

            'type_id.integer' => trans('transbaza_proposal.validate_type'),

            'phone.digits' => trans('transbaza_proposal.validate_phone'),

            'brand' => 'integer',


        ]);
    }

    function setProposal(Proposal $proposal)
    {
        $this->current_proposal = $proposal;

        return $this;
    }

    function forUser($id)
    {

        $this->user = User::findOrFail($id);

        return $this;
    }

    function getErrors()
    {
        return $this->errors;
    }

    private function modifyQuery(
        $machine,
        $request,
        $id,
        $brand)
    {


        if (!empty($brand['id']) && $brand['id'] !== 0) {

            $machine->where('brand_id', $brand['id']);
        }
        #  if ($request->input('type') !== '0') {

        $machine->whereType($id);
        #  }
        if ($request->filled('sum_hour') && $request->filled('sum_day')) {

            $machine->where('sum_hour', '<=', $request->input('sum_hour') * 100);
            $machine->where('sum_day', '<=', $request->input('sum_day') * 100);

        } elseif ($request->filled('sum_hour')) {

            $machine->where('sum_hour', '<=', $request->input('sum_hour') * 100);

        } elseif ($request->filled('sum_day')) {

            $machine->where('sum_day', '<=', $request->input('sum_day') * 100);

        }


        //->addDays($request->input('days') - 1);

        $machine->where('region_id', $request->input('region'))
            ->where('city_id', $request->input('city_id'))
            ->checkAvailable($this->date_start, $this->date_end);

        return $machine;
    }

    function setDaysByAmount()
    {
        $this->time_type = $this->request->time_type;
        switch (Widget::getTimeType($this->request->time_type)) {
            case 'hour':

                // $end_date->addHours($this->request->amount);
                $days = round($this->request->amount / 24);
                break;
            case 'change':
                //   $end_date->addDays($this->request->amount);
                $days = $this->request->amount;
                break;
            case 'day':
                //  $end_date->addDays($this->request->amount);
                $days = $this->request->amount;
                break;
            case 'week':
                //   $end_date->addWeek($this->request->amount);
                $days = $this->request->amount * 7;
                break;
        }

        $this->request->merge(['days' => $days]);

        return $this;
    }

    private function getCollection(
        Request $request,
        $ids)
    {
        $request->merge([
            'date' => $request->date . ' ' . $request->time,

        ]);
        $users = User::query();
        $users->with([
            'machines' => function ($q) use
            (
                $request,
                $ids
            ) {
                //  $q = $this->modifyQuery($q, $request, $id);
                $q->whereIn('type', $ids->pluck('id'));
                $q->with('brand', '_type', 'region', 'freeDays', 'user');
            }
        ]);
        foreach ($ids as $type) {

            $count = $ids->where('id', $type['id']);
            if (!empty($type['brand']['id']) && $type['brand']['id'] != '0') {
                $count =
                    $count->filter(function ($v) use
                    (
                        $type
                    ) {
                        if (isset($v['brand']['id'])) {
                            return $v['brand']['id'] == $type['brand']['id'];
                        }
                    });
            }
            $users->whereHas('machines', function ($q) use
            (
                $request,
                $type
            ) {
                //    $machine = Machinery::with('brand', '_type', 'region', 'freeDays', 'user');
                $this->modifyQuery($q, $request, $type['id'], $type['brand']);
            }, '>=', count($count));
        }


        return $users->get();
    }

    function prepareCountForBigSearch()
    {
        $pureCollection = [];
        $request_ids = json_decode($this->request->big_container, true);

        foreach ($request_ids as $machine) {

            for ($i = 0; $i < $machine['count']; ++$i) {
                $pureCollection[] = $machine;
            }
        }

        return collect($pureCollection);
    }


    private function bigSearch()
    {

        $request = $this->request;
        $ids = $this->prepareCountForBigSearch();


        $this->search_container = $ids;

        $all = $this->getCollection($request, $ids);
        //dd($all);
        foreach ($all as $item) {
            $item->needle = collect([]);
            $clone_collect = clone $item->machines;
            foreach ($ids as $id) {
                $curr_machine = $clone_collect->where('type', $id['id'])->first();
                $item->needle = $item->needle->push($curr_machine);

                foreach ($clone_collect as $key => $machine) {
                    if ($machine->id === $curr_machine->id) {
                        $clone_collect->forget($key);
                    }
                }
            }
        }

        return $all;
    }

    function getSearchContainer()
    {
        return $this->search_container;
    }


    private function simpleSearch()
    {
        $request = $this->request;

        $this->search_container =
            collect([
                [
                    'id' => $request->type, 'comment' => $request->comment, 'brand' => [
                    'id' => $request->brand
                        ?: 0
                ]
                ]
            ]);

        $all = $this->getCollection($request, $this->search_container);

        foreach ($all as $item) {
            $item->needle = collect([]);
            $item->needle->push($item->machines->where('type', $request->type)->first());

        }
        return $all;

    }

    function flush()
    {
        $this->errors = [];
        $this->user = null;
        return $this;
    }

    function createProposal(
        $status = 'open',
        $sum = null)
    {
        if (is_null($sum)) {
            $sum = $this->getProposalSum();
        }

        $this->created_proposal = Proposal::create([
            'sum'     => $sum,
            'user_id' => $this->user->id,
            'date'    => $this->datetime_start->format('Y-m-d H:i:s'),
            'days'    => $this->request->days
                ?: $this->days,

            'region_id'              => $this->current_region->id,
            'city_id'                => $this->current_city->id,
            // 'brand_id' => ($request_search['brand'] ? Brand::findOrFail($request_search['brand'])->id : 0),
            'address'                => trim($this->request->address),
            'end_date'               => $this->date_end->format('Y-m-d H:i:s'),
            'comment'                => $this->request->comment,
            'planned_duration_hours' => null,
            'status'                 => Proposal::status($status),
            'system_commission'      => Option::get('system_commission')
        ]);

        $this->createEmptyTimestamp();
        foreach ($this->search_container as $item) {
            $this->created_proposal->types()->attach($item['id'], [
                'brand_id'          => ($item['brand']
                    ? $item['brand']['id']
                    : 0), 'comment' => ($item['comment'] ?? '')
            ]);
        }


        return $this;
    }

    function createEmptyTimestamp()
    {
        $contractor_timestamps = new Proposal\ContractorTimestamps([]);
        $this->created_proposal->contractor_timestamps()->save($contractor_timestamps);
        return $this;
    }

    function acceptOffer()
    {

        $this->created_proposal = $this->offer->proposal;
        $this->needle_user = $this->offer->user;

        $this->needle_machines = $this->offer
            ->machines()
            ->checkProposal($this->created_proposal->region_id, $this->created_proposal->type_ids)
            ->checkAvailable($this->created_proposal->date, $this->created_proposal->end_date)
            ->get();


        if (count($this->created_proposal->type_ids) !== $this->needle_machines->count()) {
            $this->errors = ['modals' => 'Невозможно принять предложение. Техника уже занята.'];
            return $this;
        }
        if ($this->created_proposal->sum !== $this->offer->sum) {
            $this->created_proposal->sum = $this->offer->sum;
        }
        $this->offer->update(['is_win' => 1]);
        $this->setMachineOrderDates()->setRepresentativeCommission()->decrementBalance();

        $this->created_proposal->update(['status' => Proposal::status('accept')]);

        (new EventNotifications())->newOrder($this->created_proposal);
        return $this;
    }


    function createOrder()
    {

        $this->createProposal('accept', $this->getOrderSum());

        $this->created_proposal->types()->detach();
        foreach ($this->needle_machines as $machine) {
            $this->created_proposal->types()->attach($machine->type, ['brand_id' => $machine->brand_id]);
        }


        $this->createOffer()->setMachineOrderDates()->setRepresentativeCommission()->decrementBalance();

        (new EventNotifications())->newOrder($this->created_proposal);

        return $this;
    }

    private function setRepresentativeCommission()
    {
        $options = config('global_options');
        $this->created_proposal->regional_representative_id = $this->needle_user->regional_representative_id;
        $system_commission = $this->created_proposal->system_commission;
        $representative_commission =
            $options->where('key', 'representative_commission')->first()->value ?? 0; // Option::find('representative_commission')->value ?? 0;
        $this->created_proposal->regional_representative_commission =
            ($this->needle_user->regional_representative && ($this->needle_user->regional_representative->commission->enable ?? false))
                ? (($system_commission > $this->needle_user->regional_representative->commission->percent)
                ? $this->needle_user->regional_representative->commission->percent
                : $system_commission)
                : (($representative_commission < $system_commission)
                ? $representative_commission
                : $system_commission);
        $this->created_proposal->save();
        return $this;
    }

    private function setMachineOrderDates()
    {
        foreach ($this->needle_machines as $machine) {
            $machine->setOrderDates($this->created_proposal->date, $this->created_proposal->end_date, $this->created_proposal->id);
        }
        return $this;
    }

    private function createOffer()
    {
        $this->created_offer = Offer::create([
            'user_id'     => $this->needle_user->id,
            'proposal_id' => $this->created_proposal->id,
            'sum'         => $this->created_proposal->sum,
            'is_win'      => 1,
            'comment'     => '',
        ]);
        $sync = [];
        foreach ($this->needle_machines as $machine) {
            $sync[$machine->id] = ['sum' => $machine->sum_day];
        }

        $this->created_offer->machines()->syncWithoutDetaching($sync);

        return $this;
    }

    private function decrementBalance()
    {
        BalanceHistory::create([
            'user_id'      => $this->user->id,
            'admin_id'     => 0,
            'old_sum'      => $this->user->getBalance('customer'),
            'new_sum'      => $this->user->getBalance('customer') - $this->created_proposal->sum,
            'type'         => BalanceHistory::getTypeKey('reserve'),
            /*           'requisite_id' => Auth::user()->getActiveRequisite()->id,
                       'requisite_type' => Auth::user()->getActiveRequisiteType(),*/
            'billing_type' => 'customer',
            'sum'          => $this->created_proposal->sum,
            'reason'       => TransactionType::getTypeLng('reserve') . ' Заявка #' . $this->created_proposal->id,
        ]);
        $this->user->decrementCustomerBalance($this->created_proposal->sum);
    }

    /**
     * @return mixed
     */
    public function getProposalSum()
    {
        return $this->proposal_sum;
    }

    /**
     * @param mixed $proposal_sum
     */
    public function setProposalSum($proposal_sum)
    {
        $this->proposal_sum = $proposal_sum;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getOffer()
    {
        return $this->offer;
    }

    /**
     * @param mixed $offer
     */
    public function setOffer($offer)
    {
        $this->offer = $offer;

        return $this;
    }

}
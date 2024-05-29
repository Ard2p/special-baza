<?php

namespace App\Service;

use App\City;
use App\Machines\Brand;
use App\Machines\Type;
use App\Marketing\EmailLink;
use App\Marketing\SmsLink;
use App\Option;
use App\Role;
use App\Support\Region;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProposalService
{
    private $request;
    private $errors = [];
    private $user;
    private $sum;
    private $current_proposal;
    public $created_proposal;
    const DATE_FORMAT = 'Y-m-d H:i';


    public function __construct(Request $request = null)
    {
        $request = $request ?: request();
        if ($request->filled('phone')) {
            $request->merge([
                'phone' => User::trimPhone($request->phone)
            ]);
        }
        if ($request->filled('sum')) {
            $request->merge([
                'sum' => round(str_replace(',', '.', $request->sum) * 100)
            ]);
            $this->setSum($request->sum);
        }
        $this->request = $request;
    }

    private function validateProposalCreate($data)
    {
        return Validator::make($data, [

            'region_id' => 'required|integer||exists:regions,id',
            'city_id' => [
                'required',
                'integer',
                Rule::exists('cities', 'id')->where('region_id', $data['region_id'] ?? 0)
            ],
            'date' => 'required|date|date_format:Y-m-d H:i|after:' . Carbon::now()->addDay()->format('Y-m-d'),
            'shifts_count' => 'required|integer|min:1|',
            'address' => 'required|string|max:255',
            'comment' => 'required|string|max:500',
            'sum' => 'required|numeric|min:1',
            'vehicles' => 'required|array',
            'vehicles.*.category_id' => 'required|exists:types,id',
            'vehicles.*.brand_id' => 'exists:types,id',
            'vehicles.*.comment' => 'string|max:500',
        ],
            [
                'region.required' => 'Не выбран регион.',
                'region.integer' => 'Не выбран регион.',
                'city_id.integer' => 'Не выбран город.',
                'date.required' => 'Не выбрана дата заказа',
                'time.required' => 'Некорректное время',
                'date.date' => 'Введите корректный формат даты.',
                'date.date_format' => 'Введите корректный формат даты.',
                'date.after' => 'Дата должна быть не раньше, чем сегодня.',
                'days.required' => 'Не указано кол-во смен.',
                'days.integer' => 'Кол-во смен должно быть целым числом.',
                'days.min' => 'Минимальное кол-во смен: 1.',
                'type.integer|required' => 'Не выбрана категория техники.',
                'address.required' => 'Не заполнен адрес проведения работ.',
                'comment.required' => 'Не заполнен комментарий к заявке.',
                'comment.max' => 'Максимальное кол-во симоволов: 500.',
                'sum.min' => 'Сумма не может быть отрицательной.',
                'sum.numeric' => 'Сумма должна быть числом.',
                'time_type.integer' => 'Укажите единицу времени.',
                'sum.required' => 'Укажите ваше ограничение по бюджету.',
                'phone.required' => 'Некорректный номер телефона.',
                'phone.integer' => 'Некорректный номер телефона.',
                'phone.min' => 'Некорректный номер телефона.',
                'email.integer' => 'Некорректный Email.',
                'email.email' => 'Некорректный Email',
                'city_id.required' => 'Не выбран город.',
                'type.integer' => 'Не указана категория техники.',
                'type.required' => 'Не указана категория техники.',
                'amount.min' => 'Минимальное кол-во: 1.',
                'amount.integer' => '',
            ]
        );
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

    function validateRequest()
    {
        return  $this->validateProposalCreate($this->request->all());
    }

    function getErrors()
    {
        return $this->errors;
    }


    function flush()
    {
        $this->errors = [];
        $this->user = null;
        return $this;
    }

    function create($status =  'open')
    {
        $request = $this->request->all();

        $date = Carbon::createFromFormat(self::DATE_FORMAT, $request['date']);
        $end_date = (clone $date)->addDays($request['shifts_count'] - 1)->endOfDay();

        DB::beginTransaction();
        $this->created_proposal = Proposal::create([
            'sum' => $this->getSum(),
            'user_id' => $this->user->id,
            'date' => $date->format('Y-m-d H:i:s'),
            'days' => $this->request->shifts_count,

            'region_id' => $request['region_id'],
            'city_id' => $request['city_id'],
            // 'brand_id' => ($request_search['brand'] ? Brand::findOrFail($request_search['brand'])->id : 0),
            'address' => trim($this->request->address),
            'end_date' => $end_date->format('Y-m-d H:i:s'),
            'comment' => '',
            'planned_duration_hours' => null,
            'status' => Proposal::status($status),
            'system_commission' => Option::get('system_commission')
        ]);

        $this->createEmptyTimestamp();

        foreach ($request['vehicles'] as $item) {
            $this->created_proposal->types()->attach($item['category_id'], ['brand_id' => ($item['brand_id'] ??  0), 'comment' => ($item['comment'] ?? '')]);
        }

        DB::commit();

        (new EventNotifications())->newProposal($this->created_proposal);





        return $this;
    }

    function createEmptyTimestamp()
    {
        $contractor_timestamps = new Proposal\ContractorTimestamps([]);
        $this->created_proposal->contractor_timestamps()->save($contractor_timestamps);
        return $this;
    }

    /**
     * @param mixed $sum
     */
    public function setSum($sum)
    {
        $this->sum = $sum;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSum()
    {
        return $this->sum;
    }

}
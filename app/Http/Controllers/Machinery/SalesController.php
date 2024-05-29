<?php

namespace App\Http\Controllers\Machinery;

use App\City;
use App\Machinery;
use App\Machines\Sale;
use App\Machines\SaleOffer;
use App\Machines\Type;
use App\Support\Region;
use http\Client\Curl\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SalesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $alias = null)
    {
       $machines = Machinery::with('sale')->whereHas('sale')->get();

        if (!is_null($alias)) {
            $user = \App\User::with(['machines' => function ($q) use ($request) {
                if ($request->filled('type_id')) {
                    $q->whereType($request->type_id);
                }
                $q->whereHas('sale');
                if ($request->filled('region')) {
                    $q->whereRegionId($request->region);
                }
                if ($request->filled('city_id')) {
                    $q->whereCityId($request->city_id);
                }
            }])
                // ->whereContractorAliasEnable(1)
                ->whereContractorAlias($alias)->firstOrFail();


            $machines = $user->machines;
        } else {
            $user = null;
            $machines = Machinery::with('user');
            $machines->whereHas('sale');
            if ($request->filled('type_id')) {
                $machines->whereType($request->type_id);
            }
            if ($request->filled('region')) {
                $machines->whereRegionId($request->region);
            }
            if ($request->filled('city_id')) {
                $machines->whereCityId($request->city_id);
            }

            $machines = $machines->paginate(20);
        }


        $regions = Region::whereCountry('russia')->whereHas('machines', function ($q) use ($user) {
            if ($user) {
                $q->whereUserId($user->id);
            }

        })->get();

        $types = Type::whereHas('machines', function ($q) use ($user) {
            if ($user) {
                $q->whereUserId($user->id);
            }
        })->get();

        $initial_type = $request->filled('type_id') ? Type::find($request->type_id) : '';
        $initial_region = ($request->filled('region') ? Region::find($request->region) : '');
        $checked_city_source = ($request->filled('city_id') ? City::find($request->city_id) : '');


        $time_type = [
            [
                'id' => 1,
                'name' => 'Час',
            ],
            [
                'id' => 2,
                'name' => 'Смена',
            ],
            [
                'id' => 3,
                'name' => 'День',
            ],
            [
                'id' => 4,
                'name' => 'Неделя',
            ],
            [
                'id' => 5,
                'name' => 'Месяц',
            ],
        ];
        return view('user.machines.sale_page', compact('machines', 'time_type', 'user', 'types', 'regions', 'initial_region', 'initial_type', 'checked_city_source'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $machine = Machinery::whereHas('sale')->findOrFail($id);
        $rules = [
            'g-recaptcha-response' => 'required|captcha',
        ];
        if (!Auth::check()) {
            $rules = array_merge([
                'email' => 'required|string|email|max:255|unique:sale_offers,email',
                'phone' => 'required|numeric|digits:11|unique:sale_offers,phone',

            ], $rules);
        }

        $validator = Validator::make($request->all(), $rules, [
            'email.required' => 'Поле Email обязательно для заполнения.',
            'email.email' => 'Некорректный email.',
            'email.unique' => 'Вы уже добавляли заявку с таким email',
            'phone.unique' => 'Вы уже добавляли заявку с таким телефоном',
            'phone.required' => 'Поле телефон обязательно для заполнения.',
            'phone.digits' => 'Некорректный телефон',
            'g-recaptcha-response.required' => 'Пройдите проверку',
            'g-recaptcha-response.captcha' => 'Пройдите проверку',

        ]);
        $errors = $validator
            ->errors()
            ->getMessages();
        if (Auth::check()) {
            if (SaleOffer::whereUserId(Auth::id())->first()) {
                $errors['modals'] = ['Вы уже отправляли заявку к этому объявлению'];
            }
        }

        if ($errors) return response()->json($errors, 419);


        SaleOffer::create([
            'email' => \Auth::check() ? auth()->user()->email : $request->email,
            'phone' => \Auth::check() ? auth()->user()->phone : $request->phone,
            'comment' => $request->comment,
            'user_id' => \Auth::check() ? auth()->id() : 0,
            'machinery_id' => $machine->id,
        ]);

        return response()->json(['message' => 'Заявка отправлена!']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}

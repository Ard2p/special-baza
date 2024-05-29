<?php

namespace App\Http\Controllers\Ads;

use App\Ads\Advert;
use App\Ads\AdvertBlackList;
use App\Ads\AdvertCategory;
use App\Ads\AdvertOffer;
use App\Ads\Reward;
use App\City;
use App\Jobs\SmsAdvertFriendShare;
use App\Mail\ShareDealToFriends;
use App\Marketing\EmailLink;
use App\Marketing\FriendsList;
use App\Marketing\SmsLink;
use App\Support\Region;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AdvertsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $adverts = Advert::currentUser()->orderBy('created_at', 'desc')->get();

        $agents = Advert::imAgent()->orderBy('created_at', 'desc')->get();

        $im_contractor = Advert::imContractor()->orderBy('created_at', 'desc')->get();


        return view('user.adverts.index', compact('adverts', 'agents', 'im_contractor'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $regions = Region::whereCountry('russia')->get();
        $categories = AdvertCategory::all();
        $rewards = Reward::all();
        return view('user.adverts.create', compact('regions', 'categories', 'rewards'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rules = [
            'region_id' => 'required|exists:regions,id',
            'category_id' => 'required|exists:advert_categories,id',
            'city_id' => 'required|exists:cities,id',
            'address' => 'required|string',
            'sum' => 'required|numeric|min:0',
            'name' => 'required|string|min:10',
            'description' => 'required|string',
            'reward_id' => 'required|exists:rewards,id',
            'actual_date' => 'required|date|after:' . Carbon::now()->subDay(1)->format('Y-m-d'),
            'photo' => 'required'
        ];
        if ($request->reward_id !== '1') {
            $rules = array_merge($rules, ['reward_text' => 'required|string',]);
        }
        $errors = Validator::make($request->all(), $rules,
            [
                'region_id.required' => trans('transbaza_adverts.validate_region'),
                'name.required' => trans('transbaza_adverts.validate_name'),
                'name.min' => trans('transbaza_adverts.validate_name_min'),
                'category_id.required' => trans('transbaza_adverts.validate_category'),
                'city_id.required' => trans('transbaza_adverts.validate_city'),
                'address.required' => trans('transbaza_adverts.validate_address'),
                'sum.required' => trans('transbaza_adverts.validate_sum'),
                'sum.min' => trans('transbaza_adverts.validate_sum_min'),
                'description.required' => trans('transbaza_adverts.validate_description'),
                'reward_id.required' => trans('transbaza_adverts.validate_reward'),
                'reward_text.required' => trans('transbaza_adverts.validate_reward_text'),
                'actual_date.required' => trans('transbaza_adverts.validate_date'),
                'actual_date.date' => trans('transbaza_adverts.validate_date_format'),
                'actual_date.after' => trans('transbaza_adverts.validate_date_after'),
                'photo.required' => trans('transbaza_adverts.validate_photo'),
            ]
        )->errors()->getMessages();

        if ($errors) return response()->json($errors, 419);

        $advert = Advert::create([
            'name' => $request->name,
            'alias' => $request->name,
            'region_id' => $request->region_id,
            'category_id' => $request->category_id,
            'city_id' => $request->city_id,
            'address' => $request->address,
            'description' => $request->description,
            'reward_id' => $request->reward_id,
            'reward_text' => $request->reward_text,
            'actual_date' => Carbon::parse($request->actual_date),
            'photo' => $request->photo,
            'sum' => $request->sum,
            'user_id' => Auth::id(),
            'moderated' => Auth::user()->hasModerate('adverts') ? 1 : 0
        ]);

        return response()->json(['message' => trans('transbaza_adverts.advert_create'), 'url' => $advert->url]);
    }

    function setContractor(Request $request, $alias)
    {
        if (!Auth::check()) {
            return response()->json(['auth_error' => [view('includes.auth_error',
                [
                    'message' => 'Вы не авторизованы для участия в сделке',
                    'url' => route('login', ['redirect_back' => route('adverts', $alias)]),
                ])->render()]], 419);
        }
        $advert = Advert::checkAvailable()->whereModerated(1)->whereAlias($alias)->where('user_id', '!=', Auth::id())
            ->firstOrFail();

        $errors = Validator::make($request->all(), [
            'comment' => 'required|string',
            'sum' => 'required|numeric|min:0',
        ],
            [
                'comment.required' =>trans('transbaza_adverts.validate_comment'),
                'sum.required' => trans('transbaza_adverts.validate_sum'),
                'sum.min' => trans('transbaza_adverts.validate_sum_min'),
            ]
        )->errors()->getMessages();

        if (AdvertOffer::userHasOffer(Auth::id(), $advert->id)->first()) {
            $errors['modals'] = [trans('transbaza_adverts.offer_exist')];
        }

        if ($errors) return response()->json($errors, 419);

        DB::beginTransaction();

        AdvertOffer::create([
            'comment' => $request->comment,
            'sum' => $request->sum,
            'user_id' => Auth::id(),
            'advert_id' => $advert->id,
        ]);

        DB::commit();

        return response()->json(['message' => trans('transbaza_adverts.offer_add')]);
    }

    private function setAgent(Request $request, $advert)
    {
        if (!Auth::check()) {
            return response()->json(['auth_error' => [view('includes.auth_error', ['message' => trans('transbaza_adverts.no_auth'),
                'url' => route('login', ['redirect_back' => route('adverts', $advert->alias)]),
            ])->render()]], 419);
        }

        if ($advert->isAgent()) {
            return response()->json(['modals' => [trans('transbaza_adverts.agent_exist')]], 419);
        }


        $link = $advert->getRefererLink($request->hash);

        $advert->agents()->syncWithoutDetaching([
            Auth::id() => [
                'parent_id' => ($link ? $link->advert->pivot->user_id : $advert->user_id)
            ]
        ]);


        return response()->json(['message' => trans('transbaza_adverts.agent_success')]);
    }

    function submitOffer(Request $request, $alias)
    {
        $advert = Advert::checkAvailable()->whereAlias($alias)
            ->currentUser()
            ->firstOrFail();

        $offer = $advert->offers()->whereIsWin(0)->findOrFail($request->offer_id);

        DB::beginTransaction();
        $offer->update(['is_win' => 1]);
        $advert->update(['status' => 1]);
        DB::commit();

        return response()->json(['message' => trans('transbaza_adverts.contractor_success')]);
    }

    function addFeedback(Request $request, $alias)
    {
        $advert = Advert::checkAccepted()->whereAlias($alias)
            ->currentWinner()
            ->noFeedback()
            ->firstOrFail();

        $errors = Validator::make($request->all(), [
            'feedback' => 'required|string',
            'rate' => 'required|in:1,2,3,4,5',
        ],
            [
                'feedback.required' => trans('transbaza_adverts.empty_feedback'),
                'rate.required' => trans('transbaza_adverts.customer_rate'),
                'rate.in' => trans('transbaza_adverts.customer_rate'),

            ]
        )->errors()->getMessages();
        if ($errors) return response()->json($errors, 419);


        $advert->winner->update([
            'rate' => $request->rate,
            'feedback' => $request->feedback,
        ]);

        return response()->json(['message' => trans('transbaza_adverts.add_feedback')]);
    }


    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $alias)
    {

        $advert = Advert::whereAlias($alias)
            ->firstOrFail();

        if(!$advert->moderated){
            return view('moderate_error');
        }

        if(Auth::check()){
            $hasView = $advert->user_views()->find(Auth::id());
            if(!$hasView){
                DB::beginTransaction();
                $advert->user_views()->syncWithoutDetaching(Auth::id());
                $advert->increment('views');
                DB::commit();
            }
        }else{
            if(!$request->hasCookie('adv_watch_' . $advert->id)){
                $advert->increment('guest_views');
                Cookie::queue(Cookie::make('adv_watch_' . $advert->id, 1, 2880));
            }
        }

        /* if (!$request->filled('hash') && (!$advert->isAgent() || !$advert->hasOffer(Auth::id()))) {
             return response()->view('404', [], 404);
         }*/
        /*route('article_index', ['article' => 'sorry', 'dis_share_email_id' => $share->id]);*/


        if ($request->filled('set_contractor')) {
            return $this->setContractor($request, $advert);
        }

        if ($request->filled('set_agent')) {
            return $this->setAgent($request, $advert);
        }


        $link = $advert->getRefererLink($request->hash);

        $data = ['advert' => $advert, 'link' => $link, 'request' => $request];

        if ($request->filled('get_info')) {
            return response()->json(['data' => view('user.adverts.button_info', $data)->render()]);
        }

        return view('user.adverts.show', $data);

    }

    function send(Request $request, $alias)
    {
        if ($request->type === 'email') {
            return $this->sendMail($request, $alias);
        }

        if ($request->type === 'sms') {
            return $this->sendSms($request, $alias);
        }
    }


    private function sendMail(Request $request, $alias)
    {

        $advert = Advert::checkAvailable()
            ->whereModerated(1)
            ->isAgent()
            ->whereAlias($alias)
            ->firstOrFail();

        $friendsList = FriendsList::whereUserId(Auth::id())
            ->whereIn('id', $request->friends ?: [])
            ->where('id', '!=', $advert->user_id)
            ->whereNotNull('email')
            ->notInBlackListEmail($advert->id)
            ->get();

        foreach ($friendsList as $friend) {
            DB::beginTransaction();
            $hash = str_random(6);
            $link = route('adverts', ['alias' => $advert->alias, 'hash' => $hash]);
            $share = EmailLink::create([
                'friends_list_id' => $friend->id,
                'machine_id' => 0,
                'custom' => 1,
                'link' => $link,
                'hash' => $hash
            ]);
            $advert->sendingEmails()->syncWithoutDetaching([
                $share->id => ['user_id' => Auth::id()]
            ]);
            DB::commit();
            Mail::to($friend->email)->queue(new ShareDealToFriends(trans('transbaza_adverts.mail_theme'), $share));


        }

        return response()->json(['message' => 'Отправлено.']);
    }

    private function sendSms(Request $request, $alias)
    {

        $advert = Advert::checkAvailable()
            ->whereModerated(1)
            ->isAgent()
            ->whereAlias($alias)
            ->firstOrFail();
        $friendsList = FriendsList::whereUserId(Auth::id())
            ->whereIn('id', $request->friends ?: [])
            ->whereNotNull('phone')
            ->where('id', '!=', $advert->user_id)
            ->notInBlackListPhone($advert->id)
            ->get();


        foreach ($friendsList as $friend) {
            DB::beginTransaction();
            $hash = str_random(6);
            $link = route('adverts', ['alias' => $advert->alias, 'hash' => $hash]);
            $share = SmsLink::create([
                'friends_list_id' => $friend->id,
                'link' => $link,
                'machine_id' => 0,
                'custom' => 1,
                'hash' => str_random(6)
            ]);
            $advert->sendingSms()->syncWithoutDetaching([
                $share->id => ['user_id' => Auth::id()]
            ]);
         //   $this->dispatch(new SmsAdvertFriendShare($share, $friend->phone));
            DB::commit();


        }

        return response()->json(['message' => 'Отправлено.']);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $regions = Region::whereCountry('russia')->get();
        $categories = AdvertCategory::all();
        $rewards = Reward::all();
        $advert = Advert::currentUser()->findOrFail($id);
        return view('user.adverts.edit', compact('regions', 'categories', 'rewards', 'advert'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $advert = Advert::currentUser()->findOrFail($id);
        $rules = [
            'region_id' => 'required|exists:regions,id',
            'category_id' => 'required|exists:advert_categories,id',
            'city_id' => 'required|exists:cities,id',
            'address' => 'required|string',
            'sum' => 'required|numeric|min:0',
            'name' => 'required|string|min:10',
            'description' => 'required|string',
            'reward_id' => 'required|exists:rewards,id',
            'actual_date' => 'required|date|after:' . Carbon::now()->subDay(1)->format('Y-m-d'),
            'photo' => 'required'
        ];
        if ($request->reward_id !== '1') {
            $rules = array_merge($rules, ['reward_text' => 'required|string',]);
        }
        $errors = Validator::make($request->all(), $rules,
            [
                'region_id.required' => trans('transbaza_adverts.validate_region'),
                'name.required' => trans('transbaza_adverts.validate_name'),
                'name.min' => trans('transbaza_adverts.validate_name_min'),
                'category_id.required' => trans('transbaza_adverts.validate_category'),
                'city_id.required' => trans('transbaza_adverts.validate_city'),
                'address.required' => trans('transbaza_adverts.validate_address'),
                'sum.required' => trans('transbaza_adverts.validate_sum'),
                'sum.min' => trans('transbaza_adverts.validate_sum_min'),
                'description.required' => trans('transbaza_adverts.validate_description'),
                'reward_id.required' => trans('transbaza_adverts.validate_reward'),
                'reward_text.required' => trans('transbaza_adverts.validate_reward_text'),
                'actual_date.required' => trans('transbaza_adverts.validate_date'),
                'actual_date.date' => trans('transbaza_adverts.validate_date_format'),
                'actual_date.after' => trans('transbaza_adverts.validate_date_after'),
                'photo.required' => trans('transbaza_adverts.validate_photo'),

            ]
        )->errors()->getMessages();

        if ($errors) return response()->json($errors, 419);

        $advert->update([
            'name' => $request->name,
            'region_id' => $request->region_id,
            'category_id' => $request->category_id,
            'city_id' => $request->city_id,
            'address' => $request->address,
            'description' => $request->description,
            'reward_id' => $request->reward_id,
            'reward_text' => $request->reward_text,
            'actual_date' => Carbon::parse($request->actual_date),
            'photo' => $request->photo,
            'sum' => $request->sum,
            'global_show' => $request->global_show ? 1 : 0,
        ]);

        return response()->json(['message' => 'Объявление обновлено.', 'url' => $advert->url]);
    }

    function unsubscribe(Request $request, $alias)
    {
        $advert = Advert::checkAvailable()
            ->whereAlias($alias)
            ->firstOrFail();

        $link = $request->type === 'email'
            ? EmailLink::findOrFail($request->id)
            : SmsLink::findOrFail($request->id);

        if($link->user){
            AdvertBlackList::firstOrCreate([
                'email' => $link->user->email,
                'phone' => $link->user->phone,
                'advert_id' => $link->advert->id,
            ]);
        }else {
            $request->type === 'email' ?
                AdvertBlackList::firstOrCreate([
                    'email' => $link->friend->email,
                    'advert_id' => $link->advert->id,
                ])
                : AdvertBlackList::firstOrCreate([
                'phone' => $link->friend->phone,
                'advert_id' => $link->advert->id,
            ]);
        }

        return response()->json(['message' => 'Вам больше не будут приходить сообщения <br> этого объявления']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }


    function showPublicPage(Request $request, $alias = null)
    {
        if (!is_null($alias)) {
            $user = User::with(['adverts' => function ($q) use ($request) {
                $q->whereModerated(1);
                if ($request->filled('type_id')) {
                    $q->whereCategoryId($request->type_id);
                }
                if ($request->filled('region')) {
                    $q->whereRegionId($request->region);
                }
                if ($request->filled('city_id')) {
                    $q->whereCityId($request->city_id);
                }
                $q->whereStatus(0)->globalShow()->orderBy('created_at', 'desc');
            }])
                // ->whereContractorAliasEnable(1)
                ->whereContractorAlias($alias)->firstOrFail();
            $adverts = $user->adverts;
        } else {
            $user = null;
            $adverts = Advert::globalShow()->whereStatus(0)->whereModerated(1)->with('user');
            if ($request->filled('type_id')) {
                $adverts->whereCategoryId($request->type_id);
            }
            if ($request->filled('region')) {
                $adverts->whereRegionId($request->region);
            }
            if ($request->filled('city_id')) {
                $adverts->whereCityId($request->city_id);
            }

            $adverts = $adverts->orderBy('created_at', 'desc')->paginate(20);
        }


        $regions = Region::whereCountry('russia')->whereHas('adverts', function ($q) use ($user) {
            if ($user) {
                $q->whereUserId($user->id);
            }

        })->get();

        $types = AdvertCategory::whereHas('adverts', function ($q) use ($user) {

            if ($user) {
                $q->whereUserId($user->id);
            }

        })->get();

        $initial_type = $request->filled('type_id') ? AdvertCategory::find($request->type_id) : '';
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
        return view('user.adverts.public_page', compact('adverts', 'time_type', 'user', 'types', 'regions', 'initial_region', 'initial_type', 'checked_city_source'));
    }


}

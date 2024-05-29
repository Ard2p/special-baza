<?php

namespace App\Http\Controllers\Marketing;

use App\Jobs\SmsFriendShare;
use App\Machinery;
use App\Mail\ShareToFriends;
use App\Marketing\EmailLink;
use App\Marketing\FriendsList;
use App\Marketing\SmsLink;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Rap2hpoutre\FastExcel\FastExcel;

class FriendsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $friendsList = FriendsList::where('user_id', Auth::user()->id);
        if ($request->filled('sms_list')) {
            $friendsList->whereNotNull('phone');

            if ($request->filled('get_advert')) {
                $friendsList->with(['sms_links' => function ($q) use ($request){
                    $q->whereHas('advert', function ($q) use ($request) {
                        $q->where('adverts.id', $request->get_advert);
                    });
                    $q->withCount(['advert' =>  function ($q) use ($request) {
                        $q->where('advert_id', $request->get_advert);
                    }]);
                }]);
            }


        }

        if ($request->filled('email_list')) {
            $friendsList->whereNotNull('email');
            if ($request->filled('get_advert')) {
                $friendsList->with(['email_links' => function ($q) use ($request){
                    $q->whereHas('advert', function ($q) use ($request) {
                        $q->where('adverts.id', $request->get_advert);
                    });
                    $q->withCount(['advert' =>  function ($q) use ($request) {
                        $q->where('advert_id', $request->get_advert);
                    }]);
                }]);
            }
        }
        return $request->ajax()
            ? response()->json(['data' => $friendsList->get()])
            : view('marketing.share_friends', compact('friendsList'));
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

    protected function validator(Request $request)
    {
        $rule = [];
        $rulePhone = [
            'required',
            'int',
            'digits:11',
            Rule::unique('friends_lists')->where('user_id', Auth::id())
                ->whereNull('deleted_at')
            // 'unique:friends_lists,phone,NULL,id,user_id,' . Auth::id()
        ];

        $ruleEmail = ['required',
            'string',
            'email',
            'max:255',
            Rule::unique('friends_lists')->where('user_id', Auth::id())
                ->whereNull('deleted_at')
            //|unique:friends_lists,email,NULL,id,user_id,' . Auth::id()
        ];
        if ($request->filled('email') && !$request->filled('phone')) {
            $rule = array_merge($rule, [
                'email' => $ruleEmail
            ]);
        } elseif ($request->filled('phone') && !$request->filled('email')) {
            $rule = array_merge($rule, [
                'phone' => $rulePhone,
            ]);
        } else {
            $rule = array_merge($rule, [
                'email' => $ruleEmail,
                'phone' => $rulePhone,
            ]);
        }
        if ($request->filled('phone')) {
            $request->merge(
                ['phone' => (int)str_replace(
                    [')', '(', ' ', '+', '-'],
                    '',
                    $request->input('phone'))
                ]);
        }
        return Validator::make($request->all(), $rule,
            [
                'email.required' => 'Поле email обязательно для заполнения.',
                'email.email' => 'Некорректный email.',
                'email.unique' => 'Такой email уже есть в списке',
                'phone.unique' => 'Такой телефон  уже есть в списке',
                'phone.required' => 'Поле телефон обязательно для заполнения.',
                'phone.digits' => 'Поле телефон обязательно для заполнения.',

            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $errors = $this->validator($request)
            ->setAttributeNames(['name' => 'Имя'])
            ->errors()
            ->getMessages();

        if ($errors) return response()->json($errors, 419);

        if ($request->filled('email')) {
            FriendsList::create([
                'name' => $request->name,
                'email' => $request->email,
                'user_id' => Auth::id(),
            ]);
        }
        if ($request->filled('phone')) {
            FriendsList::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'user_id' => Auth::id(),
            ]);
        }


        return response()->json(['message' => 'Друг добавлен']);
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $type = $request->type;
        $friend = FriendsList::whereUserId(Auth::id())->findOrFail($id);

        if ($request->filled('get_advert')) {
            $links = $type === 'email'
                ? $friend->email_links()->whereHas('advert', function ($q) use ($request) {
                    $q->where('adverts.id', $request->get_advert);

                })
                : $friend->sms_links()->whereHas('advert', function ($q) use ($request) {
                    $q->where('adverts.id', $request->get_advert);
                });
        } else {
            $links = $type === 'email'
                ? $friend->email_links()->whereConfirmStatus(1)
                : $friend->sms_links()->whereConfirmStatus(1);
        }
        $links = $links->get();

        return response()->json(['data' => view('marketing.share_link_info', compact('type', 'links'))->render()]);
    }

    function share(Request $request)
    {

        $rules = [
            'list' => 'required|in:all,in_list',
            'send_type' => 'required|in:profile,machine',
            'send_by' => 'required|in:phone,email',
        ];

        if ($request->send_type === 'machine') {
            $rules = array_merge($rules, [
                'machine_id' => 'required|exists:machineries,id'
            ]);
        } elseif (($request->send_type === 'profile' && !Auth::user()->contractor_alias_enable)
            || (!Auth::user()->email_confirm && $request->send_by === 'email')
            || (!Auth::user()->phone_confirm && $request->send_by === 'phone')
        ) {
            return response()->json(['send_type' => ['Ваш профиль неактивен.']], 419);
        }
        $errors = Validator::make($request->all(), $rules,
            [
                'list.required' => 'Выберите получателей',
                'send_type.required' => 'Выбреите тип рассылки',
            ])
            ->errors()
            ->getMessages();

        if ($errors) return response()->json($errors, 419);


        $friendsList = FriendsList::whereUserId(Auth::id());
        if ($request->list === 'in_list') {
            $friendsList->whereIn('id', explode(',', $request->friends));
        }
        if ($request->send_by === 'phone') {
            $friendsList->whereNotNull('phone');
        } else {
            $friendsList->whereNotNull('email');
        }

        $friendsList = $friendsList->get();
        DB::beginTransaction();
        foreach ($friendsList as $friend) {

            if ($request->send_by === 'phone') {
                if ($request->send_type === 'machine') {

                    if (!$friend->sms_links()->whereMachineId($request->machine_id)->whereCustom(0)->first()) {
                        $machine = Machinery::currentUser()->find($request->machine_id);
                        if ($machine) {

                            $link = $machine->rent_url;

                            $share = SmsLink::create([
                                'friends_list_id' => $friend->id,
                                'link' => $link,
                                'machine_id' => $machine->id,
                                'hash' => str_random(6)
                            ]);
                            dispatch(new SmsFriendShare($share, $friend->phone));
                        }
                    }

                } else {
                    if (!$friend->sms_links()->whereMachineId(0)->whereCustom(0)->first()) {

                        $link = route('contractor_public_page', Auth::user()->contractor_alias);
                        $share = SmsLink::create([
                            'email' => $friend->email,
                            'friends_list_id' => $friend->id,
                            'link' => $link,
                            'hash' => str_random(6)
                        ]);
                        dispatch(new SmsFriendShare($share, $friend->phone));
                    }
                }
            } else {

                if ($request->send_type === 'machine') {

                    if (!$friend->email_links()->whereCustom(0)->whereMachineId($request->machine_id)->first()) {
                        $machine = Machinery::currentUser()->find($request->machine_id);
                        if ($machine) {

                            $link = $machine->rent_url;
                            $share = EmailLink::create([
                                'friends_list_id' => $friend->id,
                                'machine_id' => $machine->id,
                                'link' => $link,
                                'hash' => str_random(6)
                            ]);
                            Mail::to($friend->email)->queue(new ShareToFriends('TRANS-BAZA.RU - ссылка от друга', $share));
                        }
                    }

                } else {
                    if (!$friend->email_links()->whereCustom(0)->whereMachineId(0)->first()) {
                        $link = route('contractor_public_page', Auth::user()->contractor_alias);
                        $share = EmailLink::create([
                            'friends_list_id' => $friend->id,
                            'link' => $link,
                            'hash' => str_random(6)
                        ]);
                        Mail::to($friend->email)->queue(new ShareToFriends('TRANS-BAZA.RU - ссылка от друга', $share));
                    }
                }
            }

        }
        DB::commit();


        return response()->json(['message' => 'Рассылка принята в обработку']);
    }


    function importFriends(Request $request)
    {

        $errors = Validator::make($request->all(), [
            'excel' => 'required|mimeTypes:' .
                'application/vnd.ms-office,' .
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,' .
                'application/vnd.ms-excel',
            'type' => 'required|in:email,phone'
        ])->errors()
            ->getMessages();


        if ($errors) return response()->json($errors, 419);

        $path = $request->file('excel')->store('excel-files');
        $friends = (new FastExcel())->import(storage_path('app/' . $path));
        $hasErrors = [];
        DB::beginTransaction();
        foreach ($friends as $key => $row) {
            $row = array_values($row);
            $name = $row[0];
            $field = $request->type === 'email' ? $row[1] : User::trimPhone($row[1] ?? '');

            $rulePhone = [
                'phone' => [
                    'required',
                    'int',
                    'digits:11',
                    Rule::unique('friends_lists')->where('user_id', Auth::id())
                        ->whereNull('deleted_at')
                    // 'unique:friends_lists,phone,NULL,id,user_id,' . Auth::id()
                ],
            ];

            $ruleEmail = [
                'email' => ['required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique('friends_lists')->where('user_id', Auth::id())
                        ->whereNull('deleted_at')
                    //|unique:friends_lists,email,NULL,id,user_id,' . Auth::id()
                ],
            ];


            $errors = Validator::make([
                'email' => $field,
                'phone' => $field
            ], ($request->type === 'email' ? $ruleEmail : $rulePhone))->errors()->all();

            if ($errors) {
                $hasErrors[] = $key;
                continue;
            }
            FriendsList::create(
                array_merge([
                    'name' => $name,
                    'user_id' => Auth::id(),
                ],
                    $request->type === 'email' ? ['email' => $field] : ['phone' => $field]));
        }
        DB::commit();
        $errors = implode(',', $hasErrors);
        $message = $hasErrors ? "Импорт завершен. Строки {$errors} небыли загружены. Некорректный формат либо запись уже существует. Проверьте ваш файл."
            : "Импорт успешно завершен.";

        return response()->json(['message' => $message]);
    }

    function exportFriends(Request $request)
    {
        $collection = FriendsList::whereUserId(Auth::id());
        if ($request->type === 'email') {
            $collection->whereNotNull('email');
        } else {
            $collection->whereNotNull('phone');
        }
        $collection = $collection->get();
        if ($collection->isEmpty()) {
            return redirect()->back();
        }
        $name = $request->type === 'email' ? 'Friends_email.xlsx' : 'Friends_phone.xlsx';
        (new FastExcel($collection))->download($name, function ($friend) use ($request) {

            return $request->type === 'email' ? [
                'Имя' => $friend->name,
                'Email' => $friend->email,
            ] : [
                'Имя' => $friend->name,
                'Телефон' => $friend->phone,
            ];
        });
    }

    function onDeleteFriends(Request $request)
    {
        if (!$request->filled('ids')) {
            return response()->json(['data' => 'Ничего не выбрано']);
        }
        $errors = Validator::make($request->all(), [
            'type' => 'required|in:email,phone'
        ])->errors()
            ->getMessages();


        if ($errors) return response()->json($errors, 419);

        $friends = FriendsList::whereUserId(Auth::id())->whereIn('id', $request->ids);
        if ($request->type === 'phone') {
            $friends->whereNotNull('phone');
        } else {
            $friends->whereNotNull('email');
        }
        $friends = $friends->get();
        $tbc = 0;
        foreach ($friends as $friend) {

            if ($request->type = 'phone') {
                $tbc += $friend->sms_links()->whereConfirmStatus(1)->count();
            }
            if ($request->type = 'email') {
                $tbc += $friend->email_links()->whereConfirmStatus(1)->count();
            }

        }
        $count = $friends->count();
        return response()->json(
            [
                'data' => "Удалить: {$count} записей? <br> Число tbc будет уменьшено на: {$tbc}",
                'ids' => $friends->pluck('id'),
                'type' => $request->type

            ]);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        $friend = FriendsList::whereUserId(Auth::id())->findOrFail($id);
        $friend->update([
            'name' => $request->name
        ]);

        return response()->json(['message' => 'Успешно сохранено.']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        FriendsList::whereUserId(Auth::id())->findOrFail($id)->delete();
    }

    function confirmDelete(Request $request)
    {
        if (!$request->filled('ids')) {
            return response()->json(['data' => 'Ничего не выбрано']);
        }
        $errors = Validator::make($request->all(), [
            'type' => 'required|in:email,phone'
        ])->errors()
            ->getMessages();


        if ($errors) return response()->json($errors, 419);

        $friends = FriendsList::whereUserId(Auth::id())->whereIn('id', $request->ids);
        if ($request->type === 'phone') {
            $friends->whereNotNull('phone');
        } else {
            $friends->whereNotNull('email');
        }
        $friends = $friends->get();
        DB::beginTransaction();
        foreach ($friends as $friend) {

            if ($request->type = 'phone') {
                foreach ($friend->sms_links()->whereConfirmStatus(1)->get() as $phone) {
                    $friend->user->decrementTbcBalance(100, $phone);
                }
            }
            if ($request->type = 'email') {
                foreach ($friend->email_links()->whereConfirmStatus(1)->get() as $email) {
                    $friend->user->decrementTbcBalance(100, $email);
                }
            }
            $friend->delete();
        }
        DB::commit();

        return response()->json(['message' => 'Успешно удалено', 'tbc' => Auth::user()->getBalance('tbc') / 100]);
    }


    function getTbcHistory(Request $request)
    {
        $period_start = 0;
        $period_end = 0;
        $history = User\TbcBalanceHistory::whereUserId(Auth::id())->orderBy('created_at', 'DESC')->get();
        $first = $history->first();
        $last = $history->last();
        if ($first) {
            $period_start = $first->old_sum_format;
        }

        if ($last) {
            $period_end = $last->new_sum_format;
        }

        return response()->json([
            'data' => $history,
            'period_start' => $period_start,
            'period_end' => $period_end,
        ]);
    }


}

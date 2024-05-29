<?php

namespace App\Http\Controllers\Marketing;

use App\Marketing\ContactForm;
use App\Marketing\Mailing\EmailList;
use App\Marketing\Mailing\PhoneList;
use App\Marketing\SendingMails;
use App\Marketing\SubmitContactForm;
use App\User;
use App\User\SendingSubscribe;
use App\User\Subscribe;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SubscribeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->filled('get_subscribes')) {
            $subscribes = Subscribe::forUser()->orderBy('id')->get();
            return response()->json(['data' => $subscribes]);
        }

        return view('user.subscribe', compact('subscribes'));
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
        $subscribe = Subscribe::forUser()->where('can_unsubscribe', 1)->findOrFail($id);
        $is_subscribe = $subscribe->unsubscribes->contains(Auth::user()) === false;

        if ($is_subscribe) {
            $subscribe->unsubscribes()->syncWithoutDetaching([Auth::id()]);
        } else {
            $subscribe->unsubscribes()->detach([Auth::id()]);
        }

        return response()->json(['message' => ($is_subscribe ? 'Вы отписались от рассылки' : 'Вы подписались на рассылку')]);
    }

    function getPixel($id)
    {

        $share = SendingSubscribe::findOrFail($id);

        if (!$share->is_watch) {
            $share->update([
                'is_watch' => 1,
                'watch_at' => Carbon::now(),
            ]);
        }
        $headers = [
            'Content-Type' => 'image/png'
        ];

        return response()->stream(function () {
            echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=');
        }, 200, $headers);
    }


    function getMailingPixel($id)
    {

        $share = SendingMails::findOrFail($id);

        if (!$share->is_watch) {
            $share->update([
                'is_watch' => 1,
                'watch_at' => Carbon::now(),
            ]);
        }
        $headers = [
            'Content-Type' => 'image/png'
        ];

        return response()->stream(function () {
            echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=');
        }, 200, $headers);
    }

    function contactForm(Request $request, $id)
    {
        $form = ContactForm::findOrFail($id);
        if ($request->filled('_phone')) {
            $request->merge(['_phone' => User::trimPhone($request->_phone)]);
        }
        $rules = [];
        if ($form->collect_phone) {
            $rulePhone = [
                'required',
                'int',
                'digits:11',
                //Rule::unique('phone_lists')->whereNull('deleted_at')
                // 'unique:friends_lists,phone,NULL,id,user_id,' . Auth::id()
            ];
            $rules = array_merge($rules, ['_phone' => $rulePhone]);
        }
        if ($form->collect_email) {
            $ruleEmail = ['required',
                'string',
                'email',
                'max:255',
                //  Rule::unique('email_lists')->whereNull('deleted_at')
            ];
            $rules = array_merge($rules, ['_email' => $ruleEmail]);
        }
        $errors = Validator::make($request->all(), $rules, [
            '_email.string' => 'Некорректный Email',
            '_email.required' => 'Некорректный Email',
            '_email.email' => 'Некорректный Email',
            '_email.max' => 'Некорректный Email',
            '_phone.required' => 'Некорректный Телефон',
            '_phone.int' => 'Некорректный Телефон',
            '_phone.digits' => 'Некорректный Телефон',
        ])
            ->errors()
            ->getMessages();

        if ($errors) return response()->json($errors, 419);

        DB::beginTransaction();

        $fields = [
            'name' => $request->name,
            'comment' => $form->collect_comment ? $request->comment : '',
            'contact_form_id' => $form->id,
        ];
        $submit = SubmitContactForm::create($fields);

        if ($request->filled('_email') && $form->collect_email) {

            $curr_item = EmailList::firstOrCreate([
                'email' => $request->_email
            ]);


            $form->email_book->emails()->syncWithoutDetaching([$curr_item->id]);

            $this->dispatch(new \App\Jobs\Marketing\ContactForm($form, $curr_item, $submit));
        }

        if ($request->filled('_phone') && $form->collect_phone) {
            $curr_item = PhoneList::firstOrCreate([
                'phone' => $request->_phone
            ]);



            $form->phone_book->phones()->syncWithoutDetaching([$curr_item->id]);

            $this->dispatch(new \App\Jobs\Marketing\ContactFormPhone($form, $curr_item, $submit));
        }

        DB::commit();

        return response()->json(['message' => 'Спасибо! Ваши данные отправлены!']);

    }
}

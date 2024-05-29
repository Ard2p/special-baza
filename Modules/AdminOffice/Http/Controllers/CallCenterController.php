<?php

namespace Modules\AdminOffice\Http\Controllers;

use App\Article;
use App\Helpers\RequestHelper;
use App\Machines\Brand;
use App\Machines\Type;
use App\Service\Sms;
use App\Support\ArticleLocale;
use App\Support\Region;
use App\Support\SmsNotification;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\AdminOffice\Entities\Filter;
use Modules\Orders\Entities\Order;
use Modules\Telephony\Entities\Call;

class CallCenterController extends Controller
{

    public function __construct(Request $request)
    {

        $this->middleware('accessCheck:show,call_center')->only('getInitial', 'getCalls');
        $this->middleware('accessCheck:create,call_center')->only('send_sms');

        $data = $request->all();
        $data = array_map(function ($val) {
            return $val === 'null' || $val === 'undefined' ? '' : $val;
        }, $data);
        $request->merge($data);
    }

    function getInitial(Request $request)
    {
        $regions = Region::with('cities')->whereCountry('russia')->get();

        return \response()->json([
            'brands' => Brand::all(),
            'regions' => $regions,
            'categories' => Type::all(),
            'proposals' => [],
        ]);
    }

    function getCalls(Request $request)
    {
        $calls = Call::with('user');

        if ($request->filled('phone')) {
            $calls->where('phone', trimPhone($request->input('phone')));
        }

        return $calls->orderBy('created_at', 'desc')->paginate($request->per_page ?: 10);
    }

    function getCallStream($id)
    {
        $call = Call::findOrFail($id);

        return response()->download(storage_path('calls/' . $call->record_name));
    }

    function sendSms(Request $request)
    {
        $request->merge([
            'phone' => trimPhone($request->input('phone'))
        ]);
        $errors = Validator::make($request->all(), [
            'text' => 'required|string|max:900',
            'phone' => 'required|numeric|digits:' . RequestHelper::requestDomain()->options['phone_digits'],
        ])->errors()->getMessages();

        if ($errors) {
            return \response()->json($errors, 400);
        }

        $phone = $request->input('phone');
        $text = $request->input('text');

        $user = User::wherePhone($request->input('phone'))->first();

        $sms = SmsNotification::create([
            'message' => $text,
            'user_id' => ($user ? $user->id : 0),
            'phone' => $phone,
        ]);

        $result = (new Sms())->send_sms($sms->phone, $sms->message, 0, 0, $sms->id, 0, false);

        $sms->update([
            'status' => json_encode($result)
        ]);

        return response()->json();
    }


    function smsList(Request $request)
    {
        $sms = SmsNotification::query()->with('user');
        $filter = new Filter($sms);
        $filter->getLike([
            'message' => 'message',
            'phone' => 'phone',
        ]);

        return $sms->orderBy('id', 'desc')->paginate($request->input('per_page', 20));
    }


}

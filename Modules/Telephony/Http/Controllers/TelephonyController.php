<?php

namespace Modules\Telephony\Http\Controllers;

use App\Service\EventNotifications;
use App\Service\OrderService;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\Telephony\Entities\Call;
use Modules\Telephony\Entities\PhoneProposal;
use Modules\Telephony\Entities\YandexTelephony;

class TelephonyController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function pushCall(Request $request)
    {
        $echo = $request->header('Echo');

        $data = $request->all();

        try {
            $call = new YandexTelephony($data);

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }


        return response('', 200, ['Echo' => $echo]);
    }

    function getCalls()
    {
        $calls = Call::orderBy('created_at', 'desc')->get();

        return response()->json($calls->toArray());
    }


    public function index()
    {

        $calls = Call::all();

        return view('telephony::index', compact('calls'));
    }

    function newProposal(Request $request)
    {
        $request->merge(
            [
                'phone_number' => str_replace('+8', '+7', trimPhone($request->phone_number))
            ]
        );
        $service = new OrderService($request);

        $errors = $service->validateErrors()->getErrors();

        $errors2 = Validator::make($request->all(), ['phone_number' => 'required|digits:11'])->errors()->getMessages();

        $errors = array_merge($errors, $errors2);

        if ($request->sum <= 0) {
            $errors['sum'] = [['Бюджет не может быть меньше нуля']];
        }

        if ($errors) {
            $errors['modals'][] = 'Ошибка при создании заявки. Проверьте правильность заволнения полей.';
        }

        if ($errors) return response()->json($errors, 419);
        DB::beginTransaction();


        $user = User::wherePhone($request->phone_number)->first();

        if (!$user) {
            $create_new = true;
            $user = User::register('', $request->phone_number);
        }

        $service->search()->setProposalSum($request->sum)->forUser($user->id)->createProposal('open');

        if (isset($create_new)) {
            $password = str_random(6);
            $user->update([
                'phone_confirm' => 1,
                'password' => Hash::make($password),
            ]);

            $user->sendSmsNotification("Логин для входа в систему TRANSBAZA: {$user->phone}, пароль: {$password}");
        }

        PhoneProposal::create([
            'phone' => $request->phone_number,
            'proposal_id' => $service->created_proposal->id,
            'user_id' => $user->id,
        ]);
        DB::commit();



          (new EventNotifications())->newProposal($service->created_proposal);

        return response()->json([
            'status' => 'success',
            'id' => $service->created_proposal->id
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        return view('telephony::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return view('telephony::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        return view('telephony::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}

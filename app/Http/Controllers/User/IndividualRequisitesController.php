<?php

namespace App\Http\Controllers\User;

use App\User\IndividualRequisite;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class IndividualRequisitesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('requisites.individual_create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $errors = Validator::make($request->all(), IndividualRequisite::$requiredFields)
            ->setAttributeNames(IndividualRequisite::$attributesName)
            ->errors()
            ->getMessages();

        if ($errors) return response()->json($errors, 419);
        if(!$request->has('scans')){
            $request->merge(['scans' => '[]']);
        }else{
            $request->merge(['scans' => json_encode($request->input('scans'))]);
        }
        if (!$request->has('individual_id')) {
            DB::transaction(function () use ($request) {

                $date = Carbon::createFromFormat('Y/m/d', $request->input('birth_date'))->format('Y-m-d');
                $gender = Auth::user()->gender_type($request->input('gender'));
                $request->merge(['birth_date' => $date, 'gender' => $gender, 'user_id' => Auth::user()->id]);

                $data = $request->except(['active']);

                $individual = IndividualRequisite::create($data);

                if (!Auth::user()->getActiveRequisite()) {
                    Auth::user()->account_type = 'individual';
                    Auth::user()->customer_requisite_type = 'individual';
                    Auth::user()->save();

                    return $individual->update([
                        'active' => 1
                    ]);
                }

                if (Auth::user()->getCurrentBalance() == 0) {

                    Auth::user()->account_type = 'individual';
                    Auth::user()->customer_requisite_type = 'individual';
                    Auth::user()->save();

                    Auth::user()->individualRequisites()
                        ->where('active', 1)
                        ->update(['active' => 0]);

                    $individual->update(['active' => 1]);

                }
            });

        } else {
            $individual = IndividualRequisite::find($request->input('individual_id'));
            if ($individual && $individual->user_id === Auth::id()) {
                $date = Carbon::createFromFormat('Y/m/d', $request->input('birth_date'))->format('Y-m-d');

                $gender = Auth::user()->gender_type($request->input('gender'));
                $request->merge(['birth_date' => $date, 'gender' => $gender]);

                $individual->update($request->all());

                return response()->json(['message' => 'Реквизиты обновлены.']);
            }
            return response()->json(['message' => 'Реквизиты не найдены.'], 419);
        }
        return response()->json(['message' => 'Реквизиты добавлены.']);
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (Auth::user()->isCustomer()) {
            $req = IndividualRequisite::currentUser()->findOrFail($id);
            $req->active = 0;
            $req->save();
            $req->delete();

            Auth::user()->update(['customer_requisite_type' => null]);

            return response()->json(['message' => 'Реквизиты удалены.']);
        }

        return response()->json(['Невозможно удалить реквизиты в данный момент.'], 419);
    }
}

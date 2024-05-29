<?php

namespace Modules\CorpCustomer\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\CorpCustomer\Emails\EmployeeInvite;
use Modules\CorpCustomer\Entities\CorpBank;
use Modules\CorpCustomer\Entities\CorpBrand;
use Modules\CorpCustomer\Entities\CorpCompany;
use Modules\CorpCustomer\Entities\EmployeeRequest;

class CompanyController extends Controller
{

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $request->merge([
           'phone' => trimPhone($request->phone)
        ]);
        CorpBrand::currentUser()->findOrFail($request->corp_brand_id);
        $errors = \Validator::make($request->all(), CorpCompany::getRules())->errors()
            ->getMessages();

        if ($errors) return response()->json($errors, 419);

        \DB::beginTransaction();
        $company = CorpCompany::create([
            'full_name' => $request->full_name,
            'short_name' =>  $request->short_name,
            'address' =>  $request->address,
            'zip_code' =>  $request->zip_code,
            'email' =>  $request->email,
            'phone' =>  $request->phone,
            'inn' =>  $request->inn,
            'kpp' =>  $request->kpp,
            'ogrn' => $request->ogrn,
            'corp_brand_id' => $request->corp_brand_id,
        ]);

        $banks = CorpBank::currentUser()->whereIn('id', $request->banks ?: [])->pluck('id');

        $company->banks()->sync($banks);
        \DB::commit();

        return \response()->json(['message' => 'Компания добавлена', 'url' => route('corp-companies.show', $company->id)]);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        $company = CorpCompany::currentOrEmployee()->findOrFail($id);
        return $company;
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $request->merge([
            'phone' => trimPhone($request->phone)
        ]);
        $company = CorpCompany::currentUser()->findOrFail($id);
        $errors = \Validator::make($request->all(), CorpCompany::getRules())->errors()
            ->getMessages();

        if ($errors) return response()->json($errors, 419);

        \DB::beginTransaction();
        $company->update([
            'full_name' => $request->full_name,
            'short_name' =>  $request->short_name,
            'address' =>  $request->address,
            'zip_code' =>  $request->zip_code,
            'email' =>  $request->email,
            'phone' =>  $request->phone,
            'inn' =>  $request->inn,
            'kpp' =>  $request->kpp,
            'ogrn' => $request->ogrn,
        ]);

        $banks = CorpBank::currentUser()->whereIn('id', $request->banks ?: [])->pluck('id');

        $company->banks()->sync($banks);

        \DB::commit();

        return response()->json($company);
    }

    function addEmployee(Request $request)
    {
        $errors = \Validator::make($request->all(), [
            'email' => 'required|exists:users,email',
            'position' => 'required|string|min:3|max:255',
            'corp_company_id' => 'required|exists:corp_companies,id',

        ])->errors()
            ->getMessages();

        if ($errors) return response()->json($errors, 419);

        CorpCompany::currentUser()->findOrFail($request->corp_company_id);

        $user = User::where('email',  $request->email)->where('id', '!=', auth()->id())->first();

        if(!$user){
            $errors['email'] = ['Нельзя добавить самого себя.'];
            return response()->json($errors, 419);
        }

        if(
            EmployeeRequest::where([
            'corp_company_id' => $request->corp_company_id,
            'user_id' => $user->id,])->first()
        ){

            $errors['email'] = ['Вы уже отправили запрос.'];
            return response()->json($errors, 419);
        }
        DB::beginTransaction();
       $connect = EmployeeRequest::create([
            'corp_company_id' => $request->corp_company_id,
            'user_id' => $user->id,
            'link' => str_random(8),
            'position' => $request->position,
        ]);

        \Mail::to($user->email)->queue(new EmployeeInvite($connect));
        DB::commit();

        return \response()->json(['message' => 'Запрос сотруднику отправлен.']);
    }

    function acceptEmployee($link)
    {
        $employe = EmployeeRequest::whereLink($link)
            ->whereUserId(auth()->id())
            ->firstOrFail();

        $employe->company->employees()->syncWithoutDetaching([$employe->user_id => ['position' => $employe->position]]);


        return redirect()->route('corp-companies.show', $employe->corp_company_id);
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

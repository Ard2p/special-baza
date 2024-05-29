<?php

namespace Modules\AdminOffice\Http\Controllers\Users;

use App\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\CorpCustomer\Entities\CorpBank;
use Modules\CorpCustomer\Entities\CorpBrand;

class BrandsController extends Controller
{
    public function __construct(Request $request)
    {
       if($request->filled('phone')) {
           $request->merge([
               'phone' => trimPhone($request->phone)
           ]);
       }

    }

    /**
     * Display a listing of the resource.
     * @param $user_id
     * @return Response
     */
    public function index(Request $request, $user_id)
    {

        return  CorpBrand::whereUserId($user_id)->paginate($request->input('per_page', 10));
    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function store(Request $request, $user_id)
    {

        User::findOrFail($user_id);

        $errors = \Validator::make($request->all(), CorpBrand::getRules())->errors()
            ->getMessages();

        if ($errors) return response()->json($errors, 419);

        \DB::beginTransaction();

        $brand = CorpBrand::create([
            'full_name' => $request->full_name,
            'short_name' => $request->short_name,
            'address' => $request->address,
            'zip_code' => $request->zip_code,
            'email' => $request->email,
            'phone' => $request->phone,
            'inn' => $request->inn,
            'kpp' => $request->kpp,
            'ogrn' => $request->ogrn,
            'user_id' => $user_id
        ]);
        $banks = CorpBank::currentUser($user_id)->whereIn('id', $request->banks ?: [])->pluck('id');

        $brand->banks()->sync($banks);

        \DB::commit();

        return response()->json($brand);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($user_id, $id)
    {
        $brand = CorpBrand::with('companies')->whereUserId($user_id)->findOrFail($id);

        return $brand;
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     * @throws Exception
     */
    public function update(Request $request, $user_id, $id)
    {
        $brand = CorpBrand::whereUserId($user_id)->findOrFail($id);


        $errors = \Validator::make($request->all(),  CorpBrand::getRules($id))->errors()
            ->getMessages();

        if ($errors) return response()->json($errors, 419);

        \DB::beginTransaction();

        $brand->update([
            'full_name' => $request->full_name,
            'short_name' => $request->short_name,
            'address' => $request->address,
            'zip_code' => $request->zip_code,
            'email' => $request->email,
            'phone' => $request->phone,
            'inn' => $request->inn,
            'kpp' => $request->kpp,
            'ogrn' => $request->ogrn,
        ]);

        $banks = CorpBank::currentUser($user_id)->whereIn('id', $request->banks ?: [])->pluck('id');

        $brand->banks()->sync($banks);

        \DB::commit();

        return response()->json(['message' => 'Бренд обновлен']);
    }

    /**
     * Remove the specified resource from storage.
     * @param $user_id
     * @param int $id
     * @return Response
     */
    public function destroy($user_id, $id)
    {
        $brand = CorpBrand::whereUserId($user_id)->findOrFail($id);

        $brand->delete();

        return  response()->json();
    }
}

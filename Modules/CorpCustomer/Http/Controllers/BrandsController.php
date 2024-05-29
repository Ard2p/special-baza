<?php

namespace Modules\CorpCustomer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\CorpCustomer\Entities\CorpBank;
use Modules\CorpCustomer\Entities\CorpBrand;
use Modules\CorpCustomer\Http\Requests\LegalRequisiteRequest;

class BrandsController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        return  CorpBrand::currentUser()->get();
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(LegalRequisiteRequest $request)
    {

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
            'user_id' => auth()->id()
        ]);
/*        $banks = CorpBank::currentUser()->whereIn('id', $request->banks ?: [])->pluck('id');

        $brand->banks()->sync($banks);*/

        \DB::commit();

        return response()->json($brand);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        $brand = CorpBrand::with('companies')->currentUser()->findOrFail($id);

        return view('corpcustomer::corp-brands.show', compact('brand'));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(LegalRequisiteRequest $request, $id)
    {
        $brand = CorpBrand::currentUser()->findOrFail($id);

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

  /*      $banks = CorpBank::currentUser()->whereIn('id', $request->banks ?: [])->pluck('id');

        $brand->banks()->sync($banks);*/

        \DB::commit();

        return \response()->json(['message' => 'Бренд обновлен']);
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

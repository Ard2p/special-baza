<?php

namespace Modules\CorpCustomer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\CorpCustomer\Entities\CorpBank;
use Modules\CorpCustomer\Entities\CorpBrand;

class BanksController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        return view('corpcustomer::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        return view('corpcustomer::banks.create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $errors = \Validator::make($request->all(), [
            'name' => 'required|string|min:2|max:255',
            'address' => 'required|string|min:5|max:1000',
            'account' => 'required|integer',
            'bik' => 'required|integer',
        ])->errors()
            ->getMessages();

        if ($errors) return response()->json($errors, 419);

        CorpBank::create([
            'name' => $request->name,
            'account' =>  $request->account,
            'bik' =>  $request->bik,
            'address' =>  $request->address,
            'user_id' => auth()->id()
        ]);

        return \response()->json(['message' => 'Банк добавлен', 'url' => route('corp_index')]);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return view('corpcustomer::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        $bank = CorpBank::currentUser()->findOrFail($id);
        return view('corpcustomer::banks.edit', compact('bank'));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $bank = CorpBank::currentUser()->findOrFail($id);

        $errors = \Validator::make($request->all(), [
            'name' => 'required|string|min:2|max:255',
            'address' => 'required|string|min:5|max:1000',
            'account' => 'required|integer',
            'bik' => 'required|integer',
        ])->errors()
            ->getMessages();

        if ($errors) return response()->json($errors, 419);

        $bank->update([
            'name' => $request->name,
            'account' =>  $request->account,
            'bik' =>  $request->bik,
            'address' =>  $request->address,
        ]);

        return \response()->json(['message' => 'Банк обновлен']);
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

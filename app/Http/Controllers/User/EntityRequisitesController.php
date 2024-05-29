<?php

namespace App\Http\Controllers\User;

use App\User\EntityRequisite;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EntityRequisitesController extends Controller
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
        return view('requisites.entity_create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $errors = Validator::make($request->all(), EntityRequisite::$requiredFields, [
            'inn.digits_between' => 'Допустимый ИНН 10 или 12 символов.',
            'ogrn.digits_between' => 'Допустимый ОГРН 13 или 15 символов.',
        ])
            ->setAttributeNames(EntityRequisite::$attributesName)
            ->errors()
            ->getMessages();
         if($request->filled('inn')){
             if(strlen($request->input('inn')) == 11){
                 $errors['inn'] = [['Допустимый ИНН 10 или 12 символов.']];
             }
         }
        if($request->filled('ogrn')){
            if(strlen($request->input('ogrn')) == 14){
                $errors['ogrn'] = [['Допустимый ОГРН 13 или 15 символов.']];
            }
        }
        if ($errors) return response()->json($errors, 419);
        $request->merge(['user_id' => Auth::user()->id]);

        if (!$request->has('entity_id')) {
            DB::transaction(function () use ($request) {

                if (!Auth::user()->getActiveRequisite()) {
                    $entity = EntityRequisite::create($request->except(['active']));

                    if (Auth::user()->isCustomer()) {
                        Auth::user()->customer_requisite_type = 'entity';
                    } else {
                        $entity->is_contractor = 1;
                    }
                    $entity->update(['active' => 1]);
                    Auth::user()->save();
                    return response()->json(['message' => 'Реквизиты добавлены.']);
                }

                if (Auth::user()->getCurrentBalance() == 0) {
                    $entity = EntityRequisite::create($request->except(['active']));

                    $entities = Auth::user()->entityRequisites();
                    if (Auth::user()->isCustomer()) {
                        Auth::user()->customer_requisite_type = 'entity';
                        $entities->forCustomer();
                    } else {
                        $entity->is_contractor = 1;
                        $entities->forContractor();
                    }

                    $entities->where('active', 1)
                        ->update(['active' => 0]);

                    $entity->update(['active' => 1]);

                }
                Auth::user()->save();
            });
            return response()->json(['message' => 'Реквизиты добавлены.']);
        } else {
            $entity = EntityRequisite::find($request->input('entity_id'));
            if ($entity && $entity->user_id === Auth::id()) {
                $entity->update($request->all());
                return response()->json(['message' => 'Реквизиты обновлены.']);
            }
            return response()->json(['message' => 'Реквизиты не найдены.'], 419);
        }




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
     * @param  int $id
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

        $req = EntityRequisite::currentUser();

        $req = (Auth::user()->isContractor())
            ? $req->forContractor()
            : $req->forCustomer();

        $req = $req->findOrFail($id);
        $req->active = 0;
        $req->save();
        $req->delete();

        if(Auth::user()->isCustomer()){
            Auth::user()->update(['customer_requisite_type' => null]);
        }

        return response()->json(['message' => 'Реквизиты удалены.']);


        //  return response()->json(['Невозможно удалить реквизиты в данный момент.'], 419);
    }
}

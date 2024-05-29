<?php

namespace App\Http\Controllers\User;

use App\Support\Document;
use App\Support\DocumentType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DocumentsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $documents = Document::currentUser()
            ->currentAccount()
            ->withFilters($request)
            ->with('_type')
            ->get();

        return (!$request->ajax())
            ? view('user.customer.documents', ['documents' => $documents])
            : response()->json(['data' => $documents]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('documents.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $errors = Validator::make($request->all(), Document::$requiredFields)
            ->errors()
            ->all();

        if ($errors) return response()->json($errors, 419);

        $uid = now()->format('d.m.Y H:i');
        $fileName = "document_{$uid}";

        Storage::disk()->putFile("documents/{$fileName}", $request->file('document'));

        DocumentType::findOrFail($request->input('type'));

        Document::create([
            'user_id' => Auth::user()->id,
            'type' => $request->input('type'),
            'number' => $request->input('number'),
            'date' => Carbon::parse($request->input('date')),
            'body' => $request->input('body'),
            'url' => $fileName,
        ]);

        return response()->json(['message' => 'Документ добавлен']);
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
       Document::currentUser()->findOrFail($id)->delete();
    }
}

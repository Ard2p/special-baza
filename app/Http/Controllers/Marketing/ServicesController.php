<?php

namespace App\Http\Controllers\Marketing;

use App\Content\StaticContent;
use App\Marketing\Service;
use App\Service\SimpleFormService;
use App\Service\SubmitFormOnService;
use App\Service\Subscription;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ServicesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
      $services = Service::whereIsPublish(1)->orderBy('order')->get();
      $article = StaticContent::whereAlias($request->path())->first();


      return view('services', compact('services', 'article'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sendForm(Request $request, $alias = null)
    {
        $service = new SubmitFormOnService($request);
        $errors = $service->acceptSimpleForm()->getErrors();
        if ($errors) return response()->json($errors, 419);

        if($service->created_user){
           (new Subscription())->newUserFromForm($service->created_user, $service->created_user_password);
        }

       (new Subscription())->newSubmitSimpleForm($service->getSubmitForm(), $service->created_user ? true : false);
        return response()->json(['message' => 'Заявка отправлена!']);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($alias)
    {
        $service = Service::whereAlias($alias)->whereIsPublish(1)->firstOrFail();

        return view('marketing.services.index', compact('service'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}

<?php

namespace App\Http\Controllers\Support;

use App\Support\KnowledgeBase;
use App\System\SystemFunction;
use App\System\SystemModule;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class KnowledgeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $systemModules = SystemModule::whereIsPublish(1)->with(['module_functions' => function($q){
            $q->whereIsPublish(1);
        }])->get();

        return view('knowledge.index', compact('systemModules'));
    }


    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($module, $function, $id)
    {
        $systemModule = SystemModule::whereAlias($module)->firstOrFail();
        $systemFunction = SystemFunction::findOrFail($function);
        $base = KnowledgeBase::whereSystemModuleId($systemModule->id)->whereSystemFunctionId($function)->findOrFail($id);

        return view('knowledge.show', compact('base'));
    }

}

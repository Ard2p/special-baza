<?php

namespace Modules\AdminOffice\Http\Controllers\Support;

use App\Support\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DocumentsController extends Controller
{
   function getDocuments(Request $request, $id = null)
   {
       $documents = Document::query();


       if($id) {

           return $documents->findOrFail($id);
       }

       return $documents->paginate($request->input('per_page', 10));
   }

   function create(Request $request)
   {
       $errors  = \Validator::make($request->all(), [
           'number' => 'required|string|unique:documents,number',
           'date' => 'required|string',
           'body' => 'required|string',
           'url' => 'required|string',
           'billing_type' => 'required|in:all,customer,contractor',
       ])->errors()->getMessages();

       if($errors){
           return response()->json($errors, 400);
       }

       DB::beginTransaction();
       $document = Document::create([
          'user_id' => Auth::id(),
          'type' => '1',
          'number' => $request->input('number'),
          'date' => $request->input('date'),
          'body' => $request->input('body'),
          'url' => $request->input('url'),
          'author' => Auth::user()->email,
       ]);

       $ext = getFileExtensionFromString($document->url);
       $new_name = 'documents/' . generateChpu($document->number) . ".{$ext}";

       if( Storage::disk('public_disk')->exists($document->url))
          Storage::disk('public_disk')->move($document->url, $new_name);
       $document->update([
           'url' => $new_name
       ]);
       DB::commit();

       return response()->json($document);
   }

   function update(Request $request, $id)
   {
       $errors  = \Validator::make($request->all(), [
           'number' => 'required|string|unique:documents,number,' . $id,
           'date' => 'required|string',
           'body' => 'required|string',
           'url' => 'required|string',
           'billing_type' => 'required|in:all,customer,contractor',
       ])->errors()->getMessages();

       $document = Document::findOrFail($id);
       if($errors){
           return response()->json($errors, 400);
       }

       DB::beginTransaction();
       $old = $document->url;

       $document->update([
           'user_id' => Auth::id(),
           'type' => '1',
           'number' => $request->input('number'),
           'date' => $request->input('date'),
           'body' => $request->input('body'),
           'url' => $request->input('url'),
           'author' => Auth::user()->email,
       ]);
       if($old !== $request->input('url')){


           $ext = getFileExtensionFromString($document->url);

           $new_name = 'documents/' . generateChpu($document->number) . ".{$ext}";

           if(Storage::disk('public_disk')->exists($old)){
               Storage::disk('public_disk')->delete($old);
           }
           Storage::disk('public_disk')->move($document->url, $new_name);
           $document->update([
               'url' => $new_name
           ]);
       }

       DB::commit();


       return response()->json($document);
   }

   function upload(Request $request)
   {
       $errors = Validator::make($request->all(), [
           'files' => 'required|array',
           'files.*' => 'required|file|max:20480',
       ])->errors()->getMessages();
       if($errors){
           return response()->json($errors, 400);
       }
       $scans = [];
       $files = $request->file('files');
       foreach ($files as $file) {

           $extension = $file->getClientOriginalExtension();
           $fileName = str_random(5) . "-" . date('his') . "-" . str_random(3) . "." . $extension;
           $folderpath = public_path('documents');
           $file->move($folderpath, $fileName);
           $url = 'documents/' . $fileName;
           $scans[] = [
               'url' => url($url),
               'value' => $url,
           ];
           if ($request->filled('single')) {
               $scans = $scans[0];
           }
       }
       return response()->json($scans, 200);
   }
}

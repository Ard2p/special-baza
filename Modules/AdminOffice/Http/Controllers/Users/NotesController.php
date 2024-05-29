<?php

namespace Modules\AdminOffice\Http\Controllers\Users;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\AdminOffice\Entities\User\Note;

class NotesController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request, $user_id)
    {

        $notes = Note::query()->where('user_id', $user_id)->orderBy('created_at', 'desc')->get();

        return $notes;
    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request, $user_id)
    {
        $request->validate([
            'text' => 'required|string|max:500',
            'type' => 'required|in:' . implode(',',array_keys(Note::getTypes())),
        ]);

        $user = User::query()->findOrFail($user_id);

        $note = Note::create([
            'text' => $request->input('text'),
            'type' => $request->input('type'),
            'user_id' => $user->id,
            'manager_id' => \Auth::id(),
        ]);

        return  response()->json($note);
    }


    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($user_id, $id)
    {
        $user = User::query()->findOrFail($user_id);

        $user->notes()->where('id', $id)->delete();
        return  response()->json();
    }
}

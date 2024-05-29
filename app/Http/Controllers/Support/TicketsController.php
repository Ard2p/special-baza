<?php

namespace App\Http\Controllers\Support;

use App\Support\SubmitTicketPopup;
use App\Support\SupportCategory;
use App\Support\Ticket;
use App\Support\TicketMessage;
use App\Support\TicketPopup;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;

class TicketsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $tickets = Ticket::currentUser()->with('category')->get();
        return (!$request->ajax())
            ? view('support.index', ['tickets' => $tickets])
            : response()->json(['data' => $tickets->toArray()]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('support.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $errors = Validator::make($request->all(), Ticket::$requiredFields, ['category.required' => 'Выберите категорию'])
            ->errors()
            ->all();

        if ($errors) return response()->json($errors, 419);

        DB::transaction(function () use ($request, &$ticket) {
            $ticket = Ticket::create([
                'title' => $request->input('title'),
                'user_id' => Auth::user()->id,
                'status' => Ticket::status('open'),
                'category_id' => SupportCategory::findOrFail($request->input('category'))->id,
            ]);

            $message = TicketMessage::create([
                'message' => $request->input('message'),
                'user_id' => Auth::user()->id,
                'ticket_id' => $ticket->id,
            ]);
            $files = $request->input('files');
            if ($files && count($files) > 0 && $files[0]) {
//                $message->makeFiles($request->file('files'));
//                $message->save();
                $message->files = json_encode($request->input('files'));
                $message->save();
            }
        });

        return response()->json([
            'message' => 'Обращение создано.',
            'id' => $ticket->id,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $ticket = Ticket::currentUser()->with('messages')->findOrFail($id);

        TicketMessage::whereIn('id', $ticket->messages()
            ->supportMessages()
            ->pluck('id')
            ->toArray()
        )->update(['is_read' => 1]);

        return view('support.show', ['ticket' => $ticket]);
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
        $errors = Validator::make($request->all(), TicketMessage::$requiredFields)
            ->errors()
            ->all();

        if ($errors) return response()->json($errors, 419);

        $ticket = Ticket::currentUser()->isOpen()->findOrFail($id);
        DB::transaction(function () use ($request, $id, &$message) {
            $message = TicketMessage::create([
                'message' => $request->input('message'),
                'user_id' => Auth::user()->id,
                'ticket_id' => $id
            ]);

            if ($request->hasFile('files')) {
                $message->makeFiles($request->file('files'))
                    ->save();
            }
        });
        $messages = $ticket->messages()
            ->where(function ($q) {
                $q->where('is_read', 0)->supportMessages();
            })
            ->orWhere('id', $message->id)
            ->orderBy('created_at', 'ASC')
            ->get();
        $ids = $messages->where('id', '!=', $message->id)->pluck('id')->toArray();

        TicketMessage::whereIn('id', $ids)->update(['is_read' => 1]);

        return response()->json([
            'messages' => view('support.message_block')
                ->with('messages', $messages)
                ->render(),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function loadImages(Request $request)
    {
        $scans = [];
        $files = $request->file('files');
        foreach ($files as $file) {

            $extension = $file->getClientOriginalExtension();
            $fileName = str_random(5) . "-" . date('his') . "-" . str_random(3) . "." . $extension;
            $folderpath = public_path('images');
            $file->move($folderpath, $fileName);
            Image::make($folderpath . '/' . $fileName)->save($folderpath . '/' . $fileName, 50);
            $scans[] = 'images/' . $fileName;
        }
        return response()->json($scans, 201);
    }

    function createTicketFromUrl(Request $request, $id)
    {

        $simpleTicketForm = TicketPopup::whereIsPublish(1)->findOrFail($id);
        $errors = Validator::make($request->all(),
            [
                'comment' => 'required|string|min:10'

            ],
            [
                'comment.required' => 'Заполните информацию о проблеме!',
                'comment.min' => 'Слишком короткое сообщение.'
            ])
            ->errors()
            ->getMessages();

        if ($errors) return response()->json($errors, 419);

        DB::beginTransaction();
        $popup = SubmitTicketPopup::create([
            'user_id' => Auth::id(),
            'ticket_popup_id' => $simpleTicketForm->id,
            'comment' => $request->input('comment'),
            'url' => $request->input('url'),
        ]);
        if ($simpleTicketForm->support_category) {
            $ticket = Ticket::create([
                'title' => $simpleTicketForm->support_category->name,
                'user_id' => Auth::id(),
                'status' => Ticket::status('open'),
                'category_id' => $simpleTicketForm->support_category->id,
                'submit_ticket_popup_id' => $popup->id,
            ]);

            $message = TicketMessage::create([
                'message' => $request->input('comment'),
                'user_id' => Auth::id(),
                'ticket_id' => $ticket->id,
            ]);
        }


        DB::commit();

        return response()->json(['message' => 'Обращение в обработке! Ожидайте ответ.']);
    }
}

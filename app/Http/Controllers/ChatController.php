<?php

namespace App\Http\Controllers;

use App\Events\ChatMessage;
use App\Modules\LiveChat\Chat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
   function index()
   {
       $chat = Chat::whereAlias('transbaza')->first();
       return view('user.chat.index', compact('chat'));
   }

   function hello($id)
   {
       $chat = Chat::findOrFail($id);
       ChatMessage::dispatch($chat);

       return $chat;
   }

   function post(Request $request, $id)
   {
       $chat = Chat::findOrFail($id);
       $errors = Validator::make($request->all(), [
           'message' => 'required|string|min:5|max:144',
       ],
           [
               'message.required' => 'Сообщение не может быть пустым',
               'message.min' => 'Слишком короткое сообщение',
               'message.max' => 'Слишком длинное сообщение',
           ]
       )->setAttributeNames([
           'message' => 'Сообщение'
       ])->errors()
           ->all();
       $check = \App\Modules\LiveChat\ChatMessage::where('created_at', '>', now()->subMinute()->format('Y-m-d H:i:s'))->whereUserId(Auth::id())->get();
       if($check->count() > 2){
           $errors[] = 'Вы можете отправить не более 2х сообщений в минуту!';
       }

       if ($errors) return response()->json(['errors' => implode(' ', $errors)], 419);


       \App\Modules\LiveChat\ChatMessage::create([
           'message' => $request->message,
           'ip' => $request->ip(),
           'user_id' => \Auth::check() ? \Auth::id() : 0,
           'chat_id' => $id,
       ]);
       $chat->refresh();
       ChatMessage::dispatch($chat);
   }

   function deleteMessage($id)
   {
       if(!Auth::user()->checkRole(['admin'])){
           return response()->json(['errors' => 'Ошибка доступа.'], 419);
       }
       $message = \App\Modules\LiveChat\ChatMessage::findOrFail($id);
       $chat = $message->chat;
       $message->delete();
       $chat->refresh();
       ChatMessage::dispatch($chat);
   }
}

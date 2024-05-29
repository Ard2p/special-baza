<?php

namespace Modules\RestApi\Http\Controllers;

use App\Role;
use App\Service\SpamCheck\StopForumSpam;
use App\System\SpamEmail;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Modules\AdminOffice\Entities\Subscribe\Subscriber;

class SubscribesController extends Controller
{
    function addSubscribe(Request $request)
    {
      $errors = Validator::make($request->all(), [
          'email' => 'required|email|unique:subscribers,email'
      ])->errors()->all();

      if($errors){
          return response()->json(['errors' => implode(' ', $errors)], 400);
      }

      $user = User::whereEmail($request->email)->first();

      Subscriber::create([
          'email' => $request->email,
          'user_id' => $user ? $user->id : null
      ]);

      return response()->json([]);
    }


    function sendInfoMessage(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:255',
        ]);

        $message = (new MailMessage())
            ->subject('Новое обращение')
            ->line("Email: {$request->email}")
            ->line("Имя: {$request->name}")
            ->line("Тема: {$request->subject}")
            ->line("Сообщение: {$request->message}");
        Mail::to('ruslan@trans-baza.ru')->queue(new \App\Mail\Subscription($message, 'Новое обращение'));
        return response()->json();
    }
}

<link rel="stylesheet" href="/css/chat.css">
    <h4>Обращение #{{$ticket->id}}</h4>
<div class="panel panel-default tech-support-chat">
    <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-comments"></i> Обращение #{{$ticket->id}}</h3>
    </div>
    <div class="chat-wrap" id="messageBlock">
        @foreach($ticket->messages as $message)
            <div class="{{(!$message->is_admin)? 'left': 'right'}}">
                <div class="message">
                    <div class="data-msg">
                        <div class="image">
                            @if($message->is_admin)
                                <img class="img-circle avatar" alt="chat avatar"
                                     src="/images/support.png">
                            @else
                                <img width="60" height="60" src="{{$message->user->avatar}}">
                            @endif
                        </div>
                        <div class="msg">
                            {{$message->message}}
                            @if(!empty( $files = json_decode($message->files)))
                                <div class="gallery fancy-container">
                                    @foreach($files as $file)
                                        <a href="/{{$file}}" class="thumzbnail fancybox" rel="ligthbox"><img src="/{{$file}}"></a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="time">{{$message->created_at->format('d.m.Y H:i')}}</div>
                </div>
            </div>
        @endforeach
    </div>
    <form id="sendMessage" class="send-msg">
        <div class="form-item">

            @csrf
            <input type="hidden" name="_method" value="PATCH">
            <input type="hidden" name="ticket_id" id="ticket_id" value="{{$ticket->id}}">
            {{--class="form-control primary"--}}
            <input type="text" name="message" placeholder="Нажмите Enter">
            <button class="send" type="submit"></button>

        </div>
        {{--<div class="panel-footer">--}}
        {{--</div>--}}
    </form>
</div>
</div>

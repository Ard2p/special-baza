<div class="panel">
    <div class="panel-body">
        <h2>Почта</h2>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th>Тема</th>
                    <th>От</th>
                    <th>Дата</th>
                    <th class="text-center"><i class="fa fa-cog"></i></th>
                </tr>
                </thead>
                <tbody>
                @foreach($mails as $mail)
                    <tr>
                        <td>{{$mail->subject}}</td>
                        <td>{{$mail->from}}</td>
                        <td>{{$mail->date}}</td>
                        <td><a href="{{route('mail-box.show', $mail->uid)}}" class="btn btn-primary">Просмотр</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
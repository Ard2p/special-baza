@if($model->images)
<table style="width: 100%">
    @foreach($model->images as $k => $image)
        @if(($k %2) === 0 || $k === 0 || ($k+ 1 === count($model->images)) )
            <tr>
                @endif
                <td>
                    @php
                        echo '${' . "{$model->id}_${k}" . '}';
                    @endphp
                </td>
                @if(($k %2) === 0 || $k === 0 || ($k+ 1 === count($model->images)) )
            </tr>
        @endif
    @endforeach

</table>
    @endif
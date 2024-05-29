<table style="width: 100%; border: 2px #d1d1d1 solid; font-size: 14px">
    @foreach($model->characteristics as $attribute)
        <tr>
            <td style="font-size: 16px">{{$attribute->name}}</td>
            <td style="font-size: 16px">{{$attribute->pivot->value}} {{$attribute->unit}}</td>
        </tr>
    @endforeach

</table>
@extends('layouts.main')
@section('content')

    <div class="table-responsive">
        <table class="table">
            <thead>
            <tr>
                <th>Регион/Город</th>
                @foreach($cats as $cat)
                    <th>{{$cat->name}}</th>
                @endforeach
            </tr>
            </thead>
            <tbody>
            @foreach($cities as $name => $city)
                <tr>
                    @php
                    $_city = \App\City::findOrFail($name);
                    @endphp
                    <td>{{$_city->region->name}}, {{$_city->name}}</td>

                    @foreach($cats as $cat)
                        @foreach($city as $id => $category)
                            @if($id === $cat->id)
                                <td>{{$category->count()}}/{{\App\User::whereHas('machines', function ($q)use($cat, $_city){
                                $q->where('type', $cat->id)
                                ->where('city_id', $_city->id);
                                })->withTrashed()->count()}}</td>
                               @continue(2)
                            @endif

                        @endforeach
                            <td>0</td>
                    @endforeach

                </tr>
            @endforeach

            </tbody>
        </table>
    </div>
@endsection
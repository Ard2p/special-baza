<div class="table-responsive">
    <table class="table table-striped table-bordered rotate_table">
        <thead>
        <tr>
            <th><div>Регион/Город</div></th>
            @switch($request->show_type)
                @case('m')
                @case('n')
                <th class="rotate"><div>Итого</div></th>
                @break
            @endswitch
            @foreach($cats as $cat)
                <th class="rotate"><div>{{$cat->name}}</div></th>
            @endforeach

        </tr>
        </thead>
        <tbody>
        @php
            $total_users = 0;
            $total_machines = 0;
        @endphp
        @foreach($machines as $name => $city)
            <tr>
                @php
                    $_city = \App\City::findOrFail($name);
                @endphp
                <td>{{$_city->region->name}}, {{$_city->name}}</td>
                @switch($request->show_type)
                    @case('m')
                    <td data-category="{{$request->input('category')?: 0}}"
                        data-region="{{$request->input('region_id')?: 0}}"
                        data-city="{{$_city->id}}"
                        data-type="m"
                        class="more-info">{{$city->m_count}}</td>
                    @break
                    @case('n')
                    <td data-category="{{$request->input('category') ?: 0}}"
                        data-region="{{$request->input('region_id') ?: 0}}"
                        data-city="{{$_city->id}}"
                        data-type="n"
                        class="more-info">{{$city->n_count}}</td>
                    @break
                @endswitch
                @foreach($cats as $cat)
                    @foreach($city as $id => $category)
                        @if($id === $cat->id)
                            @php
                                $total_machines += $machines_count = $category->n_count;
                                $total_users += $contractors = $category->m_count;
                            @endphp
                            @switch($request->show_type)
                                @case('n_m')
                                <td data-category="{{$id}}" data-region="0" data-city="{{$name}}" data-type="n_m"
                                    class="more-info">{{$machines_count}} <b>({{$contractors}})</b></td>
                                @break
                                @case('m_n')
                                <td data-category="{{$id}}" data-region="0" data-city="{{$name}}" data-type="m_n"
                                    class="more-info">{{$contractors}} <b>({{$machines_count}})</b></td>
                                @break
                                @case('m')
                                <td data-category="{{$id}}" data-region="0" data-city="{{$name}}" data-type="m"
                                    class="more-info">{{$contractors}}</td>
                                @break
                                @case('n')
                                <td data-category="{{$id}}" data-region="0" data-city="{{$name}}" data-type="n"
                                    class="more-info">{{$machines_count}}</td>
                                @break
                            @endswitch

                            @continue(2)
                        @endif

                    @endforeach
                    <td>0</td>
                @endforeach

            </tr>
        @endforeach
        @switch($request->show_type)
            @case('m')
            @case('n')
            <tr>
                <td>Итого</td>
                @switch($request->show_type)
                    @case('m')
                    <td data-category="{{$request->input('category') ?: 0}}"
                        data-region="{{$request->input('region') ?: 0}}" data-city="{{$request->input('city') ?: 0}}"
                        data-type="m" class="more-info">{{$total_users}}</td>
                    @break
                    @case('n')
                    <td data-category="{{$request->input('category') ?: 0}}"
                        data-region="{{$request->input('region') ?: 0}}" data-city="{{$request->input('city') ?: 0}}"
                        data-type="n" class="more-info">{{$total_machines}}</td>
                    @break
                @endswitch
                @foreach($cats as $cat)
                    @switch($request->show_type)
                        @case('n')
                        <td
                                data-category="{{$cat->id}}"
                                data-region="{{$request->input('region') ?: 0}}"
                                data-city="{{$request->input('city') ?: 0}}"
                                data-type="n"
                                class="more-info">
                            {{$cat->machines()->where(function ($q) use($request){
                            if ($request->filled('region_id')) {
                                $q->whereRegionId($request->region_id);
                            }
                            if ($request->filled('city')) {
                                $q->whereCityId($request->city);
                            }
                        })->count()}}</td>
                        @break
                        @case('m')
                        <td
                                data-category="{{$cat->id}}"
                                data-region="{{$request->input('region') ?: 0}}"
                                data-city="{{$request->input('city') ?: 0}}"
                                data-type="n"
                                class="more-info">
                            {{\App\User::whereHas('machines', function ($q) use($request, $cat){
                            $q->whereType($cat->id);
                            if ($request->filled('region_id')) {
                                $q->whereRegionId($request->region_id);
                            }
                            if ($request->filled('city')) {
                                $q->whereCityId($request->city);
                            }
                        })->withTrashed()->count()}}</td>
                        @break
                    @endswitch
                @endforeach
            </tr>
            @break
        @endswitch
        </tbody>
    </table>
</div>
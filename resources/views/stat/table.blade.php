@if($categories->isNotEmpty())
    <h3>{{$type === 'region' ? 'Статистика по региону.' : 'Статистика по городу.'}}</h3>
    <div class="clearfix"></div>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
            <tr>
                @if(!$category)
                <th rowspan="2" class="text-center" style="width: 20%">Категория техники</th>
                @endif
                <th colspan="3" class="text-center">Цена за 1 час, рублей</th>
                <th rowspan="2"   class="text-center"  style="width: 10%">Кол-во записей*</th>
            </tr>
            <tr>
                <th>Мин.</th>
                <th>Средн.</th>
                <th>Макс.</th>

            </tr>
            </thead>

            @foreach($categories as $cat)
                @if($cat->stats->isEmpty())
                    @continue
                @endif
                @php
                    $stat = $cat->stats->first();
                @endphp
                <tr>
                    @if(!$category)
                    <td>{{$cat->name}}</td>
                    @endif
                    <td>{{ number_format($stat->min, 0, ',', ' ')}}</td>
                    <td>{{number_format(round($stat->aver), 0, ',', ' ')}}</td>
                    <td>{{number_format($stat->max, 0, ',', ' ')}}</td>
                    <td>{{$stat->count}}</td>
                </tr>
            @endforeach
        </table>
        <b>* - статистика собрана на основании кол-ва записей</b>
    </div>
@else
    <h3>Данные не найдены</h3>
@endif
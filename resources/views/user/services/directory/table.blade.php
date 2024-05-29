<div class="table-responsive">
    <table id="table_total" class="table table-striped table-bordered">
        <thead>
        <tr>
            <th>
                <div>Регион/Город</div>
            </th>
            <th>
                <div>Кол-во техники</div>
            </th>
        </tr>
        </thead>
        <tbody>

        @foreach($regions as $region)
            @foreach($region->cities as $city)
                <tr>
                    <td>{{$city->region->name}}, {{$city->name}}.</td>
                    <td><a href="{{route('contractor_service_directory_main_result', [$category->alias, $city->alias, $region->alias])}}">
                            {{$city->contractor_services()->whereServiceCategoryId($category->id)->count()}}</a></td>
                </tr>
            @endforeach
        @endforeach
        </tbody>
    </table>
</div>
<div class="machine-card">
    @seoTop
    @contactTop
    @foreach($services as $service)


        <div class="row">
            <div>
                <a href="{!! $service->rent_url !!}">
                    <h2 style="    margin: 15px;">{{$service->category->name}}  <p
                                style="font-size: 15px;">{{$service->city->name ?? ''}}
                            , {{$service->region->name ?? ''}}</p></h2>
                </a>
            </div>

            <div class="col-md-6  proposal-wrap ">
                <div class="list-data">
                    <p>
                                                    <span><b>Цена:</b> {{$service->sum_format}}
                                                        руб</span>
                    </p>


                </div>
                <div class="image-wrap">
                    <a class="thumbnail fancybox" rel="ligthbox"
                       href="{!! $service->rent_url !!}">
                        <img alt="{{$service->category->name}}  {{$service->city->name ?? ''}}, {{$service->region->name ?? ''}}"
                             src="/{{$service->photo}}" class="img-responsive"
                             style="max-height: 400px;"></a>
                    <input id="profile-image-upload" class="hidden" type="file">
                </div>
            </div>

            <div class="col-md-6">
                <div class="list-params">
               @include('user.services.directory.list_attributes')

                </div>
                <div class="form-item">
                    <div class="button">
                        <a class="btn-custom"
                           href="{!! $service->rent_url !!}">Заказать
                        </a>
                    </div>
                </div>
            </div>
        </div>

    @endforeach
</div>
@extends('layouts.main')
@section('content')
    <div class="container bootstrap snippet">
        <div class="row">

            <form action="" class="machine-card" method="GET">
                <h1 class="text-center">#{{$user->id ?? ''}} ПРОДАЖА СПЕЦТЕХНИКИ</h1>
                <div class="search-wrap machineries-wrap row" style="padding: 0">

                    <div class="col-md-12 harmony-accord">
                        <h3>Параметры списка</h3>
                        <div class="list-params">
                            <div class="machinery-filter-wrap">
                                <div class="tree-cols-list">
                                    <div class="col">
                                        <helper-select-input :data="{{$types->toJson()}}"
                                                             :column-name="{{json_encode('Категория техники')}}"
                                                             :place-holder="{{json_encode('Выберите категорию')}}"
                                                             :col-name="{{json_encode('type_id')}}"
                                                             :depend="1"
                                                             :types-url="{{json_encode(['url' => 'api/get-user-regions'])}}"
                                                             :user-id="{{$user->id ?? 0}}"

                                                             :initial="{{json_encode($initial_type ?? '')}}"
                                                             :show-column-name="1"></helper-select-input>
                                    </div>
                                    <div class="col">
                                        <helper-select-input :data="{{$regions->toJson()}}"
                                                             :column-name="{{json_encode('Регион')}}"
                                                             :place-holder="{{json_encode('Выберите регион')}}"
                                                             :col-name="{{json_encode('region')}}"
                                                             :required="0"

                                                             :user-id="{{$user->id ?? 0}}"
                                                             :cities="{{json_encode(['url' => 'api/get-user-cities'])}}"

                                                             :types-region-url="{{json_encode(['url' => 'api/get-user-types'])}}"
                                                             :depend="1"

                                                             :initial="{{json_encode($initial_region ?? '')}}"
                                                             :initial-city="{{json_encode($checked_city_source ?? '')}}"
                                                             :city-data="{{json_encode([])}}"
                                                             :show-column-name="1"
                                                             :hide-city="1">
                                        </helper-select-input>

                                    </div>
                                    <div class="col">
                                        <helper-select-input :data="{{json_encode([])}}"
                                                             :column-name="{{json_encode('Город')}}"
                                                             :place-holder="{{json_encode('Город')}}"
                                                             :required="0"

                                                             :depend="1"
                                                             :user-id="{{$user->id ?? 0}}"
                                                             :col-name="{{json_encode('city_id')}}"
                                                             :initial="{{json_encode($checked_city_source ?? '')}}"
                                                             :show-column-name="1"
                                                             :hide-city="1"></helper-select-input>

                                    </div>

                                </div>
                                <div class="tree-cols-list">
                                    <div class="col">
                                        <div class="form-item">
                                            <div class="button">
                                                <button class="btn-custom black" type="submit">Поиск</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <div id="machine_show">
                @seoTop
                @contactTop
                <div class="">
                    @if(!$user)
                        <div class="text-center">
                            {{$machines->links( "pagination::bootstrap-4")}}
                        </div>
                    @endif
                    @foreach($machines as $machine)

                        <div class="machine-card">
                            <div class="row" itemscope itemtype="http://schema.org/Product">
                                @include('user.machines.schema')
                                <div>
                                    <a href="{{$machine->rent_url}}">
                                        <h2 style="    margin: 15px;">{{$machine->name}}
                                            <p style="font-size: 15px;">
                                                {{$machine->_type->name}} {{$machine->brand->name ?? ''}}
                                                {{$machine->city->name ?? ''}}
                                                , {{$machine->region->name ?? ''}}
                                            </p></h2>
                                    </a>
                                </div>
                                <div class="col-md-6  proposal-wrap ">
                                    <div class="image-wrap">
                                        <a class="thumbnail fancybox" rel="ligthbox"
                                           href=" /{{$machine->photo}}">
                                            <img alt="Фото техники"
                                                 src="/{{$machine->photo}}" class="img-responsive"
                                                 style="max-height: 400px;"></a>
                                        <input id="profile-image-upload" class="hidden" type="file">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="list-params">
                                        <p>
                                                    <span><b>Цена:</b> {{$machine->sale->price_format}}
                                                        руб</span>
                                        </p>
                                        <p>
                                            <strong style="margin-bottom: auto">Описание:</strong>{!! nl2br(e($machine->sale->description)) !!}
                                        </p>
                                        @foreach($machine->optional_attributes as $attribute)
                                            <p style="padding: 5px;" class="small">
                                                <span><b>{{$attribute->name}}</b> {{$attribute->pivot->value}} ({{$attribute->unit}})</span>
                                            </p>
                                        @endforeach

                                    </div>
                                    <div class="form-item">
                                        <div class="button">
                                            <button class="btn-custom" type="button" data-toggle="modal"
                                                    data-target="#proposal-modal-{{$machine->id}}">Купить
                                            </button>
                                        </div>
                                    </div>
                                </div>


                            </div>
                        </div>
                        <div class="modal modal-fade" id="proposal-modal-{{$machine->id}}" style="display: none;">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal"><span
                                                    aria-hidden="true">×</span><span
                                                    class="sr-only">Close</span></button>
                                        <h4 class="modal-title">
                                            Купить технику
                                        </h4>
                                    </div>
                                    <form class="machine-sale-form" action="{{route('sales.update', $machine->id)}}">
                                        <div class="modal-body order-modal"
                                             style="max-height: calc(100vh - 200px);overflow-y: auto;">
                                            @csrf
                                            @method('PATCH')
                                            <div class="form-item small">
                                                <label class="required">
                                                    Кто покупает?
                                                </label>
                                            </div>
                                            <div class="form-item small">
                                                <label>
                                                    <input type="text" name="_email" placeholder="Email"
                                                           value="{{Auth::check() ? Auth::user()->email : ''}}" {{Auth::check() ? 'disabled' : ''}}>
                                                </label>
                                            </div>
                                            <div class="form-item small">
                                                <label>
                                                    <input type="text" name="_phone" class="phone"
                                                           value="{{Auth::check() ? Auth::user()->phone : ''}}"
                                                           placeholder="Номер телефона" {{Auth::check() ? 'disabled' : ''}}>
                                                </label>
                                            </div>

                                            <div class="form-item">
                                                <label for="">Комментарий...
                                                    <textarea rows="3" name="comment"
                                                              placeholder="Комментарий..."></textarea>
                                                </label>
                                            </div>

                                            <div style="text-align: center">
                                                {!! NoCaptcha::display() !!}
                                            </div>
                                            <div class="form-item">
                                                <input type="hidden" name="captcha_error">
                                            </div>
                                            <div class="clearfix"></div>
                                        </div>

                                        <div class="modal-footer">
                                            <div class="form-item">
                                                <div class="button">
                                                    <button type="submit" class="btn-custom">ОТПРАВИТЬ
                                                        ЗАЯВКУ
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="form-item">
                                                <div class="button">
                                                    <button type="button" data-dismiss="modal" class="btn-custom black">
                                                        Отмена
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="clearfix"></div>
                        <div class="margin-wrap"></div>
                    @endforeach
                    @if(!$user)
                        {{$machines->links()}}
                    @endif
                </div>
                @seoBottom
                @contactBottom
            </div>
        </div>



    </div>
    {!! \App\Marketing\ShareList::renderShare() !!}
    @push('after-scripts')
        <script>

            $('[data-toggle="_datepicker"]').datetimepicker({
                format: 'Y/m/d',
                dayOfWeekStart: 1,
                timepicker: false
            });

        </script>
        <style>
            .thumbnail {
                margin-bottom: 0px;
            }
        </style>
    @endpush
    @include('scripts.machine.show')
@endsection
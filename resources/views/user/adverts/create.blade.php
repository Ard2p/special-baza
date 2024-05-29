@extends('layouts.main')
@section('content')
    <div class="container bootstrap snippet">
        <div class="row">

            <div class="col-md-12 user-profile-wrap box-shadow-wrap">

                <div class="row">
                    <div class="col-md-12">
                        <div class="row" id="errors" style="display: none">
                            <div class="alert alert-warning" id="alerts" role="alert">


                            </div>
                        </div>
                    </div>
                    <form id="advert_form" class="proposal-wrap" action="{{route('adverts.store')}}">
                        @csrf
                        <h3>@lang('transbaza_adverts.create_title')</h3>
                        <div class="col-md-6">
                            <helper-select-input :data="{{$categories->toJson()}}"
                                                 :column-name="{{json_encode(trans('transbaza_adverts.create_category'))}}"
                                                 :place-holder="{{json_encode(trans('transbaza_adverts.create_category'))}}"
                                                 :col-name="{{json_encode('category_id')}}"
                                                 :required="1"
                                                 :initial="{{json_encode($initial_type ?? '')}}"
                                                 :show-column-name="1"></helper-select-input>
                            <div class="form-item">
                                <label class="required">@lang('transbaza_adverts.create_name')</label>
                                <input class="promo_code"
                                       value=""
                                       name="name"
                                       type="text">
                            </div>
                            <div class="form-item">
                                <label  class="required">@lang('transbaza_adverts.create_description')</label>
                                <textarea class="promo_code" style="height: auto;"

                                          name="description"></textarea>
                            </div>

                            <div class="form-item">
                                <label class="required">@lang('transbaza_adverts.create_sum')</label>
                                <input class="promo_code"
                                       value="0"
                                       name="sum"
                                       type="number">
                            </div>



                            <helper-select-input :data="{{$rewards->toJson()}}"
                                                 :column-name="{{json_encode(trans('transbaza_adverts.create_bonus'))}}"
                                                 :place-holder="{{json_encode(trans('transbaza_adverts.create_choose_type'))}}"
                                                 :col-name="{{json_encode('reward_id')}}"
                                                 :required="1"
                                                 :initial="{{json_encode($initial_type ?? '')}}"
                                                 :show-column-name="1"></helper-select-input>
                            <div class="form-item" id="reward_text" style="display: none">
                                <label  class="required">@lang('transbaza_adverts.create_budget') </label>
                                <input class="promo_code"
                                       value=""
                                       name="reward_text"
                                       type="text">
                            </div>

                        </div>
                        <div class="col-md-6">

                                <div class="form-item">
                                    <label  class="required">@lang('transbaza_adverts.create_actual_date') </label>
                                    <input class="promo_code" data-toggle="datepicker"
                                           value=""
                                           name="actual_date"
                                           type="text">
                                </div>
                            <helper-select-input :data="{{$regions->toJson()}}"
                                                 :column-name="{{json_encode(trans('transbaza_adverts.create_choose_region'))}}"
                                                 :place-holder="{{json_encode(trans('transbaza_adverts.create_choose_region'))}}"
                                                 :col-name="{{json_encode('region_id')}}"
                                                 :required="1"
                                                 :initial="{{json_encode($initial_region ?? '')}}"
                                                 :show-column-name="1"
                                                 :hide-city="1">
                            </helper-select-input>

                            <helper-select-input :data="{{ json_encode([])}}"
                                                 :column-name="{{json_encode(trans('transbaza_adverts.create_choose_city'))}}"
                                                 :place-holder="{{json_encode(trans('transbaza_adverts.create_choose_city'))}}"
                                                 :col-name="{{json_encode('city_id')}}"
                                                 :required="1"
                                                 :initial="{{json_encode($checked_city_source ?? '')}}"
                                                 :show-column-name="1"
                                                 :hide-city="1">
                            </helper-select-input>

                            <div class="form-item">
                                <label class="required">@lang('transbaza_adverts.create_address')</label>
                                <input class="promo_code"
                                       value=""
                                       name="address"
                                       type="text">
                            </div>
                        </div>
                        <div class="col-xs-5">
                            <h4>@lang('transbaza_adverts.create_photo')</h4>

                            <helper-image-loader multiple-data="0" col-id="photoTech" col-name="photo"
                                                 :required="1"
                                                 :exist="{{json_encode(['img/no_product.png'])}}"
                                                 :url="{{json_encode(route('machinery.load-files'))}}"
                                                 :token="{{json_encode(csrf_token())}}"></helper-image-loader>

                        </div>
                        <div class="clearfix"></div>
                        <div class="col-md-12">
                            <div class="button">
                                <button type="submit" id="create_advert" class="btn-custom">
                                    @lang('transbaza_adverts.create_accept')
                                </button>
                            </div>
                        </div>


                    </form>

                </div>
            </div>
        </div>
    </div>

    @push('after-scripts')
        <script>
            $(document).on('reward_id', function (e, name, value, id) {
                $('#reward_text').hide()

                switch (id) {
                    case 1:
                        $('#reward_text').hide()
                        break;
                    case 2:

                        $('#reward_text label').html('Какой?')
                        $('#reward_text input').attr('placeholder', 'Введите описание')
                        $('#reward_text').show()
                        break;
                    case 3:
                        $('#reward_text label').html('Сколько?')
                        $('#reward_text input').attr('placeholder', 'Введите сумму')
                        $('#reward_text').show()
                        break;
                }

            })
            $(document).on('submit', '#advert_form', function (e) {
                e.preventDefault()
                let $form = $(this);

                $.ajax({
                    url: $form.attr('action'),
                    type: 'POST',
                    data: $form.serialize(),
                    success: function (e) {
                       showMessage(e.message)
                        setTimeout(function () {
                            window.location = e.url
                        }, 3000)

                    },
                    error: function (e) {
                        showErrors(e)
                    }
                })
            })
        </script>
    @endpush
@endsection
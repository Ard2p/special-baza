<div class="clearfix"></div>
@isset($type)
    @if(!empty($form->settings['category_settings']))
        @foreach($form->settings['category_settings'] as $set)

            @if($set['category'] == $type)
                <input type="hidden" id="__settings_popup_cat"
                       value="{{$set['delay'] ?? 0}}">
                @break
            @endif
        @endforeach
    @endif
@endisset
<form method="post" action="{{route('simple_form', $form->id)}}" id="submit_simple-form"
      style="padding: 10px;background: {{$form->settings['color']}};
              border-color: {{$form->settings['border']}};
              display: none;
              ">
    <a href="#" class="pull-right __close_simple_form"><i class="fa fa-window-close"
                                                          aria-hidden="true"></i></a>
    @csrf
    <div>
        {!! $form->form_text !!}
    </div>
    <div>
        {{--<div class="form-item">
            <label>Адрес выполнения заказа:</label>
            <input class="promo_code" value="{{$machine->region->name ?? ''}}"
                   type="text" disabled>
        </div>
        <div class="form-item">
            <label>Город:</label>
            <input class="promo_code" value="{{$machine->city->name ?? ''}}"
                   type="text" disabled>
        </div>--}}
        <input value="{{$region ?? ''}}" name="region_id"
               type="hidden">
        <input value="{{$type??''}}" name="type_id"
               type="hidden">
        <input value="{{$city ?? ''}}" name="city_id"
               type="hidden">

        <div class="form-item image-item">
            <label for="">
                {{--     Ваш email--}}
                <input type="email" name="_email" placeholder="Введите почту"
                       value="{{Auth::check() ? Auth::user()->email : ''}}" {{Auth::check() ? 'disabled' : ''}}>
                <span class="image email"></span>
            </label>
        </div>

        <div class="form-item image-item">
            <label>
                {{-- Телефон--}}
                <input type="text" class="phone" name="_phone" value="{{Auth::check() ? Auth::user()->phone : ''}}"
                       placeholder="Номер телефона" {{Auth::check() ? 'disabled' : ''}} />
                <span class="image phone"></span>
            </label>
        </div>

        <div class="form-item">
            <label for="">
                <textarea rows="3" name="comment" placeholder=" {{$form->comment_label}}"></textarea>
            </label>
        </div>

        <div>
            {!! NoCaptcha::display() !!}
        </div>
        <div class="form-item">
            <input type="hidden" name="captcha_error">
        </div>
        <div class="clearfix"></div>
        <div class="button">
            <button type="button" class="btn-custom" id="submit_simple_btn"
                    style="background: {{$form->settings['button_color']}};color: {{$form->settings['button_text_color']}}; height: auto">
                {{$form->button_text}}
            </button>
            <button type="button" class="btn-custom black __close_simple_form"
                    style="margin-top: 5px;">
                @lang('transbaza_proposal_search.modal_close')
            </button>
        </div>
    </div>
    <input type="hidden" name="simple_proposal_id" value="{{$form->id}}">
    <input type="hidden" name="url" value="{{Request::url()}}">
    <input type="hidden" name="form_id" value="{{$form->id}}">
</form>
@include('includes.popup_button')

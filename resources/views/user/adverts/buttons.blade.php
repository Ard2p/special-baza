<div class="col-md-12 text-center" id="action_buttons">
<h4>@lang('transbaza_adverts.i_interesting')</h4>
<div class="col-md-6">

    <div class="button">
        <a href="#" id="im_contractor" style="background: #ee2b24"
           class="btn-custom">{{$advert->category->button_text}}
        </a>
        <hr>
    </div>

</div>
<div class="col-md-6">

    <div class="button">
        <a class="btn-custom" id="im_agent" style="background:  #1E6F41;" href="#">
            @lang('transbaza_adverts.i_agent')
        </a>
        <hr>
    </div>

</div>
    <div class="col-md-6 col-md-offset-3">
        <div class="button">
            <a class="btn-custom black" target="_blank"  href="/howdoesitwork/zdelki/50/1">
                @lang('transbaza_adverts.how_does_it_work')
            </a>
        </div>
    </div>
<form class="col-md-12 text-left" action="{{route('push_advert_offer', $advert->alias)}}" id="__my_offer" style="display: none">
    @csrf
    <h4>@lang('transbaza_adverts.add_offer')</h4>
    <div class="col-sm-6">
        <div class="form-item">
            <label class="required">@lang('transbaza_adverts.add_offer_sum')</label>
            <input class="promo_code"
                   value="0"
                   name="sum"
                   type="number">

            <div class="form-item">
                <label class="required">@lang('transbaza_adverts.comment_for_customer')</label>
                <textarea class="promo_code" style="height: auto;"

                          name="comment"></textarea>
            </div>
        </div>
    </div>
    <div class="clearfix"></div>
    <div class="col-sm-6">
        <div class="button">
            <button style="background: #45c0ee" type="submit"
                    class="btn-custom">@lang('transbaza_adverts.add_offer')
            </button>
        </div>
    </div>

</form>
</div>
{{--
<div class="col-md-12 text-center" id="action_buttons">
    <h4>Вас заинтересовало это объявление?</h4>
    <div class="{{$advert->getRefererLink() ? 'col-md-3' : 'col-md-6'}} col-md-offset-3">
        <div class="btn-col">
            <div class="button">
                <a href="#" id="link_auth"
                   style="background: #ee2b24" class="btn-custom">Да, мне интересно
                </a>
                <hr>
            </div>
        </div>
    </div>
    @if($advert->getRefererLink())
        <div class="col-md-3">
            <div class="btn-col">
                <div class="button">
                    <a class="btn-custom" data-url="{!!  $advert->getRefererLink()->advert_unsubscribe !!}" id="disAdvert" style="background: #686868;" href="#"> Нет, не хочу его видеть
                    </a>
                    <hr>
                </div>
            </div>
        </div>
    @endif
</div>
--}}

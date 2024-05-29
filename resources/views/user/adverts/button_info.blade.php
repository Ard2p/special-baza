<h4>Вас заинтересовало это объявление? Если ДА, в чём ваш интерес?</h4>
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
            Я - Агент (Отправлю ссылку на это объявление своим друзьям)
        </a>
        <hr>
    </div>

</div>
<form class="col-md-12 text-left" action="{{route('push_advert_offer', $advert->alias)}}" id="__my_offer" style="display: none">
    @csrf
    <h4>Добавить предложение</h4>
    <div class="col-sm-6">
        <div class="form-item">
            <label class="required">Сумма:</label>
            <input class="promo_code"
                   value="0"
                   name="sum"
                   type="number">

            <div class="form-item">
                <label class="required">Комментарий для заказчика:</label>
                <textarea class="promo_code" style="height: auto;"

                          name="comment"></textarea>
            </div>
        </div>
    </div>
<div class="clearfix"></div>
    <div class="col-sm-6">
        <div class="button">
            <button style="background: #45c0ee" type="submit"
                    class="btn-custom">Добавить предложение
            </button>
        </div>
    </div>

</form>
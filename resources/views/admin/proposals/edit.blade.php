<div class="content body" id="tables_content">
    <div class="nav-tabs-custom ">
        <ul role="tablist" class="nav nav-tabs">
            <li role="presentation" class="active"><a href="#ru_locale" aria-controls="all" role="tab"
                                                      data-toggle="tab">
                    Формы сбора заявок
                </a></li>
            @foreach (\App\Option::$systemLocales as $locale)
                <li role="presentation"><a href="#{{$locale}}_locale" aria-controls="all" role="tab"
                                                          data-toggle="tab">
                       {{$locale}} Локализация
                    </a></li>

            @endforeach
        </ul>
        <div class="tab-content">
            <div role="tabpanel" id="ru_locale" class="tab-pane in active">
    <div class="panel">
        <div class="panel-body">
            <h3>Изменить форму сбора</h3>
            @if(Session::has('errors'))
                <div class="alert alert-warning">
                    <strong>Внимание!</strong>{!! Session::get('errors') !!}
                </div>
            @endif
            <form class="row" method="POST" action="{{route('simple-proposal.update', $form->id)}}">
                @csrf
                @method('PATCH')
                <div class="col-md-12">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Наименование формы</label>
                            <input class="form-control" type="text" value="{{$form->name}}" name="name">
                        </div>
                        @if($form->default !== 1)
                            <div class="form-group">
                                <label>URL адрес</label>
                                <input class="form-control" type="text" value="{{$form->url}}" name="url">
                            </div>
                            <label class="checkbox-inline">
                                <input type="checkbox" name="include_sub"
                                       value="1" {{$form->include_sub ? 'checked' : ''}}>Включить
                                подкатегории
                            </label>
                        @else
                            <input type="hidden" name="url" value="*">
                            <input type="hidden" name="position" value="top">
                            @endif
                        <div class="form-group">
                            <label>Текст кнопки</label>
                            <input class="form-control" type="text" name="button_text" value="{{$form->button_text}}">
                        </div>
                        <div class="form-group">
                            <label>Текст формы</label>
                            <textarea id="html" class="form-control" type="text"
                                      name="form_text">{{$form->form_text}}</textarea>
                        </div>

                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Название текстового поля</label>
                            <input class="form-control" type="text" name="comment_label"
                                   value="{{$form->comment_label}}">
                        </div>
                        @if($form->default !== 1)
                        <b>Расположение</b>
                        <div class="form-group">

                            <label class="radio-inline">
                                <input type="radio" name="position"
                                       value="top" {{$form->position === 'top' ? 'checked' : ''}}>Верхнее
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="position"
                                       value="bottom" {{$form->position === 'bottom' ? 'checked' : ''}}>Нижнее
                            </label>
                        </div>
                        @endif

                        <div class="form-group">
                            <label>Цвет</label>
                            <input class="form-control" type="color" value="{{$form->settings['color']}}" name="color">
                        </div>
                        <div class="form-group">
                            <label>Граница</label>
                            <input class="form-control" type="color" value="{{$form->settings['border']}}" name="border">
                        </div>
                        <div class="form-group">
                            <label>Цвет кнопки</label>
                            <input class="form-control" type="color" value="{{$form->settings['button_color']}}" name="button_color">
                        </div>
                        <div class="form-group">
                            <label>Цвет текста кнопки</label>
                            <input class="form-control" type="color" value="{{$form->settings['button_text_color']}}" name="button_text_color">
                        </div>
                        <popup-template
                                name-btn="{{'Добавить категорию'}}"
                                current-cat="{{json_encode(\App\Machines\Type::all()->toArray())}}"
                                settings="{{json_encode($form->settings['category_settings'])}}"

                        ></popup-template>

                        <script type="text/x-template" id="pop-template">
                            <div class="params-helper">
                                <div class="button" style="width: 200px;">
                                    <a href="#" class="btn btn-primary" @click.prevent="addParam" v-html="nameBtn"></a>
                                </div>

                                <div class="form" v-for="(param, i) in paramsList">
                                    <div class="col-xs-6">
                                        <div class="form-group">
                                            <label for="">
                                               По умолчанию открыта для Категории:
                                            </label>
                                            <select class="form-control" v-model="param.category">
                                                <option v-for="category in categories" :value="category.id" v-html="category.name"></option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xs-6">
                                        <div class="form-group">
                                            <label for="">
                                               Задержка (сек)
                                            </label>
                                            <input type="number" class="form-control" v-model="param.delay">
                                        </div>
                                    </div>
                                    <div class="col-xs-12">
                                        <div class="form-group ">
                                            <button href="#" class="btn btn-danger btn-xs" @click.prevent="delItem(i)"><i
                                                        class="fa fa-trash"></i>
                                                Удалить
                                            </button>
                                        </div>
                                    </div>
                                    <hr>
                                </div>
                                <input type="hidden" name="category_settings" :value="result">
                            </div>
                        </script>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <div class="btn-group">
                                <button class="btn btn-primary" type="submit">Сохранить</button>
                                <button class="btn btn-default" id="preview"
                                        data-toggle="modal"
                                        data-target="#preview_iframe"
                                        type="button"><i class="fa fa-eye"></i> Превью
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <hr>
        </div>
    </div>
            </div>

            @foreach (\App\Option::$systemLocales as $locale)
                @php
                    $en = $form->locale()->whereLocale($locale)->first();
                @endphp
                <div role="tabpanel" id="{{$locale}}_locale" class="tab-pane">
                    @if($en)
                        {!! view('admin.proposals.locales_update', ['locale' => $locale, 'localization' => $en, 'simple_id'=> $form->id])->render()  !!}
                        @else
                        {!! view('admin.proposals.locales_create', ['locale' => $locale, 'simple_id'=> $form->id])->render()  !!}
                    @endif
                </div>

            @endforeach
        </div>
    </div>
    <div class="modal" id="preview_iframe" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Превью</h4>
                </div>
                <div class="modal-body" id="__iframe">

                </div>

                <div class="modal-footer button two-btn">
                    <button type="button" class="btn btn-custom" data-dismiss="modal">Закрыть</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    var data_table_lang = {!!json_encode(trans('sleeping_owl::lang.table'))!!};
    document.addEventListener("DOMContentLoaded", function (event) {


        var token = document.head.querySelector('meta[name="csrf-token"]').content;
        Admin.WYSIWYG.switchOn('html', 'ckeditor', {
            'removeButtons': 'Save',
            'language': 'en',
            "height": 200,
            "script": true,
            "allowedContent": true,
            "extraPlugins": "panelbutton,uploadimage,image2,justify,youtube,uploadfile,colorbutton",
            "uploadUrl": "https://office.trans-baza.ru/ckeditor/upload/image?_token=" + token,
            "filebrowserUploadUrl": "https://office.trans-baza.ru/ckeditor/upload/image?_token=" + token
        })

        @foreach (\App\Option::$systemLocales as $locale)
        Admin.WYSIWYG.switchOn('html_{{$locale}}', 'ckeditor', {
            'removeButtons': 'Save',
            'language': 'en',
            "height": 200,
            "script": true,
            "allowedContent": true,
            "extraPlugins": "panelbutton,uploadimage,image2,justify,youtube,uploadfile,colorbutton",
            "uploadUrl": "https://office.trans-baza.ru/ckeditor/upload/image?_token=" + token,
            "filebrowserUploadUrl": "https://office.trans-baza.ru/ckeditor/upload/image?_token=" + token
        })
        @endforeach

        $(document).on('click', '#preview', function () {
            CKEDITOR.instances.html.updateElement();
            iframe(0)

            var result = {};
            $.each($('form').serializeArray(), function () {
                result[this.name] = this.value;
            });
            console.log(result);
            postToIframe(result, '/preview-form/0', 'target_iframe')
        })

        function postToIframe(data, url, target) {
            $('body').append('<form action="' + url + '" method="post" target="' + target + '" id="postToIframe"></form>');
            $.each(data, function (n, v) {
                $('#postToIframe').append('<textarea name="' + n + '" />' + v + '</textarea>');
                $('body').find('#postToIframe [name=' + n + ']').val(v);
            });
            $('#postToIframe').submit().remove();
        }

        function iframe(id) {
            $('#__iframe').html('')
            var link = "/preview-simple-form/";
            var _iframe = document.createElement('iframe');
            _iframe.width = "100%";
            _iframe.name = "target_iframe";
            _iframe.scrolling = "yes";
            _iframe.style.height = "400px";
            _iframe.setAttribute("src", link);
            document.getElementById("__iframe").appendChild(_iframe);

        }


        $(document).on('change', '[name=has_click]', function () {
            if (this.value == 'yes') {
                $('#show_click_type').show()
            } else {
                $('#show_click_type').hide()
            }
        })


    });
</script>

<div class="panel-body">
    <div class="form-elements">
        <div class="col-md-6">
            <div class="form-group">
                <label>Код аналитики &#x3C;head&#x3E;</label>

                <div class="input-group">
                    <textarea type="text" name="analytics_head"
                              class="form-control">{!! $options->where('key', 'analytics_head')->first()->value !!}</textarea>

                    <div class="input-group-addon">
                        <i></i>
                    </div>
                </div>
                <!-- /.input group -->
            </div>
            <div class="form-group">
                <label>Код аналитики &#x3C;body&#x3E;</label>

                <div class="input-group">
                    <textarea type="text" name="analytics_body"
                              class="form-control">{!! $options->where('key', 'analytics_body')->first()->value !!}</textarea>

                    <div class="input-group-addon">
                        <i></i>
                    </div>
                </div>
                <!-- /.input group -->
            </div>

            <div class="form-group">
                <label>Код над новостями</label>

                <div class="input-group">
                    <textarea type="text" name="custom_index_html"
                              class="form-control">{!! $options->where('key', 'custom_index_html')->first()->value !!}</textarea>

                    <div class="input-group-addon">
                        <i></i>
                    </div>
                </div>
                <!-- /.input group -->
            </div>

            <div class="form-elements">
                <div class="form-group">
                    <label for="exampleInputFile">Robots.txt</label>
                    <input type="file" name="robots">
                    @if($options->where('key', 'robots')->first()->value)<p class="help-block">Текущий файл <a
                                href="{{env('APP_URL')}}/robots.txt" target="_blank">robots.txt</a></p> @endif
                </div>
            </div>
            <div class="form-elements">
                <div class="form-group">
                    <label for="exampleInputFile">Sitemap.xml</label>
                    <input type="file" name="sitemap">
                    @if($options->where('key', 'sitemap')->first()->value)<p class="help-block">Текущий файл <a
                                href="{{env('APP_URL')}}/sitemap.xml" target="_blank">sitemap.xml</a></p> @endif
                </div>
            </div>
            <div class="form-group form-element-checkbox ">
                <div class="checkbox"><label>
                        <input name="system_sitemap"
                               type="checkbox"
                               value="1" {{$options->where('key', 'system_sitemap')->first()->value == '1' ? 'checked' : '' }}>
                        Заменить sitemap.xml на <a
                                href="{{route('sitemap_')}}" target="_blank">системный</a>
                    </label></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group form-elements">
                <label  class="control-label">
                   Мета заголовок</label>
                <input type="text" name="main_title" value="{{$options->where('key', 'main_title')->first()->value }}" class="form-control">
            </div>
            <div class="form-group form-elements">
                <label  class="control-label">
                    Ключевые слова</label>
                <input type="text" name="main_keywords" value="{{$options->where('key', 'main_keywords')->first()->value }}" class="form-control">
            </div>
            <div class="form-group">
                <label>Мета описание</label>

                <div class="input-group">
                    <textarea name="main_description"
                              class="form-control">{{$options->where('key', 'main_description')->first()->value }}</textarea>

                    <div class="input-group-addon">
                        <i></i>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
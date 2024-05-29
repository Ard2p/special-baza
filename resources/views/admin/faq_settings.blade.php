<div class="panel-body">
    <div class="form-elements">
        <div class="col-md-6">
            <h3>FAQ</h3>
            <hr>
            <div class="form-group form-elements">
                <label class="control-label">
                   H1</label>
                <input type="text" name="faq_meta_h1"
                       value="{{$options->where('key', 'faq_meta_h1')->first()->value }}" class="form-control">
            </div>
            <div class="form-group form-elements">
                <label class="control-label">
                    Мета заголовок</label>
                <input type="text" name="faq_meta_title"
                       value="{{$options->where('key', 'faq_meta_title')->first()->value }}" class="form-control">
            </div>
            <div class="form-group form-elements">
                <label class="control-label">
                    Ключевые слова</label>
                <input type="text" name="faq_meta_keywords"
                       value="{{$options->where('key', 'faq_meta_keywords')->first()->value }}" class="form-control">
            </div>
            <div class="form-group">
                <label>Мета описание</label>

                <div class="input-group">
                    <textarea name="faq_meta_description"
                              class="form-control">{{$options->where('key', 'faq_meta_description')->first()->value }}</textarea>

                    <div class="input-group-addon">
                        <i></i>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
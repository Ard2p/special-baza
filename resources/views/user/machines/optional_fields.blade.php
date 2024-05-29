@if($options->count())

    <div class="show-if-number">
        <div class="col roller-item">
            <div class="item">
                <i class="fas fa-plus active"></i>
                <i class="fas fa-minus"></i>
                <h4>Характеристики техники</h4>
            </div>
            <div class="content">
                @foreach($options as $option)
                    <div class="col-md-4">
                        <div class="form-item">
                            <label>
                                {{$option->current_locale_name}} ({{$option->unit}})
                                <input type="text" name="option_cat{{$id}}_{{$option->id}}" value=""
                                       placeholder="">
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
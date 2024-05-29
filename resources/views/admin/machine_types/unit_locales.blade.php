@foreach(\App\Option::$systemLocales as $locale)
    <div class="form-group form-element-text "><label for="name" class="control-label">
            Локалиазция {{$locale}}
@php
$val = $unit->locale()->whereLocale($locale)->first();
$val = $val ? $val->name : '';
@endphp
            <span class="form-element-required">*</span></label> <input type="text" name="{{$locale}}_name"
                                                                        value="{{$val}}" class="form-control">
    </div>
@endforeach
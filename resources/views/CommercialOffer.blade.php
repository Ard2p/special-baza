<h4 style="font-size: 14px; font-weight: bold">{{$model->category->name}} {{$model->name}}</h4>
@if($model->images)

    ${imagesTable@php echo $model->id; @endphp}
@endif
${attributesTable@php echo $model->id; @endphp}
{{--<h3 style="font-weight: bold;">{{trans('transbaza_machine_edit.cost_title')}}</h3>--}}
{{--${rentTable@php echo $model->id; @endphp_with_driver}
${rentTable@php echo $model->id; @endphp_without_driver}--}}
{{--<h3 style="font-weight: bold;">{{trans('transbaza_services.title_h1')}}</h3>--}}
{{--${servicesTable@php echo $model->id; @endphp}--}}
{{--
<h3 style="font-weight: bold;">{{trans('transbaza_machine_edit.delivery_cost')}}</h3>
${deliveryTable@php echo $model->id; @endphp_forward}
${deliveryTable@php echo $model->id; @endphp_back}
--}}



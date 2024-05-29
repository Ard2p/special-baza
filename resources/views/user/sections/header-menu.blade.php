<div class="container">
    <div class="row">
        <div class="col-md-12 button three-btns box-shadow-wrap header-custom">
            @customer<a href="{{route('search.index')}}" class="btn-custom black">@lang('transbaza_menu.search_contractor')</a>@endCustomer
            @performer<a href="{{route('machinery.index')}}" class="btn-custom black">@lang('transbaza_menu.my_machineries')</a>@endPerformer
            @performer<a href="{{route('order.index')}}" class="btn-custom black">@lang('transbaza_menu.customer_proposals')</a>@endPerformer
            @customer<a href="{{route('customer.proposals')}}" class="btn-custom black">@lang('transbaza_menu.my_proposals')</a>@endCustomer
            @customer<a href="{{route('customer.orders')}}" class="btn-custom black">@lang('transbaza_menu.my_orders')</a>@endCustomer
            @performer<a href="{{route('fire.proposals')}}" class="btn-custom black">@lang('transbaza_menu.fire_proposals')</a>@endPerformer
            @performer<a href="{{route('orders.index')}}" class="btn-custom black">@lang('transbaza_menu.my_orders')</a>@endPerformer
        </div>
    </div>
</div>
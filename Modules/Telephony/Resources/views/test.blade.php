@extends('telephony::layouts.master')

@section('content')
<div id="phone_block"></div>
@push('scripts')
 <script>
  var mcConfig = {login: "86b8fa62-f0e4-4607-9da8-9a30661c138b", password: "14b6637fa216"};

  MightyCallWebPhone.ApplyConfig(mcConfig);
  MightyCallWebPhone.Phone.Init("phone_block"); //id контейнера для встраивания WebPhone.

 </script>
 @endpush
@stop

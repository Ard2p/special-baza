@extends('telephony::layouts.master')

@section('content')

 <telephony
         :calls-url="{{json_encode(route('get_telephony_calls'))}}"
         :proposal-url="{{json_encode(route('phone_proposal'))}}"
         :dep-url="{{json_encode(route('dep_drop'))}}"
         :all-brands="'{{\App\Machines\Brand::all()}}'"
         :all-categories="'{{\App\Machines\Type::all()}}'"
          :created-proposals="'{{\Modules\Telephony\Entities\PhoneProposal::all()}}'"
         :all-regions="'{{\App\Support\Region::whereCountry('russia')->with('cities')->get()}}'"
 ></telephony>
@stop

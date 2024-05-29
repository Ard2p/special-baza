@extends('new-front.Layouts.app')

@section('content')

                  <router-view
                          {{--:init-static="{{json_encode(true)}}"
                          :filter-categories="{{json_encode($categories)}}"
                          :filter-regions="{{json_encode($regions)}}"
                          :filter-cities="{{json_encode($current_region ? $current_region->cities : [])}}"
                          :initial-vehicles="{{json_encode($machines)}}"
                          :initial-region="{{json_encode($current_region ?: [])}}"
                          :initial-city="{{json_encode($current_city ?: [])}}"
                          :initial-category="{{json_encode($category)}}"
                          :search-url="{{json_encode(route('search_vehicles'))}}"--}}
                  ></router-view>

@stop

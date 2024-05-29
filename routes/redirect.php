<?php

Route::get('/spectehnika/arenda_{category}', function ($cat){
    return redirect()->route('directory_main_category', $cat)->setStatusCode(301);;
});//->name('directory_main_category');

Route::get('/spectehnika/arenda_{category}/{city}_{region}', function ($cat, $city, $region){
    return redirect()->route('directory_main_result', [$cat, $region, $city])->setStatusCode(301);
});//->name('directory_main_result');

Route::get('/spectehnika/arenda_{category}/{city}_{region}/{alias}', function ($cat, $city, $region, $alias){
    return redirect()->route('show_rent', [$cat, $region, $city, $alias])->setStatusCode(301);
});//->name('show_rent');
<?php

Route::group(['prefix' => 'app'], function(){
    Route::group(['prefix' => '/v1'], function(){
        Route::post('signup', 'RegistrationController@register')->name('signup');
        Route::post('login', 'AuthenticationController@login')->name('login');
        Route::get('combo-list', 'RegistrationController@getCombo')->name('combo-list');
        //After Login
        Route::get('complain-list', 'ComplainController@getComplain')->name('complain-list');
        Route::post('add-complain', 'ComplainController@addComplain')->name('add-complain');
    });
});


<?php

Route::match(["options","post"],'/app/index/notice', 'App\IndexController@notice');//首页列表
Route::match(["options","post"],'/app/index/init', 'App\IndexController@init');//首页列表
Route::match(["options","post"],'/app/index/save', 'App\IndexController@save');//首页列表
Route::match(["options","post"],'/app/index/mobile', 'App\IndexController@mobile');//首页列表
Route::match(["options","post"],'/app/index/lists', 'App\IndexController@lists');//首页列表
Route::match(["options","post"],'/app/index/get_date_num', 'App\IndexController@getDateNum');//首页列表

Route::match(["options","post"],'/app/clue/add', 'App\ClueController@add');//首页列表
Route::match(["options","post"],'/app/upload/excel', 'App\UploadController@excel');//首页列表


Route::namespace('App')->middleware(["token","api"])->group(function () {

   // Route::match(["options","post"],'/app/index/lists', 'IndexController@lists');//首页列表

});
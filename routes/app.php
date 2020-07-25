<?php

Route::match(["options","post"],'/app/index/notice', 'IndexController@notice');//首页列表

Route::namespace('App')->middleware(["token","api"])->group(function () {

   // Route::match(["options","post"],'/app/index/lists', 'IndexController@lists');//首页列表

});
<?php
//后台api

Route::match(["options","post"],'/admin/login/index', 'Admin\LoginController@index');//登陆

Route::match(["options","post"],'/admin/perm/lists', 'Admin\PermController@lists')-> name("权限模块_权限列表");


Route::namespace('Admin')->middleware(["web_token","api"])->group(function () {

    Route::match(["options","post"],'/admin/user/lists', 'UserController@lists')-> name("用户模块_用户列表");
    Route::match(["options","post"],'/admin/user/save', 'UserController@save')->name("用户模块_保存用户");

    Route::match(["options","post"],'/admin/depart/admin_lists', 'DepartController@adminLists')-> name("用户模块_管理员列表");
    Route::match(["options","post"],'/admin/depart/admin_save', 'DepartController@admin_save')-> name("用户模块_管理员保存");
    Route::match(["options","post"],'/admin/depart/admin_del', 'DepartController@admin_del')-> name("用户模块_管理员删除");

    Route::match(["options","post"],'/admin/version/lists', 'VersionController@lists')-> name("版本模块_版本列表");
    Route::match(["options","post"],'/admin/version/save', 'VersionController@save')-> name("版本模块_版本保存");

    Route::match(["options","post"],'/admin/flow/lists', 'FlowController@lists')-> name("流程模块_流程列表");
    Route::match(["options","post"],'/admin/flow/save', 'FlowController@save')-> name("流程模块_流程保存");

    Route::match(["options","post"],'/admin/node/lists', 'FlowNodeController@lists')-> name("流程模块_节点列表");
    Route::match(["options","post"],'/admin/node/save', 'FlowNodeController@save')-> name("流程模块_节点保存");
});



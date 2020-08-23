<?php
//后台api

Route::match(["options","post"],'/admin/login/index', 'Admin\LoginController@index');//登陆

Route::match(["options","post"],'/admin/perm/lists', 'Admin\PermController@lists')-> name("权限模块_权限列表");

Route::any('/admin/business/export_lists', 'Admin\BusinessController@exportList')-> name("商机模块_导出商机表格");

Route::any('/admin/business/get_type', 'Admin\BusinessController@getType');


Route::namespace('Admin')->middleware(["web_token","api"])->group(function () {

    Route::match(["options","post"],'/admin/upload/excel', 'UploadController@excel')-> name("权限模块_文件上传");

    Route::match(["options","post"],'/admin/user/lists', 'UserController@lists')-> name("权限模块_员工列表");
    Route::match(["options","post"],'/admin/user/save', 'UserController@save')->name("权限模块_员工保存");
    Route::match(["options","post"],'/admin/user/my_allot_user_list', 'UserController@myAllotUserList')->name("权限模块_我的员工");
    Route::match(["options","post"],'/admin/user/set_month_target', 'UserController@setMonthTarget')->name("权限模块_设置绩效");

    Route::match(["options","post"],'/admin/business/lists', 'BusinessController@lists')-> name("商机模块_商机列表");
    Route::match(["options","post"],'/admin/business/save', 'BusinessController@save')-> name("商机模块_商机保存");
    Route::match(["options","post"],'/admin/business/follow_log', 'BusinessController@follow_log')-> name("商机模块_跟进记录");
    Route::match(["options","post"],'/admin/business/allot', 'BusinessController@allot')-> name("商机模块_商机分配");
    Route::match(["options","post"],'/admin/business/confirm', 'BusinessController@confirm')-> name("商机模块_领取确认");
    Route::match(["options","post"],'/admin/business/continue_follow', 'BusinessController@continue_follow')-> name("商机模块_继续跟进");

    Route::match(["options","post"],'/admin/depart/lists', 'DepartController@lists')-> name("权限模块_部门列表");
    Route::match(["options","post"],'/admin/depart/save', 'DepartController@save')-> name("权限模块_部门保存");
    Route::match(["options","post"],'/admin/depart/del', 'DepartController@del')-> name("权限模块_部门删除");

    Route::match(["options","post"],'/admin/role/lists', 'RoleController@lists')-> name("权限模块_角色列表");
    Route::match(["options","post"],'/admin/role/save', 'RoleController@save')-> name("权限模块_角色保存");
    Route::match(["options","post"],'/admin/role/del', 'RoleController@del')-> name("权限模块_角色删除");

    Route::match(["options","post"],'/admin/commonsea/lists', 'CommonSeaController@lists')-> name("公海模块_公海列表");
    Route::match(["options","post"],'/admin/giveup/lists', 'GiveupController@lists')-> name("废弃库_废弃列表");

    Route::match(["options","post"],'/admin/datacount/staff', 'DataCountController@staff')-> name("数据看板_员工列表");
    Route::match(["options","post"],'/admin/datacount/everyday', 'DataCountController@everyday')-> name("数据看板_每日数据");


});



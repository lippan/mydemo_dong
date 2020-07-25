<?php
//App中心  路由  match(["options","post"]) 支持多种方式请求 防止options直接被过滤
Route::match(["options","post"],'/app/login/index', 'App\LoginController@index');//登陆
Route::match(["options","post"],'/app/login/reflesh_token', 'App\LoginController@refleshToken');//刷新token

Route::match(["options","post"],'/app/version/newest', 'App\VersionController@newest');//版本最新信息

Route::namespace('App')->middleware(["token","api"])->group(function () {

    Route::match(["options","post"],'/app/index/lists', 'IndexController@lists');//首页列表
    Route::match(["options","post"],'/app/index/do_wait', 'IndexController@doWait');//处理待办

    Route::match(["options","post"],'/app/user/lists', 'UserController@lists');//用户列表
    Route::match(["options","post"],'/app/user/my', 'UserController@my');//用户--个人中心

    Route::match(["options","post"],'/app/address/add', 'AddressController@add');//添加用户地址
    Route::match(["options","post"],'/app/address/edit', 'AddressController@edit');//修改用户地址
    Route::match(["options","post"],'/app/address/del', 'AddressController@del');//删除用户地址

    Route::match(["options","post"],'/app/customer/quick_add', 'CustomerController@quick_add');//快速创建
    Route::match(["options","post"],'/app/customer/lists', 'CustomerController@lists');//客户列表
    Route::match(["options","post"],'/app/customer/save', 'CustomerController@save');//客户修改
    Route::match(["options","post"],'/app/customer/info', 'CustomerController@info');//客户详情

    Route::match(["options","post"],'/app/visit/lists', 'VisitController@lists');//拜访列表
    Route::match(["options","post"],'/app/visit/info', 'VisitController@info');//拜访详情
    Route::match(["options","post"],'/app/visit/save', 'VisitController@save');//保存拜访
    Route::match(["options","post"],'/app/visit/history', 'VisitController@history');//拜访历史

    Route::match(["options","post"],'/app/clue/lists', 'ClueController@lists');//线索列表
    Route::match(["options","post"],'/app/clue/info', 'ClueController@info');//线索详情
    Route::match(["options","post"],'/app/clue/save', 'ClueController@save');//保存线索
    Route::match(["options","post"],'/app/clue/history', 'ClueController@history');//线索历史
    Route::match(["options","post"],'/app/clue/my_customer_lists', 'ClueController@myCustomerLists');//线索-关联客户

    Route::match(["options","post"],'/app/business/lists', 'BusinessController@lists');//商机
    Route::match(["options","post"],'/app/business/info', 'BusinessController@info');//商机
    Route::match(["options","post"],'/app/business/save', 'BusinessController@save');//保存商机

    Route::match(["options","post"],'/app/agreement/lists', 'AgreementController@lists');//合同列表
    Route::match(["options","post"],'/app/agreement/info', 'AgreementController@info');//合同详情
    Route::match(["options","post"],'/app/agreement/save', 'AgreementController@save');//合同保存
    Route::match(["options","post"],'/app/agreement/attachment_save', 'AgreementController@attachment_save');//合同附件保存
    Route::match(["options","post"],'/app/agreement/attachment_info', 'AgreementController@attachment_info');//合同附件详情
    Route::match(["options","post"],'/app/agreement/apply_verify', 'AgreementController@apply_verify');//合同附件详情
});
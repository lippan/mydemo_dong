<?php
//公共调用 路由
Route::get('/', function() {
    return 'Hello World';
});

Route::get('/check_health', function() {
    return 'Hello World';
});

//上传图片获取OSS令牌信息
Route::any('/oss/get_oss_policy', 'Common\OssController@getOssPolicy');
//获取用户类型
Route::any('/customer/type_lists', 'Common\CustomerController@typeLists');
Route::any('/customer/init', 'Common\CustomerController@init');
Route::any('/customer/main_lists', 'Common\CustomerController@mainLists');

//获取与验证短信
Route::any('/sms/get_sms_code', 'Common\SmsController@getSmsCode');
Route::any('/sms/check_sms', 'Common\SmsController@checkSms');

//获取地址信息
Route::any('/address/get_province', 'Common\AddressController@getProvince');
Route::any('/address/get_city', 'Common\AddressController@getCity');
Route::any('/address/get_area', 'Common\AddressController@getArea');

Route::any('/depart/lists', 'Common\DepartController@lists');//部门列表
Route::any('/perm/sync', 'Admin\PermController@sync');//权限同步

Route::any('/crm/get_api_list', 'Api\CrmController@getApiList');//登陆
Route::any('/sap/get_category', 'Api\SapController@getCategory');//获取分类信息
Route::any('/sap/get_parent_category', 'Api\SapController@getParentCategory');//获取固定一级分类信息
Route::any('/sap/get_bom_lists', 'Api\SapController@getBomList');//获取物料清单

//获取地图信息
Route::any('/map/get_around', 'Common\MapController@getAround');//获取周边信息
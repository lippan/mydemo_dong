<?php
//(去除前缀的)数据库表名称 便于统一,每个仓位进行表名初始化
return [
    "user_table"            => "user",//账号表
    "wechat_table"          => "wechat",//微信用户表
    "sms_log_table"         => "sms_log",//短信记录表(可公共)
    "address_table"         => "address",//地址表(可公共)
    "depart_table"          => "depart",//部门架构表(可公共)
    "admin_operate_log"     => "admin_operate_log",//操作记录表
    "role_table"            => "role",//角色表
    "perm_table"            => "perm",//权限表
    "business_table"        => "business",//商机列表
    "business_log"          => "business_log",//商机日志列表
];

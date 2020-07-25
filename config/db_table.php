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
    "crm_request_log"       => "crm_request_log",//crm请求日志表
    "customer_table"        => "customer",//客户表
    "customer_type"         => "customer_type",//客户类型表
    "customer_normal_info"  => "customer_normal_info",//普通客户详情表
    "customer_company_info" => "customer_company_info",//公司客户详情表
    "customer_charge_log"   => "customer_charge_log",//客户责任人记录表
    "visit_table"           => "visit_log",//拜访列表
    "clue_table"            => "clue_log",//线索列表
    "business_table"        => "business_log",//商机列表
    "business_product"      => "business_product",//商机产品表
    "agreement_table"       => "agreement",
    "agreement_attachment"  => "agreement_attachment",//合同附件表
    "main_business"         => "main_business",//主营业务表
    "tag_product"           => "tag_product",//标签-商品表
    "tag_weight"            => "tag_weight",//标签-重量表
    "wait_done_event"       => "wait_done_event",//待办事项
    "flow_table"            => "flow",//流程表
    "flow_node"             => "flow_node",//流程节点表
    "flow_link"             => "flow_link",//流程线表
    "flow_business_log"     => "flow_business_log",//流程线表
    "crm_org"               => "crm_org",//crm组织表
    "crm_syb_agent"         => "crm_syb_agent",//crm事业部代理商表
    "crm_employee"          => "crm_employee",//crm雇员关系表
    "crm_position"          => "crm_position",//crm职位表
    "crm_org_admin"         => "crm_org_admin",//crm单组织管理者
    "crm_source"            => "crm_source",//crm线索来源表
    "version"               => "app_version",//版本库
];

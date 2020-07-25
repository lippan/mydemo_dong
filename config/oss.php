<?php
//阿里云 OSS 配置
return [
    'bucket'        => env('OSS_BUCKET', 'syjx-mkt'),
    'endpoint'      => env('OSS_END_POINT', 'oss-cn-beijing.aliyuncs.com'),
    'accessKeyId'   => env('OSS_ACCESS_KEY_ID',''),
    'accessKeySecret'   => env('OSS_ACCESS_KEY_SECRET',''),
    'timeout'       => env('OSS_TIME_OUT',10),
    'dir'           => env('OSS_DIR','customer'),
    'base_url'      => "https://syjx-mkt.oss-cn-beijing.aliyuncs.com"
];

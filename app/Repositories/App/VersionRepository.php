<?php

/**
 *  版本资源
 */
namespace App\Repositories\App;

use Illuminate\Support\Facades\DB;

class VersionRepository
{
    use \App\Helpers\ApiResponse;

    private $version;

    private $base_url;//图片地址

    //初始化需要用到的表名称
    public function __construct()
    {
        $this->version                  = config('db_table.version');
        $this->base_url                 = config('oss.base_url');
    }


    /**
     * 获取最新版本号
     * @param $system
     * @return array
     */
    public function newest($system)
    {
        $system = $system>0 ? "ios":"android";
        $row = DB::table($this->version)->where("system",$system)->orderBy("version_id","desc")->first();
        if(empty($row))
            $this->failed("版本不存在");

        $data = [
            "version_info"  => $row,
        ];

        return $data;
    }






}
<?php

/**
 *  后台版本资源
 */
namespace App\Repositories\Admin;

use Illuminate\Support\Facades\DB;

class VersionRepository
{
    use \App\Helpers\ApiResponse;

    private $version;
    private $version_log;

    private $base_url;//图片地址

    //初始化需要用到的表名称
    public function __construct()
    {
        $this->version                  = config('db_table.version');
        $this->version_log              = config('db_table.version_log');
        $this->base_url                 = config('oss.base_url');
    }


    /**
     * 列表
     */
    public function lists($params)
    {
        $where      = [];
        $page       = !empty($params['page'])       ? (int)$params['page']:1;
        $page_nums  = !empty($params['page_nums'])  ? (int)$params['page_nums']:10;
        $offset     = ($page-1) * $page_nums;

        $field  = !empty($params["field"]) ? $params["field"]:"create_time";
        $sort   = !empty($params["sort"]) ? $params["sort"]:"desc";

        $count  = DB::table($this->version)->where($where)->count();
        if($count == 0)
            return ["list"=>[],"count"=>0];

        $data["count"] = $count;

        $list = DB::table($this->version)
            ->where($where)
            ->orderby($field,$sort)
            ->offset($offset)
            ->limit($page_nums)
            ->get();

        $data["list"] = $list;

        return $data;
    }


    /**
     * 添加
     * @param $params
     * @return int
     */
    public function add($params)
    {
        $data = [
            "system"        => $params["system"],
            "version"       => $params["version"],
            "down_url"      => $params["down_url"],
            "create_time"   => date("Y-m-d H:i:s")
        ];
        isset($params["content"]) && $data["content"] = $params["content"];

        $versionId = DB::table($this->version)->insertGetId($data);

        return $versionId;
    }


    /**
     * 修改
     * @param $params
     * @param $versionId
     * @return bool
     */
    public function edit($params,$versionId)
    {
        $cRow = DB::table($this->version)->where("version_id",$versionId)->first();
        if(empty($cRow))
            $this->failed("访问出错");

        $data = [];
        isset($params["system"]) && $data["system"] = $params["system"];
        isset($params["version"]) && $data["version"] = $params["version"];
        isset($params["down_url"]) && $data["down_url"] = $params["down_url"];

        if(empty($data))
            return true;

        $bool = DB::table($this->version)->where("version_id",$versionId)->update($data);

        return $bool;
    }
}
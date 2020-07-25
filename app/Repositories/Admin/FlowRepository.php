<?php

/**
 *  后台流程资源
 */
namespace App\Repositories\Admin;


use Illuminate\Support\Facades\DB;

class FlowRepository
{
    use \App\Repositories\BaseRepository;
    use \App\Helpers\ApiResponse;

    //客户表
    private $customer;
    private $flow;
    private $flow_node;
    private $user;

    private $base_url;//图片地址

    //初始化需要用到的表名称
    public function __construct()
    {
        $this->flow                     = config('db_table.flow_table');
        $this->flow_node                = config('db_table.flow_node');
        $this->user                     = config('db_table.user_table');
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

        $count  = DB::table($this->flow)->where($where)->count();
        if($count == 0)
            return ["list"=>[],"count"=>0];

        $data["count"] = $count;

        $list = DB::table($this->flow)
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
     */
    public function add($params)
    {
        $data = [
            "flow_name"     => $params["flow_name"],
            "flow_desc"     => $params["flow_desc"],
        ];

        //节点不能添加相同的名称
        $row = DB::table($this->flow)->where("flow_name",$data["flow_name"])->first();
        if(!empty($row))
            $this->failed("该流程已存在");

        $clueId = DB::table($this->flow)->insertGetId($data);

        return $clueId;
    }


    /**
     * 编辑
     */
    public function edit($params,$flowId)
    {
        $cRow = DB::table($this->flow)->where("flow_id",$flowId)->first();
        if(empty($cRow))
            $this->failed("访问出错");

        $data = [];
        isset($params["flow_name"]) && $data["flow_name"] = $params["flow_name"];
        isset($params["flow_desc"]) && $data["flow_desc"] = $params["flow_desc"];

        if(empty($data))
            return true;

        $bool = DB::table($this->flow)->where("flow_id",$flowId)->update($data);

        return $bool;
    }

}
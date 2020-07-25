<?php

/**
 *  后台流程节点资源
 */
namespace App\Repositories\Admin;


use Illuminate\Support\Facades\DB;

class FlowNodeRepository
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

        $field  = !empty($params["field"]) ? $params["field"]:"sort";
        $sort   = !empty($params["sort"]) ? $params["sort"]:"asc";

        $count  = DB::table($this->flow_node." as a")->where($where)->count();
        if($count == 0)
            return ["list"=>[],"count"=>0];

        $data["count"] = $count;

        $list = DB::table($this->flow_node." as a")
            ->leftJoin($this->flow." as b","a.flow_id","=","b.flow_id")
            ->leftJoin($this->user." as c","a.uid","=","c.uid")
            ->where($where)
            ->orderby("a.".$field,$sort)
            ->offset($offset)
            ->limit($page_nums)
            ->get(["a.*","b.*","c.username"]);

        $data["list"] = $list;

        return $data;
    }


    /**
     * 添加
     */
    public function add($params)
    {
        $data = [
            "flow_id"       => $params["flow_id"],
            "node_name"     => $params["node_name"],
        ];

        isset($params["uid"]) && $data["uid"] = $params["uid"];
        isset($params["remark"]) && $data["remark"] = $params["remark"];

        //节点不能添加相同的名称
        $row = DB::table($this->flow_node)->where("node_name",$data["node_name"])->first();
        if(!empty($row))
            $this->failed("该节点已存在");

        $nodeId = DB::table($this->flow_node)->insertGetId($data);

        return $nodeId;
    }


    /**
     * 编辑
     */
    public function edit($params,$nodeId)
    {
        $cRow = DB::table($this->flow_node)->where("node_id",$nodeId)->first();
        if(empty($cRow))
            $this->failed("访问出错");

        $data = [];

        isset($params["flow_id"]) && $data["flow_id"] = $params["flow_id"];
        isset($params["node_name"]) && $data["node_name"] = $params["node_name"];
        isset($params["uid"]) && $data["uid"] = $params["uid"];
        isset($params["remark"])    && $data["remark"] = $params["remark"];

        if(empty($data))
            return true;

        $bool = DB::table($this->flow_node)->where("node_id",$nodeId)->update($data);

        return $bool;
    }

}
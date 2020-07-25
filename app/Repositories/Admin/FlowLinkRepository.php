<?php

/**
 *  后台流程线资源
 */
namespace App\Repositories\Admin;


use Illuminate\Support\Facades\DB;

class FlowLinkRepository
{
    use \App\Repositories\BaseRepository;
    use \App\Helpers\ApiResponse;

    private $flow;
    private $flow_node;
    private $flow_link;
    private $user;

    //初始化需要用到的表名称
    public function __construct()
    {
        $this->flow                     = config('db_table.flow_table');
        $this->flow_node                = config('db_table.flow_node');
        $this->user                     = config('db_table.user_table');
        $this->flow_link                = config('db_table.flow_link');
    }


    /**
     * 列表
     */
    public function lists()
    {
        $list = DB::table($this->flow_link." as a")
            ->leftJoin($this->flow." as b","a.flow_id","=","b.flow_id")
            ->leftJoin($this->flow_node." as c","a.link_pre_node","=","c.node_id")
            ->leftJoin($this->flow_node." as d","a.link_next_node","=","d.node_id")
            ->get();

        return $list;
    }


    /**
     * 添加
     */
    public function add($params)
    {
        $data = [
            "flow_id"        => $params["flow_id"],
            "link_name"      => $params["link_name"],
            "link_pre_node"  => $params["link_pre_node"],
            "link_next_node" => $params["link_next_node"],
        ];

        //流程线不能添加相同的名称
        $row = DB::table($this->flow_link)->where("link_name",$data["link_name"])->first();
        if(!empty($row))
            $this->failed("该流程线已存在");

        $linkId = DB::table($this->flow_link)->insertGetId($data);

        return $linkId;
    }


    /**
     * 编辑
     */
    public function edit($params,$linkId)
    {
        $cRow = DB::table($this->flow_link)->where("link_id",$linkId)->first();
        if(empty($cRow))
            $this->failed("访问出错");

        $data = [];
        isset($params["node_name"]) && $data["node_name"] = $params["node_name"];
        isset($params["node_role"]) && $data["node_role"] = $params["node_role"];
        isset($params["remark"])    && $data["remark"] = $params["remark"];

        if(empty($data))
            return true;

        $bool = DB::table($this->flow_link)->where("link_id",$linkId)->update($data);

        return $bool;
    }

}
<?php

/**
 *  后台部门资源
 */
namespace App\Repositories\Admin;


use Illuminate\Support\Facades\DB;

class DepartRepository
{
    use \App\Helpers\ApiResponse;

    private $depart_table;
    private $user;

    //初始化需要用到的表名称
    public function __construct()
    {
        $this->depart_table         = config('db_table.depart_table');
        $this->user                 = config('db_table.user_table');
    }


    /**
     * 部门列表
     * @param $params
     * @return mixed
     */
    public function lists($params)
    {
        $where      = [];
        $page       = !empty($params['page'])       ? (int)$params['page']:1;
        $page_nums  = !empty($params['page_nums'])  ? (int)$params['page_nums']:20;
        $offset     = ($page-1) * $page_nums;

        $count  = DB::table($this->depart_table)->where($where)->count();
        if(empty($count))
            return ["count"=>0,"list"=>[]];

        $data["count"] = $count;
        $list   = DB::table($this->depart_table)
            ->where($where)
            ->orderby('depart_id',"desc")
            ->offset($offset)
            ->limit($page_nums)
            ->get();

        $data["list"] = $list;

        return $data;
    }


    /**
     * 添加
     */
    public function add($params,$parent_id,$admin_uid)
    {
        $time = date("Y-m-d H:i:s");

        //如果手机号已经存在  则不能再次添加
        $row = DB::table($this->depart_table)->where("depart_name",$params["depart_name"])->first();
        if(!empty($row))
            $this->failed("该部门已存在");


        $data = [
            "depart_name"   => $params["depart_name"],
            "parent_id"     => $parent_id,
            "sort"          => isset($params["sort"]) ? $params["sort"]:0,
            //"operator_uid"  => $admin_uid,
            //"operator_time" => $time,
            "create_time"   => $time
        ];


        $depart_id = DB::table($this->depart_table)->insertGetId($data);
        if($depart_id>0)
        {
            if(empty($parent_id))//如果是一级节点，则自动更新
            {
                DB::table($this->depart_table)->where("depart_id",$depart_id)->update(["level"=>$depart_id]);
            }
            else
            {
                //查找该部门的父级节点
                $row = DB::table($this->depart_table)->where("depart_id",$params["parent_id"])->first();
                if(!empty($row))
                    DB::table($this->depart_table)->where("depart_id",$depart_id)->update(["level"=>$row->level."/".$depart_id]);
            }

        }

        return $depart_id;
    }


    /**
     * 修改部门
     */
    public function edit($params,$depart_id,$admin_uid)
    {
        $time = date("Y-m-d H:i:s");
        $data = [
            "depart_name"   => $params["depart_name"],
            "parent_id"     => $params["parent_id"],
            "sort"          => isset($params["sort"]) ? $params["sort"]:0,
            //"operator_uid"  => $admin_uid,
            //"operator_time" => $time,
        ];

        $bool = DB::table($this->depart_table)->where("depart_id",$depart_id)->update($data);
        if(!empty($bool))
        {
            //查找该部门的父级节点
            $row = DB::table($this->depart_table)->where("depart_id",$params["parent_id"])->first();
            if(!empty($row))
                DB::table($this->depart_table)->where("depart_id",$depart_id)->update(["level"=>$row->level."/".$depart_id]);
        }

        return $bool;
    }

    /**
     * 删除部门
     * @param $departId
     * @param $admin_uid
     * @return bool
     */
    public function del($departId,$admin_uid)
    {
        $row  = DB::table($this->depart_table)->where("depart_id",$departId)->first();
        if(empty($row))
            $this->failed("非法操作");

        $uRow = DB::table($this->user)->where("depart_id",$departId)->first();
        if(!empty($uRow))
            $this->failed("请先解除该部门员工，再删除部门");

        $num  = DB::table($this->depart_table)->where("depart_id",$departId)->delete();

        return $num ? true:false;
    }
}
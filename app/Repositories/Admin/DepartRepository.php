<?php

/**
 *  后台部门资源
 */
namespace App\Repositories\Admin;


use Illuminate\Support\Facades\DB;

class DepartRepository
{
    use \App\Repositories\BaseRepository;
    use \App\Helpers\ApiResponse;

    private $depart_table;
    private $depart_admin;
    private $user;
    private $crm_employee;

    //初始化需要用到的表名称
    public function __construct()
    {
        $this->depart_table         = config('db_table.depart_table');
        $this->depart_admin         = config('db_table.crm_org_admin');
        $this->user                 = config('db_table.user_table');
        $this->crm_employee         = config('db_table.crm_employee');
    }


    /**
     * 获取事业部列表
     * @param null $pid
     * @return \Illuminate\Support\Collection
     */
    public function lists()
    {
        $list = DB::table($this->depart_table)->get();

        return $list;
    }


    /**
     * 添加
     */
    public function add($params,$admin_uid)
    {
        $time = date("Y-m-d H:i:s");

        //如果手机号已经存在  则不能再次添加
        $row = DB::table($this->depart_table)->where("depart_name",$params["depart_name"])->first();
        if(!empty($row))
            $this->failed("该部门已存在");


        $data = [
            "depart_name"   => $params["depart_name"],
            "parent_id"     => $params["parent_id"],
            "sort"          => $params["sort"],
            "operator_uid"  => $admin_uid,
            "operator_time" => $time,
            "create_time"   => $time
        ];


        $depart_id = DB::table($this->depart_table)->insertGetId($data);
        if($depart_id>0)
        {
            //查找该部门的父级节点
            $row = DB::table($this->depart_table)->where("depart_id",$params["parent_id"])->first();
            if(!empty($row))
                DB::table($this->depart_table)->where("depart_id",$depart_id)->update(["level"=>$row->level."/".$depart_id]);
        }

        return $depart_id;
    }


    /**
     * 修改部门1
     */
    public function edit($params,$depart_id,$admin_uid)
    {
        $time = date("Y-m-d H:i:s");
        $data = [
            "depart_name"   => $params["depart_name"],
            "parent_id"     => $params["parent_id"],
            "sort"          => $params["sort"],
            "operator_uid"  => $admin_uid,
            "operator_time" => $time,
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
     * 部门管理员列表
     */
    public function adminList($params)
    {
        $where      = [];
        $page       = !empty($params['page'])       ? (int)$params['page']:1;
        $page_nums  = !empty($params['page_nums'])  ? (int)$params['page_nums']:10;
        $offset     = ($page-1) * $page_nums;


        $count  = DB::table($this->depart_admin)->where($where)->count();
        if($count == 0)
            return ["list"=>[],"count"=>0];

        $data["count"] = $count;

        $list = DB::table($this->depart_admin." as a")
            ->leftJoin($this->user." as b","a.uid","=","b.uid")
            //->leftJoin($this->crm_employee." as c","c.user_id","=","a.crm_user_id")
            ->where($where)
            ->offset($offset)
            ->limit($page_nums)
            ->get(["a.*","b.username"]);

        $data["list"] = $list;

        return $data;

    }

    /**
     * 添加管理员
     */
    public function admin_add($params)
    {
        $time = date("Y-m-d H:i:s");

        //首先查询该用户是否存在
        $row = DB::table($this->user)->where("uid",$params["uid"])->first();
        if(empty($row))
            $this->failed("访问出错");
        //再次查询该用户是否已经是部门管理员
        $has = DB::table($this->depart_admin)->where("uid",$params["uid"])->first();
        if(!empty($has))
            $this->failed("他已经是部门管理员了");

        $data = [
            "uid"           => $params["uid"],
            "crm_user_id"   => $row->crm_user_id,
            "org_id"        => $row->crm_org_id,
            "is_org_admin"  => 1,
            "create_time"   => $time
        ];


        $org_admin_id = DB::table($this->depart_admin)->insertGetId($data);


        return $org_admin_id;
    }


    /**
     * 修改管理员
     */
    public function admin_edit($params,$org_admin_id)
    {
        //首先查询该用户是否存在
        $row = DB::table($this->user)->where("uid",$params["uid"])->first();
        if(empty($row))
            $this->failed("访问出错");

        $data = [
            "uid"           => $params["uid"],
            "crm_user_id"   => $row->crm_user_id,
            "org_id"        => $row->org_id,
        ];

        $bool = DB::table($this->depart_admin)->where("id",$org_admin_id)->update($data);

        return $bool;
    }

    /**
     * 删除管理员
     */
    public function admin_del($org_admin_id)
    {
        $bool = DB::table($this->depart_admin)->where("id",$org_admin_id)->delete();
        return $bool;
    }
}
<?php

/**
 *  后台角色资源
 */
namespace App\Repositories\Admin;


use Illuminate\Support\Facades\DB;

class RoleRepository
{
    use \App\Repositories\BaseRepository;
    use \App\Helpers\ApiResponse;

    private $role_table;
    private $user;

    //初始化需要用到的表名称
    public function __construct()
    {
        $this->role_table       = config('db_table.role_table');
        $this->user             = config('db_table.user_table');
    }


    /**
     * 获取事业部列表
     * @param $params
     * @return mixed
     */
    public function lists($params)
    {
        $where      = [];
        $page       = !empty($params['page'])       ? (int)$params['page']:1;
        $page_nums  = !empty($params['page_nums'])  ? (int)$params['page_nums']:20;
        $offset     = ($page-1) * $page_nums;

        $count  = DB::table($this->role_table)->where($where)->count();
        if(empty($count))
            return ["count"=>0,"list"=>[]];

        $data["count"] = $count;
        $list = DB::table($this->role_table)
            ->where($where)
            ->orderby('role_id',"desc")
            ->offset($offset)
            ->limit($page_nums)
            ->get();

        $data["list"] = $list;

        return $data;
    }


    /**
     * 添加
     */
    public function add($params,$admin_uid)
    {
        $time = date("Y-m-d H:i:s");

        //如果手机号已经存在  则不能再次添加
        $row = DB::table($this->role_table)->where("role_name",$params["role_name"])->first();
        if(!empty($row))
            $this->failed("该角色已存在");


        $data = [
            "role_name"     => $params["role_name"],
            "remark"        => $params["remark"],
            //"perm_lists"    => json_decode($params["perm_lists"]),
            //"operator_uid"  => $admin_uid,
            //"operator_time" => $time,
            "create_time"   => $time
        ];

        $role_id = DB::table($this->role_table)->insertGetId($data);

        return $role_id;
    }


    /**
     * 修改
     */
    public function edit($params,$role_id,$admin_uid)
    {
        $data = [
            "role_name"     => $params["role_name"],
            "remark"        => $params["remark"],
        ];

        $bool = DB::table($this->role_table)->where("role_id",$role_id)->update($data);

        return $bool;
    }



    /**
     * 删除角色
     * @param $roleId
     * @param $admin_uid
     * @return bool
     */
    public function del($roleId,$admin_uid)
    {
        $row  = DB::table($this->role_table)->where("role_id",$roleId)->first();
        if(empty($row))
            $this->failed("非法操作");

        $uRow = DB::table($this->user)->where("role_id",$roleId)->first();
        if(!empty($uRow))
            $this->failed("请先解除该角色员工，再删除角色");

        $num  = DB::table($this->role_table)->where("role_id",$roleId)->delete();

        return $num ? true:false;
    }



    /**
     * 给角色添加权限节点
     */
    public function addPerms()
    {

    }
}
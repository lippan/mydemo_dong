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

    //初始化需要用到的表名称
    public function __construct()
    {
        $this->role_table       = config('db_table.role_table');
    }


    /**
     * 获取事业部列表
     * @param null $pid
     * @return \Illuminate\Support\Collection
     */
    public function lists()
    {
        $list = DB::table($this->role_table)->get();

        return $list;
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
            "perm_lists"    => json_decode($params["perm_lists"]),
            "operator_uid"  => $admin_uid,
            "operator_time" => $time,
            "create_time"   => $time
        ];

        $uid = DB::table($this->role_table)->insertGetId($data);

        return $uid;
    }


    /**
     * 修改
     */
    public function edit($params,$uid,$admin_uid)
    {
        $time = date("Y-m-d H:i:s");
        $data = [
            "role_name"   => $params["role_name"],
            "operator_uid"  => $admin_uid,
            "operator_time" => $time,
        ];

        $bool = DB::table($this->role_table)->where("uid",$uid)->update($data);

        return $bool;
    }


    /**
     * 给角色添加权限节点
     */
    public function addPerms()
    {

    }
}
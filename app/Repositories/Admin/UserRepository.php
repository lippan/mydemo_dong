<?php

/**
 *  后台用户资源
 */
namespace App\Repositories\Admin;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserRepository
{
    use \App\Repositories\BaseRepository;
    use \App\Helpers\ApiResponse;

    private $user_table;

    //初始化需要用到的表名称
    public function __construct()
    {
        $this->user_table       = config('db_table.user_table');
    }


    /**
     * 获取列表
     */
    public function lists($params)
    {
        $where      = [];
        $page       = !empty($params['page'])       ? (int)$params['page']:1;
        $page_nums  = !empty($params['page_nums'])  ? (int)$params['page_nums']:10;
        $offset     = ($page-1) * $page_nums;

        $field  = !empty($params["field"]) ? $params["field"]:"create_time";
        $sort   = !empty($params["sort"]) ? $params["sort"]:"desc";

        $count  = DB::table($this->user_table)->where($where)->count();
        if($count == 0)
            return ["list"=>[],"count"=>0];

        $data["count"] = $count;

        $list = DB::table($this->user_table)
            ->where($where)
            ->orderby($field,$sort)
            ->offset($offset)
            ->limit($page_nums)
            ->get(["uid","username","mobile","status","create_time"]);

        $data["list"] = $list;

        return $data;
    }


    /**
     * 添加用户
     */
    public function add($params,$admin_uid)
    {
        $time = date("Y-m-d H:i:s");
        $slat = rand_str(6);

        //那手机号获取一下crm_id TODO 是否获取crmid
        $crm_id = isset($params["crm_user_id"]) ? $params["crm_user_id"]:0;

        //如果手机号已经存在  则不能再次添加
        $row = DB::table($this->user_table)->where("mobile",$params["mobile"])->first();
        if(!empty($row))
            $this->failed("该用户已存在");

        $params['password'] = isset($params['password']) ? $params['password']:"123456";
        $data = [
            "mobile"    => $params["mobile"],
            "username"  => $params["username"],
            "slat"      => $slat,
            "password"  => Hash::make($params['password'].$slat),
            "depart_id" => $params["depart_id"],
            "is_admin"  => 0,
            "role_id"   => $params["role_id"],
            "is_depart_admin"   => $params["is_depart_admin"],//是否为部门管理员
            "crm_user_id"       => $crm_id,
            "operator_uid"      => $admin_uid,
            "operator_time"     => $time,
            "create_time"       => $time
        ];

        isset($params["mail"]) && $data["mail"] = $params["mail"];

        $uid = DB::table($this->user_table)->insertGetId($data);

        return $uid;
    }

    /**
     * 修改用户
     */
    public function edit($params,$uid,$admin_uid)
    {
        $time = date("Y-m-d H:i:s");
        $data = [
            "mobile"    => $params["mobile"],
            "username"  => $params["username"],
            "status"    => $params["status"],
            "operator_uid"      => $admin_uid,
            "operator_time"     => $time,
        ];

        isset($params["depart_id"]) && $data["depart_id"] = $params["depart_id"];
        isset($params["role_id"]) && $data["role_id"] = $params["role_id"];
        isset($params["is_depart_admin"]) && $data["is_depart_admin"] = $params["is_depart_admin"];
        isset($params["mail"]) && $data["mail"] = $params["mail"];

        $bool = DB::table($this->user_table)->where("uid",$uid)->update($data);

        return $bool;
    }

    /**
     * 个人中心
     */
    public function my()
    {

    }


    /**
     * 修改个人信息
     */
    public function modify($params,$type="account")
    {

    }


}
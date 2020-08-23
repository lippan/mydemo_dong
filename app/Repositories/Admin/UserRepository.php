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
    private $depart_table;
    private $user_month_target;

    //初始化需要用到的表名称
    public function __construct()
    {
        $this->user_table         = config('db_table.user_table');
        $this->depart_table       = config('db_table.depart_table');
        $this->user_month_target  = config('db_table.user_month_target');
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


        if(isset($params["status"]) && !empty($params["status"]))
            $where[] = ["a.status","=",1];

        if(isset($params["keywords"]) && !empty($params["keywords"]))
            $where[] = ["a.username","like","%{$params["keywords"]}%"];

        $count  = DB::table($this->user_table." as a")->where($where)->count();
        if($count == 0)
            return ["list"=>[],"count"=>0];

        $data["count"] = $count;

        $list = DB::table($this->user_table." as a")
            ->leftJoin($this->depart_table." as b","a.depart_id","=","b.depart_id")
            ->where($where)
            ->orderby("a.".$field,$sort)
            ->offset($offset)
            ->limit($page_nums)
            ->get(["a.uid","a.username","a.mobile","a.role_id","a.depart_id","last_login_time","last_login_ip","is_depart_admin","a.status","a.create_time","b.depart_name"]);

        $data["list"]  = $list;
        $data["month"] = date("Y-m");

        if(!$list->isEmpty())
        {
            foreach($list as &$val)
            {
                $row = DB::table($this->user_month_target)->where("uid",$val->uid)->where("month",$data["month"])->first();
                if(empty($row))
                    $val->task_money = 0;
                else
                    $val->task_money = $row->task_money;
            }
        }

        return $data;
    }


    /**
     * 添加用户
     */
    public function add($params,$admin_uid)
    {
        $time = date("Y-m-d H:i:s");
        $slat = rand_str(6);

        //如果手机号已经存在  则不能再次添加
        $row = DB::table($this->user_table)->where("mobile",$params["mobile"])->first();
        if(!empty($row))
            $this->failed("该用户已存在");

        //查询该用户的组织架构
        $drow = DB::table($this->depart_table)->where("depart_id",$params["depart_id"])->first();
        if(empty($drow))
            $this->failed("部门有误");

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

            //"operator_uid"      => $admin_uid,
            //"operator_time"     => $time,
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

        //如果封禁了用户  那么流程中的审核节点也将被封禁掉  TODO 若存在待审核的  怎么处理？
        if($bool && empty($params["status"]))
        {

        }

        return $bool;
    }

    /**
     * 我的用户可分配列表
     */
    public function myAllotUserList($isAdmin,$isDepartAdmin,$level,$uid)
    {
        if($isAdmin)
        {
            $list = DB::table($this->user_table)->where("status",1)->get();
            return $list;
        }

        $userArr = getUserAuth($uid,$isDepartAdmin,$level);

        $list = DB::table($this->user_table)->whereIn("uid",$userArr)->where("status",1)->get();

        return $list;
    }


    /**
     * 设置月度销售额目标
     */
    public function setMonthTarget($toUid,$taskMoney)
    {
        $month = date("Y-m");
        $where = [
            "uid"       => $toUid,
            "month"     => $month
        ];
        $row   = DB::table($this->user_month_target)->where($where)->first();

        if(!empty($row))
        {
            $bool = DB::table($this->user_month_target)->where("id",$row->id)->update(["task_money"=>$taskMoney]);

            return $bool;
        }

        $where["task_money"]    = $taskMoney;
        $where["create_time"]   = date("Y-m-d H:i:s");

        $bool = DB::table($this->user_month_target)->insert($where);

        return $bool;
    }


    /**
     * 绑定openid
     * @param $openid
     * @param $mobile
     * @return bool
     */
    public function bindOpenId($openid,$mobile)
    {
        $row    = DB::table($this->user_table)->where("mobile",$mobile)->first();
        if(empty($row))
            $this->failed("非法操作");
        if($row->openid)
            return true;

        $bool   = DB::table($this->user_table)->where("uid",$row->uid)->update(["openid"=>$openid]);

        return $bool;
    }


}
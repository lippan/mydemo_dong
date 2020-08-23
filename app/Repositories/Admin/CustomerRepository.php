<?php

/**
 *  后台-客户资源
 */
namespace App\Repositories\Admin;

use Illuminate\Support\Facades\DB;

class CustomerRepository
{
    use \App\Helpers\ApiResponse;

    //客户表
    private $business;
    private $business_log;
    private $user;

    //初始化需要用到的表名称
    public function __construct()
    {
        $this->user                     = config('db_table.user_table');
        $this->business                 = config('db_table.customer_table');
        $this->business_log             = config('db_table.business_log');
    }


    /**
     * 列表
     */
    public function lists($params,$uid,$isAdmin,$isDepartAdmin,$level)
    {
        $page       = !empty($params['page'])       ? (int)$params['page']:1;
        $page_nums  = !empty($params['page_nums'])  ? (int)$params['page_nums']:10;
        $offset     = ($page-1) * $page_nums;

        if($isAdmin == 1)
        {
            $list = $this->adminList($params,$offset,$page_nums);

            return $list;
        }

        $list = $this->normalList($params,$uid,$isDepartAdmin,$level,$offset,$page_nums);

        return $list;
    }


    /**
     * 商机详情
     */
    public function info($customerId)
    {

    }


    /**
     * 添加
     * @param $params
     * @return int
     */
    public function add($params,$uid)
    {
        $data = [
            "uid"           => $uid,//归属员工id，谁添加的 归属谁
            "type"          => $params["type"],//商机类型
            "level"         => $params["level"],//组织架构分级
            "name"          => $params["name"],//客户名称
            "create_time"   => date("Y-m-d H:i:s")
        ];

        isset($params["mobile"]) &&         $data["mobile"]         = $params["mobile"];//客户手机号
        isset($params["industry"]) &&       $data["industry"]       = $params["industry"];//客户行业
        isset($params["regist_area"]) &&    $data["regist_area"]    = $params["regist_area"];//注册区域
        isset($params["qq_number"]) &&      $data["qq_number"]      = $params["qq_number"];//客户QQ

        isset($params["wx_number"]) &&      $data["wx_number"]      = $params["wx_number"];//客户wx
        isset($params["mail_number"]) &&    $data["mail_number"]    = $params["mail_number"];//客户邮箱
        isset($params["tax"]) &&            $data["tax"]            = $params["tax"];//税种

        isset($params["is_special_industry"]) && $data["is_special_industry"] = $params["is_special_industry"];//是否特种行业

        isset($params["company_type"]) &&   $data["company_type"]   = $params["company_type"];//企业类型
        isset($params["source"]) &&         $data["source"]         = $params["source"];//客户来源
        isset($params["first_price"]) &&    $data["first_price"]    = $params["first_price"];//首次报价
        isset($params["remark"]) &&         $data["remark"]         = $params["remark"];//客户备注
        isset($params["first_contact"]) &&  $data["first_contact"]  = $params["first_contact"];//首次交谈记录

        if(isset($params["mobile"]) && !empty($params["mobile"]))
        {
            $row = DB::table($this->business)->where("mobile",$params["mobile"])->first();
            if(empty($row))
                $this->failed("该手机号已存在");
        }

        $customerId = DB::table($this->business)->insert($data);

        return $customerId;
    }


    /**
     * 编辑
     * @param $params
     * @param $customerId
     * @return bool
     */
    public function edit($params,$customerId,$uid)
    {
        $row = DB::table($this->business)->where("customer_id",$customerId)->first();
        if(empty($row))
            $this->failed("该手机号已存在");

        //验证该条记录是否为他本人的
        if($row->uid != $uid)
            $this->failed("您不能修改别人的商机");

        $data = [];
        isset($params["mobile"]) &&         $data["mobile"]         = $params["mobile"];//客户手机号
        isset($params["industry"]) &&       $data["industry"]       = $params["industry"];//客户行业
        isset($params["regist_area"]) &&    $data["regist_area"]    = $params["regist_area"];//注册区域
        isset($params["qq_number"]) &&      $data["qq_number"]      = $params["qq_number"];//客户QQ

        isset($params["wx_number"]) &&      $data["wx_number"]      = $params["wx_number"];//客户wx
        isset($params["mail_number"]) &&    $data["mail_number"]    = $params["mail_number"];//客户邮箱
        isset($params["tax"]) &&            $data["tax"]            = $params["tax"];//税种

        isset($params["is_special_industry"]) && $data["is_special_industry"] = $params["is_special_industry"];//是否特种行业

        isset($params["company_type"]) &&   $data["company_type"]   = $params["company_type"];//企业类型
        isset($params["source"]) &&         $data["source"]         = $params["source"];//客户来源
        isset($params["first_price"]) &&    $data["first_price"]    = $params["first_price"];//首次报价
        isset($params["remark"]) &&         $data["remark"]         = $params["remark"];//客户备注
        isset($params["first_contact"]) &&  $data["first_contact"]  = $params["first_contact"];//首次交谈记录


        if(empty($data))
            return true;

        $bool = DB::table($this->business)->where("customer_id",$customerId)->update($data);

        return $bool;
    }


    /**
     * 分配
     * @param $adminUid
     * @param $customerId
     * @param $toUid
     * @return bool;
     */
    public function allot($isAdmin,$isDepartAdmin,$uid,$level,$customerId,$toUid)
    {

        $info = DB::table($this->business)->where("customer_id",$customerId)->first();
        if(empty($info))
            $this->failed("访问出错");

        //查询该线索是否成交或者完成   dealed  finished
        if($info->status == "dealed" || $info->status == "finished")
            $this->failed("该商机已成交或已完成");

        //首先查询该条线索是不是 这个管理员的
        if(empty($isAdmin))
        {
            $uidArr = getUserAuth($uid,$isDepartAdmin,$level);

            $row = DB::table($this->business)->whereIn("uid",$uidArr)->where("customer_id",$customerId)->first();
            if(empty($row))
                $this->failed("您没有权限");

            //查询要分配的这个人是否为他的部下
            if(!in_array($toUid,$uidArr))
                $this->failed("对方不在您的部门");
        }

        //查询该用户是否封禁
        $urow = DB::table($this->user)->where("uid",$toUid)->first();
        if(empty($urow))
            $this->failed("非法操作");

        if(empty($urow->status))
            $this->failed("不能分配封禁员工");

        //此处进行事务机制

        DB::table($this->business)->where("customer_id",$customerId)->update(["uid"=>$toUid]);

        return $bool;
    }


    //超级管理员列表
    protected function adminList($params,$offset,$page_nums)
    {
        $where = [];
        $field  = !empty($params["field"]) ? $params["field"]:"create_time";
        $sort   = !empty($params["sort"]) ? $params["sort"]:"desc";

        ///isset($params["uid"]) && $where[] = ["a.uid","=",$params["uid"]];
        !empty($params["status"]) && $where[] = ["a.status","=",$params["status"]];

        if(isset($params["start_time"]) && !empty($params["start_time"]))
        {
            $where[] = ["a.create_time",">",$params["start_time"]];
        }
        if(isset($params["end_time"]) && !empty($params["end_time"]))
        {
            $where[] = ["a.create_time","<",$params["end_time"]." 23:59:59"];
        }

        if(isset($params["keywords"]) && !empty($params["keywords"]))
        {

            $list = DB::table($this->business." as a")
                ->leftJoin($this->user." as b","a.uid","=","b.uid")
                ->where($where)
                ->where(function($query)use($params){
                    $query->where('a.name',"like","%".$params["keywords"]."%")
                        //->orWhere("a.company_name","like","%".$params["keywords"]."%")
                        ->orWhere("a.mobile","like","%".$params["keywords"]."%");
                })
                ->orderby('a.'.$field,$sort)
                ->offset($offset)
                ->limit($page_nums)
                ->get(["a.*","b.username"]);

            $data["list"] = $list;
            return $data;
        }

        //获取数量
        $count = DB::table($this->business." as a")->where($where)->count();
        if(empty($count))
            return ["list"=>[],"count"=>0];

        $data["count"] = $count;

        $list = DB::table($this->business." as a")
            ->leftJoin($this->user." as b","a.uid","=","b.uid")
            ->where($where)
            ->orderby('a.'.$field,$sort)
            ->offset($offset)
            ->limit($page_nums)
            ->get(["a.*","b.username"]);

        $data["list"] = $list;

        return $data;
    }



    //普通员工--商机列表
    protected function normalList($params,$uid,$isDepartAdmin,$level,$offset,$page_nums)
    {
        $where = [];
        if(isset($params["customer_id"]))
        {
            $where[] = ["a.customer_id","=",$params["customer_id"]];
        }

        $uidArr = getUserAuth($uid,$isDepartAdmin,$level);

        if(isset($params["start_time"]) && !empty($params["start_time"]))
        {
            $where[] = ["a.create_time",">",$params["start_time"]];
        }
        if(isset($params["end_time"]) && !empty($params["end_time"]))
        {
            $where[] = ["a.create_time","<",$params["end_time"]." 23:59:59"];
        }

        //获取数量
        $count = DB::table($this->business." as a")
            ->leftJoin($this->user." as b","b.uid","=","a.uid")
            ->where($where)
            ->whereIn("a.uid",$uidArr)
            ->count();

        if(empty($count))
            return ["list"=>[],"count"=>0];

        $data["count"] = $count;

        $list = DB::table($this->business." as a")
            ->leftJoin($this->user." as b","b.uid","=","a.uid")
            ->where($where)
            ->whereIn("a.uid",$uidArr)
            ->orderby('a.create_time',"desc")
            ->offset($offset)
            ->limit($page_nums)
            ->get(["a.*","b.username"]);

        $data["list"] = $list;

        return $data;
    }


    /**
     * 导出列表
     */
    public  function  exportList($params)
    {
        $page       = !empty($params['page'])       ? (int)$params['page']:1;
        $page_nums  = !empty($params['page_nums'])  ? (int)$params['page_nums']:10000;
        $offset     = ($page-1) * $page_nums;

        $where  = [];
        $field  = !empty($params["field"]) ? $params["field"]:"create_time";
        $sort   = !empty($params["sort"]) ? $params["sort"]:"desc";

        !empty($params["status"]) && $where[] = ["a.status","=",$params["status"]];

        if(isset($params["start_time"]) && !empty($params["start_time"]))
        {
            $where[] = ["a.create_time",">",$params["start_time"]];
        }
        if(isset($params["end_time"]) && !empty($params["end_time"]))
        {
            $where[] = ["a.create_time","<",$params["end_time"]];
        }



        $list = DB::table($this->business." as a")
            ->leftJoin($this->user." as b","b.uid","=","a.uid")
            ->where($where)
            ->orderby('a.'.$field,$sort)
            ->offset($offset)
            ->limit($page_nums)
            ->get(["b.username","a.*"]);

        return $list;

    }

}
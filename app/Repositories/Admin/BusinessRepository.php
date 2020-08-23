<?php

/**
 *  后台-客户资源
 */
namespace App\Repositories\Admin;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class BusinessRepository
{
    use \App\Helpers\ApiResponse;

    //客户表
    private $business;
    private $user;

    //初始化需要用到的表名称
    public function __construct()
    {
        $this->user                     = config('db_table.user_table');
        $this->business                 = config('db_table.business_table');
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
            "source"        => $params["source"],//商机类型
            //"level"         => $params["level"],//组织架构分级
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
        isset($params["type"]) &&         $data["type"]         = $params["type"];//意向等级
        isset($params["first_price"]) &&    $data["first_price"]    = $params["first_price"];//首次报价
        isset($params["remark"]) &&         $data["remark"]         = $params["remark"];//客户备注
        isset($params["first_contact"]) &&  $data["first_contact"]  = $params["first_contact"];//首次交谈记录

        if(isset($params["mobile"]) && !empty($params["mobile"]))
        {
            $row = DB::table($this->business)->where("mobile",$params["mobile"])->first();
            if(!empty($row))
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

        $info = DB::table($this->business." as a")
            ->leftJoin($this->user." as b","a.uid","=","b.uid")
            ->where("customer_id",$customerId)
            ->first(["a.*","b.username"]);

        if(empty($info))
            $this->failed("访问出错");

        //如果人员没有做修改，则直接返回
        if($toUid == $info->uid)
            return true;

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

        $updateTime = date("Y-m-d H:i:s");

        $data = [
            "customer_id"   => $customerId,
            "old_uid"       => $info->uid,
            "new_uid"       => $toUid,
            "operate_type"  => "allot",
            "remark"        => "商机信息从---".$info->username."---转给了---".$urow->username,
            "operate_uid"   => $uid,
            "operate_remark"=> "",
            "create_time"   => $updateTime
        ];
        //此处需要事务操作
        try{
            DB::transaction(function () use($customerId,$toUid,$data,$updateTime){
                DB::table($this->business)->where("customer_id",$customerId)->update(["uid"=>$toUid,"allot_time"=>$updateTime]);
                DB::table($this->business_log)->insert($data);
            });
        }catch(QueryException $ex){
            $this->failed("分配失败:数据库更新失败");
        }

        return true;
    }


    /**
     * 确认领取
     */
    public function confirm($uid,$username,$customer_id)
    {
        $where = [
            "uid"           => $uid,
            "customer_id"   => $customer_id
        ];
        $row  = DB::table($this->business)->where($where)->first();

        if(empty($row))
            $this->failed("您不能领取别人的商机");

        if($row->status != "waiting")
            $this->failed("您已经领取过了！");

        //领取跟进了 才能领取下一条商机  TODO  (领取的商机存在未跟进的，则不能领取)
        $getNum = DB::table($this->business_log." as a")
            ->leftJoin($this->business." as b","a.customer_id","=","b.customer_id")
            ->where("b.uid",$uid)
            ->where("a.operate_type","get")
            ->count();

        $followNum = DB::table($this->business_log." as a")
            ->leftJoin($this->business." as b","a.customer_id","=","b.customer_id")
            ->where("b.uid",$uid)
            ->where("a.operate_type","follow")
            ->count();

        if($getNum>$followNum)
            $this->failed("领取失败:您还有领取后未跟进的商机");


        $updateTime = date("Y-m-d H:i:s");

        $desc = $username."---领取了商机信息";
        $data = [
            "customer_id"   => $customer_id,
            "old_uid"       => $uid,
            "new_uid"       => $uid,
            "operate_type"  => "get",
            "remark"        => $desc,
            "operate_uid"   => $uid,
            "operate_remark"=> "",
            "create_time"   => $updateTime
        ];

        $update_data = [
            "status"                => "running",
            "last_follow_time"      => $updateTime,
            "last_follow_remark"    => $desc
        ];

        //此处需要事务操作
        try{
            DB::transaction(function () use($customer_id,$data,$update_data){
                DB::table($this->business)->where("customer_id",$customer_id)->update($update_data);
                DB::table($this->business_log)->insert($data);
            });
        }catch(QueryException $ex){
            $this->failed("领取确认失败:数据库更新失败");
        }

        return true;
    }

    /**
     * 继续跟进
     * @param $uid
     * @param $customer_id
     * @param $status
     * @param $remark
     * @param $dealMoney
     * @param $next_follow_time
     * @return  bool
     */
    public function continue_follow($uid,$customer_id,$username,$status,$remark,$dealMoney,$next_follow_time = null)
    {
        //1.修改商机状态   2.新增商机跟进记录
        $row = DB::table($this->business)->where("customer_id",$customer_id)->first();
        if(empty($row))
            $this->failed("访问出错");

        if($row->status != "running")
        {
            $this->failed("跟进状态不能修改");
        }

        $arr = ["running"=>"跟进中","dealed"=>"已成交","not_dealed"=>"未成交","finished"=>"已完成","give_up","已放弃"];

        $oldStatus = $arr[$row->status];
        $nowStatus = $arr[$status];

        $updateTime = date("Y-m-d H:i:s");


        $desc = $username."---跟进了商机[状态:{$oldStatus}=>$nowStatus]:".$remark;
        $data = [
            "customer_id"   => $customer_id,
            "old_uid"       => $uid,
            "new_uid"       => $uid,
            "operate_type"  => "follow",
            "remark"        => $desc,
            "operate_uid"   => $uid,
            "operate_remark"=> "",
            "create_time"   => $updateTime
        ];


        $update_data = [
            "status"                => $status,
            "last_follow_time"      => $updateTime,
            "last_follow_remark"    => $desc
        ];
        if($status == "dealed")
            $update_data["deal_money"]  = $dealMoney;

        isset($next_follow_time) && $update_data["next_follow_time"] = $next_follow_time;

        //此处需要事务操作
        try{
            DB::transaction(function () use($uid,$customer_id,$data,$update_data,$status,$dealMoney){
                DB::table($this->business)->where("customer_id",$customer_id)->update($update_data);
                DB::table($this->business_log)->insert($data);
                if($status == "dealed")//如果是已成交的，那么需要记录当月的销售额
                {
                    $where = [
                        "uid"       => $uid,
                        "month"     => date("Y-m")
                    ];
                    DB::table($this->user_month_target)->where($where)->increment("real_money",$dealMoney);
                }
            });
        }catch(QueryException $ex){
            $this->failed("领取确认失败:数据库更新失败");
        }

        return true;
    }

    /**
     * 跟进记录
     * @param $uid
     * @param $customer_id
     * @return object
     */
    public function follow_log($uid,$customer_id)
    {
        $list = DB::table($this->business_log." as a")
            ->leftJoin($this->business." as b","a.customer_id","=","b.customer_id")
            ->where("a.customer_id",$customer_id)
            ->get(["a.*","b.name"]);

        return $list;
    }


    //超级管理员列表
    protected function adminList($params,$offset,$page_nums)
    {$where = [];
        //$where[]  = ["a.uid",">",0];
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


            $count = DB::table($this->business." as a")
                ->leftJoin($this->user." as b","a.uid","=","b.uid")
                ->where($where)
                ->where(function($query)use($params){
                    $query->where('b.username',"like","%".$params["keywords"]."%")
                        //->orWhere("a.username","like","%".$params["keywords"]."%")
                        ->orWhere("a.mobile","like","%".$params["keywords"]."%");
                })
                ->count();
            if(empty($count))
                return ["list"=>[],"count"=>0];

            $data["count"] = $count;

            $list = DB::table($this->business." as a")
                ->leftJoin($this->user." as b","a.uid","=","b.uid")
                ->where($where)
                ->where(function($query)use($params){
                    $query->where('b.username',"like","%".$params["keywords"]."%")
                        //->orWhere("a.username","like","%".$params["keywords"]."%")
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
        $where[]  = ["a.uid",">",0];
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
        if(isset($params["type"]) && !empty($params["type"]))
        {
            $where[] = ["a.type","=",$params["type"]];
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
     * 获取商机类型
     * @return \Illuminate\Support\Collection
     */
    public function getType()
    {
        $list = DB::table($this->business_type)->get();

        $arr = config("app.purpose");

        $data["source"] = $list;
        $data["purpose"] = $arr;
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
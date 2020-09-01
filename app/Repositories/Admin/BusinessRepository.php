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
    private $business_log;
    private $user;
    private $depart;

    //初始化需要用到的表名称
    public function __construct()
    {
        $this->user                     = config('db_table.user_table');
        $this->business                 = config('db_table.business_table');
        $this->business_log             = config('db_table.business_log');
        $this->depart                   = config('db_table.depart_table');
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
     * 添加
     * @param $params
     * @return int
     */
    public function add($params,$uid,$departId)
    {
        $data = [
            "uid"           => $uid,//归属员工id，谁添加的 归属谁
            "source"        => "地推",//商机类型
            "name"          => $params["name"],//客户名称
            "mobile"        => $params["mobile"],//客户名称
            "status"        => $params["status"],
            "position"      => $params["position"],//地址名称
            "create_time"   => date("Y-m-d H:i:s")
        ];

        //此处需要记录城市
        $drow = DB::table($this->depart)->where("depart_id",$departId)->first();

        $data["city"] = isset($drow->depart_name) ? $drow->depart_name:"";

        isset($params["remark"]) &&      $data["remark"]      = $params["remark"];//客户QQ

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
    public function edit($params,$id,$uid)
    {
        $row = DB::table($this->business)->where("id",$id)->first();
        if(empty($row))
            $this->failed("访问出错");

        //验证该条记录是否为他本人的
        //if($row->uid != $uid)
            //$this->failed("您不能修改别人的内容");

        $data = [];
        isset($params["status"]) &&         $data["status"]         = $params["status"];//状态
        isset($params["telphone_sale_nums"]) &&       $data["telphone_sale_nums"]       = $params["telphone_sale_nums"];//客户行业
        isset($params["telphone_record"]) &&       $data["telphone_record"]       = $params["telphone_record"];//客户行业


        if(empty($data))
            return true;

        $bool = DB::table($this->business)->where("id",$id)->update($data);

        return $bool;
    }


    /**
     * 分配
     * @param $adminUid
     * @param $customerId
     * @param $toUid
     * @return bool;
     */
    public function allot($isAdmin,$isDepartAdmin,$uid,$level,$toUid,$num)
    {
        //首先查询该条线索是不是 这个管理员的
        if($uid == $toUid)
            $this->failed("不能分配给自己");

        //$uidArr = getUserAuth($uid,$isDepartAdmin,$level);
        if($isAdmin)
        {
            $clist   = DB::table($this->business)->where("sms_send_nums",0)->take($num)->pluck("id");
        }
        else
            $clist   = DB::table($this->business)->where("uid",$uid)->where("sms_send_nums",0)->take($num)->pluck("id");


        $realNum    = count($clist);
        $updateTime = date("Y-m-d H:i:s");

        if(empty($realNum))
            $this->failed("满足条件的商机数量为0");

        if($realNum < $num)
            $this->failed("满足条件的商机数量为".$realNum."个");

        $data = [
            "old_uid"       => $uid,
            "new_uid"       => $toUid,
            "num"           => $realNum,
            "remark"        => "分配了".$realNum."条商机",
            "create_time"   => $updateTime
        ];

        //此处需要事务操作
        try{
            DB::transaction(function () use($clist,$toUid,$data,$updateTime){
                DB::table($this->business)->whereIn("id",$clist)->update(["uid"=>$toUid]);
                //DB::table($this->business_log)->insert($data);
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
                    $query->where('a.name',"like","%".$params["keywords"]."%")
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
                    $query->where('a.name',"like","%".$params["keywords"]."%")
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
        $where  = [];
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

        if(isset($params["keywords"]) && !empty($params["keywords"]))
        {
            //获取数量
            $count = DB::table($this->business." as a")
                ->leftJoin($this->user." as b","b.uid","=","a.uid")
                ->where($where)
                ->where(function($query)use($params){
                    $query->where('a.name',"like","%".$params["keywords"]."%")
                        //->orWhere("a.username","like","%".$params["keywords"]."%")
                        ->orWhere("a.mobile","like","%".$params["keywords"]."%");
                })
                ->whereIn("a.uid",$uidArr)
                ->count();

            if(empty($count))
                return ["list"=>[],"count"=>0];

            $data["count"] = $count;

            $list = DB::table($this->business." as a")
                ->leftJoin($this->user." as b","b.uid","=","a.uid")
                ->where($where)
                ->where(function($query)use($params){
                    $query->where('a.name',"like","%".$params["keywords"]."%")
                        //->orWhere("a.username","like","%".$params["keywords"]."%")
                        ->orWhere("a.mobile","like","%".$params["keywords"]."%");
                })
                ->whereIn("a.uid",$uidArr)
                ->orderby('a.answer_update_time',"desc")
                ->offset($offset)
                ->limit($page_nums)
                ->get(["a.*","b.username"]);

            $data["list"] = $list;

            return $data;
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
            ->orderby('a.answer_update_time',"desc")
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
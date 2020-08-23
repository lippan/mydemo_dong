<?php

/**
 *  数据统计资源
 */
namespace App\Repositories\Admin;


use Illuminate\Support\Facades\DB;

class DataCountRepository
{
    use \App\Helpers\ApiResponse;

    private $user;
    private $business;
    private $business_log;

    //初始化需要用到的表名称
    public function __construct()
    {
        $this->user                 = config('db_table.user_table');
        $this->business             = config('db_table.business_table');
        $this->business_log         = config('db_table.business_log');
    }


    /**
     * 员工统计看板
     */
   public function staff($params)
   {
       $msort      = "a.".$params["field"];
       $asc        = $params["sort"];

       $page       = !empty($params['page'])       ? (int)$params['page']:1;
       $page_nums  = !empty($params['page_nums'])  ? (int)$params['page_nums']:20;
       $offset     = ($page-1) * $page_nums;

       $where      = [];
       $wherecount = "";
       if(!empty($params["keywords"]))
       {
           $wherecount = " where a.username like '%{$params["keywords"]}%'";
           $where[] = ["a.username",'like',$params["keywords"]];
       }

       $count  = DB::table($this->user." as a")
           ->where($where)
           ->count();

       if(empty($count))
           return ["list"=>[],"count"=>0];

       $data["count"] = $count;

       $sql = "select a.uid,a.username,b.wait_num,c.run_num,d.give_up_num,e.deal_num,f.not_deal_num,g.finished_num from sy_user as a 
        LEFT JOIN(select count(*) as wait_num,uid from sy_business where (status='waiting')GROUP BY uid) as b  on  a.uid=b.uid
        LEFT JOIN(select count(*) as run_num,uid from sy_business where (status='running')GROUP BY uid) as c on a.uid =c.uid
        LEFT JOIN(select count(*) as give_up_num,uid from sy_business where (status='give_up')GROUP BY uid) as d on a.uid =d.uid
        LEFT JOIN(select count(*) as deal_num,uid from sy_business where (status='dealed')GROUP BY uid) as e on a.uid =e.uid
        LEFT JOIN(select count(*) as not_deal_num,uid from sy_business where (status='not_dealed')GROUP BY uid) as f on a.uid =f.uid
        LEFT JOIN(select count(*) as finished_num,uid from sy_business where (status='finished')GROUP BY uid) as g on a.uid =g.uid";

       $sql = $sql.$wherecount." ORDER BY $msort $asc limit $offset,$page_nums";

       $list = DB::select($sql);

       $data["list"] = $list;

       //额外算出即将掉入公海的数量
       foreach($list as &$val)
       {
           if(empty($val->wait_num))
               $val->wait_num =0;
           if(empty($val->run_num))
               $val->run_num =0;
           if(empty($val->give_up_num))
               $val->give_up_num =0;
           if(empty($val->deal_num))
               $val->deal_num =0;
           if(empty($val->not_deal_num))
               $val->not_deal_num =0;
           if(empty($val->finished_num))
               $val->finished_num =0;

           $not_get_num = 0;
           //如果当前时间大于晚上的30分钟截止时间  小于截止时间  则为即将掉入公海  1.超过时间未认领的
            if(time() > strtotime(date("Y-m-d 21:30:00")) && time() < strtotime(date("Y-m-d 22:00:00")))
            {
                $not_get_num = DB::table($this->business)->where("uid",$val->uid)->where("status","waiting")->whereDate("create_time",date("Y-m-d"))->count();
            }

           $not_follow_num = 0;
           //2.认领后未跟进的
            if(time() > strtotime(date("Y-m-d 17:30:00")) && time() < strtotime(date("Y-m-d 17:30:00")))
            {
                //获取所有今天认领的商机
                $list1 = DB::table($this->business_log)->where("uid",$val->uid)->groupBy("customer_id")->where("operate_type","get")->whereDate("create_time",date("Y-m-d"))->get();
                //获取所有今天跟进的商机
                $list2 = DB::table($this->business_log)->where("uid",$val->uid)->groupBy("customer_id")->where("operate_type","follow")->whereDate("create_time",date("Y-m-d"))->get();

                $not_follow_num = count($list1) - count($list2);
            }

           //3.跟进时 过期的
           $overtime_num = DB::table($this->business)->where("uid",$val->uid)->where("status","running")->whereNotNull("next_follow_time")->where("next_follow_time","<",date("Y-m-d H:i:s"))->whereDate("create_time",date("Y-m-d"))->count();

           $val->common_sea_num = $not_get_num + $not_follow_num + $overtime_num;

       }

       return $data;
   }

    /**
     * 每日统计看板
     */
   public function everyday($params)
   {
       $msort      = "a.".$params["field"];
       $asc        = $params["sort"];

       $page       = !empty($params['page'])       ? (int)$params['page']:1;
       $page_nums  = !empty($params['page_nums'])  ? (int)$params['page_nums']:20;
       $offset     = ($page-1) * $page_nums;

       $where      = [];
       $wherecount = "";
       if(!empty($params["keywords"]))
       {
           $wherecount = " where a.username like '%{$params["keywords"]}%'";
           $where[] = ["a.username",'like',$params["keywords"]];
       }

       $count  = DB::table($this->user." as a")
           ->where($where)
           ->count();

       if(empty($count))
           return ["list"=>[],"count"=>0];

       $data["count"] = $count;

       $month = date("Y-m");

       $sql = "select a.uid,a.username,b.waitdone_num,c.today_money,d.task_money,d.real_money,e.business_num,f.baidu_num,g.qsb_num,h.qdr_num from sy_user as a 
        LEFT JOIN(select count(*) as waitdone_num,uid from sy_business where (status in ('waiting','running') AND  TO_DAYS(create_time) = TO_DAYS(NOW()))GROUP BY uid) as b  on  a.uid=b.uid
        LEFT JOIN(select sum(deal_money) as today_money,uid from sy_business where (status='dealed' and  TO_DAYS(last_follow_time) = TO_DAYS(NOW()))GROUP BY uid) as c on a.uid =c.uid
        LEFT JOIN(select task_money,real_money,uid from sy_user_month_target where month='{$month}') as d on a.uid =d.uid
        LEFT JOIN(select count(*) as business_num,uid from sy_business where DATE_FORMAT(create_time, '%Y%m') = DATE_FORMAT(CURDATE(), '%Y%m') GROUP BY uid) as e on a.uid =e.uid
        LEFT JOIN(select count(*) as baidu_num,uid from sy_business where (source=10 AND TO_DAYS(create_time) = TO_DAYS(NOW()))GROUP BY uid) as f on a.uid =f.uid
        LEFT JOIN(select count(*) as qsb_num,uid from sy_business where (source=8 AND TO_DAYS(create_time) = TO_DAYS(NOW()))GROUP BY uid) as g on a.uid =g.uid
        LEFT JOIN(select count(*) as qdr_num,uid from sy_business where (source=17 AND TO_DAYS(create_time) = TO_DAYS(NOW()))GROUP BY uid) as h on a.uid =h.uid
        ";

       $sql = $sql.$wherecount." ORDER BY $msort $asc limit $offset,$page_nums";

       $list = DB::select($sql);

       if(!empty($list))
       {
           foreach($list as &$val)
           {
               if(empty($val->waitdone_num))
                   $val->waitdone_num =0;
               if(empty($val->today_money))
                   $val->today_money =0;
               if(empty($val->task_money))
                   $val->task_money =0;
               if(empty($val->real_money))
                   $val->real_money =0;
               if(empty($val->business_num))
                   $val->business_num =0;
               if(empty($val->baidu_num))
                   $val->baidu_num =0;
               if(empty($val->qsb_num))
                   $val->qsb_num =0;
               if(empty($val->qdr_num))
                   $val->qdr_num =0;
           }
       }

       $data["list"] = $list;

       return $data;
   }
}
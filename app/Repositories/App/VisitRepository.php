<?php

/**
 *  拜访资源
 */
namespace App\Repositories\App;
use App\Repositories\Business\WaitDoneRepository;

use Illuminate\Support\Facades\DB;

class VisitRepository
{
    use \App\Helpers\ApiResponse;

    //客户表
    private $customer;
    private $visit;
    private $user;
    private $customer_normal_info;
    private $customer_company_info;

    private $base_url;//图片地址

    //初始化需要用到的表名称
    public function __construct()
    {
        $this->customer                 = config('db_table.customer_table');
        $this->customer_normal_info     = config('db_table.customer_normal_info');
        $this->customer_company_info    = config('db_table.customer_company_info');
        $this->visit                    = config('db_table.visit_table');
        $this->user                     = config('db_table.user_table');
        $this->base_url                 = config('oss.base_url');
    }


    /**
     * 列表
     */
    public function lists($params,$uid,$crm_user_id,$is_admin,$isDepartAdmin=null,$level=null)
    {

        $page       = !empty($params['page'])       ? (int)$params['page']:1;
        $page_nums  = !empty($params['page_nums'])  ? (int)$params['page_nums']:10;
        $offset     = ($page-1) * $page_nums;

        //如果是超级管理员
        if($is_admin)
        {
            $list = $this->adminList($params,$offset,$page_nums);
            return $list;
        }

        if(empty($crm_user_id))
            $list = $this->normalList($uid,$isDepartAdmin,$level,$params,$offset,$page_nums);
        else
            $list = $this->crmList($uid,$crm_user_id,$params,$offset,$page_nums);

        return $list;
    }


    /**
     * 添加
     */
    public function add($params)
    {
        //如果该用户已经存在拜访，并且拜访时间在今天之前，才能创建新的拜访
        $row = DB::table($this->visit)->where("customer_id",$params["customer_id"])->orderBy("create_time","desc")->first();
        if(empty($row))
        {
            $data = [
                "uid"           => $params["uid"],
                "customer_id"   => $params["customer_id"],
                //"next_date"     => $params["next_date"],
                "create_time"   => date("Y-m-d H:i:s")
            ];

            isset($params["level"]) && $data["level"] = $params["level"];

            isset($params["contact_remark"]) && $data["contact_remark"] = $params["contact_remark"];
            //图片为一个数组 需要特别处理
            isset($params["pic_url"])     && $data["pic_url"] = json_encode($params["pic_url"]);
            isset($params["contact_event"]) && $data["contact_event"] = $params["contact_event"];
            isset($params["longitude"]) && $data["longitude"] = $params["longitude"];//经度
            isset($params["latitude"]) && $data["latitude"] = $params["latitude"];//维度
            isset($params["visit_place"]) && $data["visit_place"] = $params["visit_place"];//拜访地址

            isset($params["next_date"])          && $data["next_date"] = $params["next_date"];

            $type = $businessId =null;
            if(isset($params["type"]) && isset($params["business_id"]))
            {
                $type       = $data["type"]        =  $params["type"];
                $businessId = $data["business_id"] =  $params["business_id"];
            }


            $visitId = DB::table($this->visit)->insertGetId($data);
            if($visitId && isset($params["next_date"]) )//如果创建成功，则添加一个事项
            {
                DB::table($this->customer)->where("customer_id",$params["customer_id"])->update(["last_follow_time"=>date("Y-m-d H:i:s")]);

                $waitDoneRepos = new WaitDoneRepository();
                if(!empty($type) && !empty($businessId))
                    $waitDoneRepos->add($params["uid"],$params["customer_id"],$type,$businessId,$params["next_date"],$data["create_time"]);
                else
                    $waitDoneRepos->add($params["uid"],$params["customer_id"],"visit",$visitId,$params["next_date"],$data["create_time"]);
            }

            return $visitId;
        }

        if($row->uid != $params["uid"])
            $this->failed("这个拜访客户不属于您");

        //获取拜访截止时间
        $end_time = strtotime($row->next_date);

        //如果当前时间大于截止时间1天以上，则可以重新创建拜访
        //if(time() - $end_time > 23*60*60)
        //{
            $data = [
                "uid"           => $params["uid"],
                "customer_id"   => $params["customer_id"],
                "create_time"   => date("Y-m-d H:i:s")
            ];

            isset($params["level"]) && $data["level"] = $params["level"];

            isset($params["contact_remark"]) && $data["contact_remark"] = $params["contact_remark"];
            isset($params["pic_url"])     && $data["pic_url"] = json_encode($params["pic_url"]);
            isset($params["contact_event"]) && $data["contact_event"] = $params["contact_event"];
            isset($params["next_date"]) && $data["next_date"] = $params["next_date"];

            isset($params["longitude"]) && $data["longitude"] = $params["longitude"];//经度
            isset($params["latitude"]) && $data["latitude"] = $params["latitude"];//维度
            isset($params["visit_place"]) && $data["visit_place"] = $params["visit_place"];//拜访地址

            $visitId = DB::table($this->visit)->insertGetId($data);

            DB::table($this->customer)->where("customer_id",$params["customer_id"])->update(["last_follow_time"=>date("Y-m-d H:i:s")]);

            return $visitId;
       // }

        //$this->failed("您还有拜访未完成");
    }


    /**
     * 编辑
     */
    public function edit($params,$visitId)
    {
        $cRow = DB::table($this->visit)->where("visit_id",$visitId)->first();
        if(empty($cRow))
            $this->failed("访问出错");

        //下次拜访到了 未处理 过期的，才有修改的机会

        $data = [];
        isset($params["contact_remark"]) && $data["contact_remark"] = $params["contact_remark"];

        if(isset($params["pic_url"]) && !empty($params["pic_url"]))
        {
            foreach($params["pic_url"] as $val)
            {
                if(strpos($val,"http")!==false)
                {
                    $arr[] = parse_url($val)["path"];
                }
                else
                    $arr[] = $val;
            }

            $data["pic_url"] = json_encode($arr);
        }

        isset($params["contact_event"]) && $data["contact_event"] = $params["contact_event"];
        isset($params["next_date"]) && $data["next_date"] = $params["next_date"];
        isset($params["longitude"]) && $data["longitude"] = $params["longitude"];//经度
        isset($params["latitude"]) && $data["latitude"] = $params["latitude"];//维度

        if(empty($data))
            return true;

        //如果下次拜访时间 要修改 ，那么查看对应的 待办任务是否已经完成，若完成则不修改
        if(isset($data["next_date"]) && !empty($data["next_date"]))
        {

        }

        $bool = DB::table($this->visit)->where("visit_id",$visitId)->update($data);

        DB::table($this->customer)->where("customer_id",$cRow->customer_id)->update(["last_follow_time"=>date("Y-m-d H:i:s")]);

        return $bool;
    }

    /**
     * 详情
     */
    public function info($visitId)
    {
        $row = DB::table($this->visit." as a")
            ->leftJoin($this->customer." as b","a.customer_id","=","b.customer_id")
            ->leftJoin($this->user." as c","b.uid","=","c.uid")
            ->where("a.visit_id",$visitId)
            ->first(["a.*","b.name","c.username"]);

        if(empty($row))
            $this->failed("访问出错");

        if(!empty($row->pic_url))
        {
            $list = json_decode($row->pic_url,true);
            foreach($list as &$val)
            {
                if(strpos($val,"http") === false)
                    $val = $this->base_url.$val;
            }
            $row->pic_url = $list;
        }


        return $row;
    }

    /**
     * 业务的拜访列表
     * @param $clueId
     * @return object
     */
    public function visitList($type,$clueId)
    {
        $where = [
            "type"          => $type,
            "business_id"   => $clueId
        ];
        $list = DB::table($this->visit)->where($where)->orderBy("next_date","desc")->get();

        return $list;
    }


    /**
     * 拜访的历史记录
     */
    public function history($params,$uid,$crm_user_id,$is_admin,$isDepartAdmin=null,$level=null)
    {
        $page       = !empty($params['page'])       ? (int)$params['page']:1;
        $page_nums  = !empty($params['page_nums'])  ? (int)$params['page_nums']:10;
        $offset     = ($page-1) * $page_nums;

        //如果是超级管理员
        if($is_admin)
        {
            $list = $this->adminList($params,$offset,$page_nums);
            return $list;
        }

        if(empty($crm_user_id))
            $list = $this->normalList($uid,$isDepartAdmin,$level,$params,$offset,$page_nums);
        else
            $list = $this->crmList($uid,$crm_user_id,$params,$offset,$page_nums);

        return $list;
    }


    //超级管理员用户列表
    protected function adminList($params,$offset,$page_nums)
    {
        $where = [];
        $field  = !empty($params["field"]) ? $params["field"]:"create_time";
        $sort   = !empty($params["sort"]) ? $params["sort"]:"desc";

        isset($params["wait_done"]) && $params["wait_done"] == 1 && $where[] = ["a.next_date",">",date("Y-m-d H:i:s")];

        if(isset($params["customer_id"]))
        {
            $where   = null;
            $where[] = ["a.customer_id","=",$params["customer_id"]];
        }

        if(isset($params["keywords"]) && !empty($params["keywords"]))
        {
            //获取数量
            $count = DB::table($this->visit." as a")
                ->leftJoin($this->customer." as b","a.customer_id","=","b.customer_id")
                ->where($where)
                ->where(function($query)use($params){
                    $query->where('b.name',"like","%".$params["keywords"]."%")
                        ->orWhere("b.company_name","like","%".$params["keywords"]."%")
                        ->orWhere("b.mobile","like","%".$params["keywords"]."%");
                })->count();

            if(empty($count))
                return ["list"=>[],"count"=>0];

            $list = DB::table($this->visit." as a")
                ->leftJoin($this->customer." as b","a.customer_id","=","b.customer_id")
                ->where($where)
                ->where(function($query)use($params){
                    $query->where('b.name',"like","%".$params["keywords"]."%")
                        ->orWhere("b.company_name","like","%".$params["keywords"]."%")
                        ->orWhere("b.mobile","like","%".$params["keywords"]."%");
                })
                ->orderby('a.'.$field,$sort)
                ->offset($offset)
                ->limit($page_nums)
                ->get();

            $data["list"] = $list;

            return $data;
        }

        //获取数量
        $count = DB::table($this->visit." as a")
            ->leftJoin($this->customer." as b","a.customer_id","=","b.customer_id")
            ->where($where)
            ->count();

        if(empty($count))
            return ["list"=>[],"count"=>0];

        $data["count"] = $count;

        $list = DB::table($this->visit." as a")
            ->leftJoin($this->customer." as b","a.customer_id","=","b.customer_id")
            ->where($where)
            ->orderby('a.'.$field,$sort)
            ->offset($offset)
            ->limit($page_nums)
            ->get();

        $data["list"] = $list;

        return $data;
    }

    //不具备crm属性用户列表
    protected function normalList($uid,$isDepartAdmin,$level,$params,$offset,$page_nums)
    {
        $field  = !empty($params["field"]) ? $params["field"]:"create_time";
        $sort   = !empty($params["sort"]) ? $params["sort"]:"desc";

        isset($params["wait_done"]) && $params["wait_done"] == 1 && $where[] = ["a.next_date",">",date("Y-m-d H:i:s")];

        if(isset($params["customer_id"]))
        {
            $where   = null;
            $where[] = ["a.customer_id","=",$params["customer_id"]];
        }

        if($isDepartAdmin)
            $where[] = ['a.level', 'like', $level."%"];
        else
            $where[] = ['a.uid', '=', $uid];

        if(isset($params["keywords"]) && !empty($params["keywords"]))
        {   //获取数量
            $count = DB::table($this->visit." as a")
                ->leftJoin($this->customer." as b","a.customer_id","=","b.customer_id")
                ->where($where)
                ->where(function($query)use($params){
                    $query->where('b.name',"like","%".$params["keywords"]."%")
                        ->orWhere("b.company_name","like","%".$params["keywords"]."%")
                        ->orWhere("b.mobile","like","%".$params["keywords"]."%");
                })
                ->count();

            if(empty($count))
                return ["list"=>[],"count"=>0];

            $data["count"] = $count;

            $list = DB::table($this->visit." as a")
                ->leftJoin($this->customer." as b","a.customer_id","=","b.customer_id")
                ->where($where)
                ->where(function($query)use($params){
                    $query->where('b.name',"like","%".$params["keywords"]."%")
                        ->orWhere("b.company_name","like","%".$params["keywords"]."%")
                        ->orWhere("b.mobile","like","%".$params["keywords"]."%");
                })
                ->orderby('a.'.$field,$sort)
                ->offset($offset)
                ->limit($page_nums)
                ->get();

            $data["list"] = $list;

            return $data;
        }

        //获取数量
        $count = DB::table($this->visit." as a")
            ->leftJoin($this->customer." as b","a.customer_id","=","b.customer_id")
            ->where($where)
            ->count();

        if(empty($count))
            return ["list"=>[],"count"=>0];

        $data["count"] = $count;

        $list = DB::table($this->visit." as a")
            ->leftJoin($this->customer." as b","a.customer_id","=","b.customer_id")
            ->where($where)
            ->orderby('a.'.$field,$sort)
            ->offset($offset)
            ->limit($page_nums)
            ->get();

        $data["list"] = $list;

        return $data;
    }


    //crm属性用户列表
    protected function crmList($uid,$crm_user_id,$params,$offset,$page_nums)
    {
        $field  = !empty($params["field"]) ? $params["field"]:"create_time";
        $sort   = !empty($params["sort"]) ? $params["sort"]:"desc";

        $userArr = getUserAuth($crm_user_id,$uid);
        $where = [];
        isset($params["wait_done"]) && $params["wait_done"] == 1 && $where[] = ["a.next_date",">",date("Y-m-d H:i:s")];

        if(isset($params["customer_id"]))
        {
            $where   = null;
            $where[] = ["a.customer_id","=",$params["customer_id"]];
        }

        if(isset($params["keywords"]) && !empty($params["keywords"]))
        {
            //获取数量
            $count = DB::table($this->visit." as a")
                ->leftJoin($this->customer." as b","a.customer_id","=","b.customer_id")
                ->where($where)
                ->whereIn("a.uid",$userArr)
                ->where(function($query)use($params){
                    $query->where('b.name',"like","%".$params["keywords"]."%")
                        ->orWhere("b.company_name","like","%".$params["keywords"]."%")
                        ->orWhere("b.mobile","like","%".$params["keywords"]."%");
                })
                ->count();

            if(empty($count))
                return ["list"=>[],"count"=>0];

            $data["count"] = $count;

            $list = DB::table($this->visit." as a")
                ->leftJoin($this->customer." as b","a.customer_id","=","b.customer_id")
                ->where($where)
                ->whereIn("a.uid",$userArr)
                ->where(function($query)use($params){
                    $query->where('b.name',"like","%".$params["keywords"]."%")
                        ->orWhere("b.company_name","like","%".$params["keywords"]."%")
                        ->orWhere("b.mobile","like","%".$params["keywords"]."%");
                })
                ->orderby('a.'.$field,$sort)
                ->offset($offset)
                ->limit($page_nums)
                ->get();

            $data["list"] = $list;

            return $data;
        }
        //获取数量
        $count = DB::table($this->visit." as a")
            ->leftJoin($this->customer." as b","a.customer_id","=","b.customer_id")
            ->where($where)
            ->whereIn("a.uid",$userArr)
            ->count();

        if(empty($count))
            return ["list"=>[],"count"=>0];

        $data["count"] = $count;

        $list = DB::table($this->visit." as a")
            ->leftJoin($this->customer." as b","a.customer_id","=","b.customer_id")
            ->where($where)
            ->whereIn("a.uid",$userArr)
            ->orderby('a.'.$field,$sort)
            ->offset($offset)
            ->limit($page_nums)
            ->get();

        $data["list"] = $list;

        return $data;
    }

}
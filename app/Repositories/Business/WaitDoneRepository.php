<?php

/**
 * 待办资源
 */
namespace App\Repositories\Business;


use Illuminate\Support\Facades\DB;

class WaitDoneRepository
{
    use \App\Helpers\ApiResponse;

    private $wait_done_event;
    private $customer;
    private $visit;
    private $business;
    private $clue;

    public function __construct(){
        $this->wait_done_event          = config("db_table.wait_done_event");
        $this->customer                 = config('db_table.customer_table');
        $this->business                 = config('db_table.business_table');
        $this->clue                     = config('db_table.clue_table');
        $this->visit                    = config('db_table.visit_table');
    }


    /**
     * 待办列表
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
     * 添加一个待办事项
     * @param $uid
     * @param $customerId
     * @param $type
     * @param $business_id
     * @param $date
     * @param $create_time
     * @return integer
     */
    public function add($uid,$customerId,$type,$business_id,$date,$create_time)
    {
        //如果是线索，商机，合同，如果存在则需要更新
        if (in_array($type, ["clue", "business", "agreement"]))
        {
            $where = [
                "type"          => $type,
                "business_id"   => $business_id
            ];

            $row = DB::table($this->wait_done_event)->where($where)->first();
            if(!empty($row))
            {
                $bool = DB::table($this->wait_done_event)->where($where)->update(["date"=>$date]);
                return $bool;
            }
        }

        $data = [
            "uid"           => $uid,
            "customer_id"   => $customerId,
            "type"          => $type,
            "business_id"   => $business_id,
            "date"          => $date,
            "create_time"   => $create_time
        ];
        $bool = DB::table($this->wait_done_event)->insert($data);

        return $bool;
    }


    /**
     *  编辑待办事项(一般修改待办时间)
     * @param $waitId
     * @param $date
     * @return  bool
     */
    public function edit($waitId,$date)
    {
        $wRow = DB::table($this->wait_done_event)->where("id",$waitId)->first();
        if(empty($wRow))
            $this->failed("非法请求");

        if($wRow->status == "has_done")//待办变为了已办
            $this->failed("改待办事项已经处理完毕，不能修改");

        $data = [
            "date"          => $date
        ];
        $bool = DB::table($this->wait_done_event)->where("id",$waitId)->update($data);

        return $bool;
    }


    /**
     * 处理待办的事项
     */
    public function doWait($uid,$waitId)
    {
        $where = [
            "uid"   => $uid,
            "id"    => $waitId
        ];
        $row  = DB::table($this->wait_done_event)->where($where)->first();
        if(empty($row))
            $this->failed("您没有此权限");

        if(!empty($row))
        {
                $bool = DB::table($this->wait_done_event)->where("id",$waitId)->update(["status"=>"has_done"]);
            //如果涉及到拜访、线索详情状态展示   是否 TODO?   如果需要 则要增加status字段来标识状态
//            if($row->type == "visit")
//                $bool = DB::table($this->visit)->where("visit_id",$row->business_id)->update(["status"=>"finished"]);
//            if($row->type == "clue")
//                $bool = DB::table($this->clue)->where("clue_id",$row->business_id)->update(["status"=>"finished"]);
//            if($row->type == "business")
//                $bool = DB::table($this->business)->where("business_id",$row->business_id)->update(["status"=>"finished"]);
        }


        return $bool;
    }




    //超级管理员用户列表
    protected function adminList($params,$offset,$page_nums)
    {
        $where[] = ["a.date","<",date("Y-m-d H:i:s")];
        $where[] = ["a.status","=","wait_done"];

        $field  = !empty($params["field"]) ? $params["field"]:"date";
        $sort   = !empty($params["sort"]) ? $params["sort"]:"asc";

        //获取数量
        $count = DB::table($this->wait_done_event." as a")
            ->leftJoin($this->customer." as b","a.customer_id","=","b.customer_id")
            ->where($where)
            ->when(isset($params["keywords"]),function($query)use($params){
                $query->where('b.name',"like","%".$params["keywords"]."%")
                    ->orWhere("b.company_name","like","%".$params["keywords"]."%")
                    ->orWhere("b.mobile","like","%".$params["keywords"]."%");
            })->count();

        if(empty($count))
            return ["list"=>[],"count"=>0];

        $data["count"] = $count;

        $list = DB::table($this->wait_done_event." as a")
            ->leftJoin($this->customer." as b","a.customer_id","=","b.customer_id")
            ->where($where)
            ->when(isset($params["keywords"]),function($query)use($params){
                $query->where('b.name',"like","%".$params["keywords"]."%")
                    ->orWhere("b.company_name","like","%".$params["keywords"]."%")
                    ->orWhere("b.mobile","like","%".$params["keywords"]."%");
            })
            ->orderby('a.'.$field,$sort)
            ->offset($offset)
            ->limit($page_nums)
            ->get(["a.*","b.name","b.is_company","b.company_name"]);

        $data["list"] = $list;
        return $data;
    }


    //不具备crm属性用户列表
    protected function normalList($uid,$isDepartAdmin,$level,$params,$offset,$page_nums)
    {
        $where[] = ["a.date","<",date("Y-m-d H:i:s")];
        $where[] = ["a.status","=","wait_done"];

        $field  = !empty($params["field"]) ? $params["field"]:"date";
        $sort   = !empty($params["sort"]) ? $params["sort"]:"asc";

        if($isDepartAdmin)
            $where[] = ['a.level', 'like', $level."%"];
        else
            $where[] = ['a.uid', '=', $uid];

        $count = DB::table($this->wait_done_event." as a")
            ->leftJoin($this->customer." as b","a.customer_id","=","b.customer_id")
            ->where($where)
            ->when(isset($params["keywords"]),function($query)use($params){
                $query->where('b.name',"like","%".$params["keywords"]."%")
                    ->orWhere("b.company_name","like","%".$params["keywords"]."%")
                    ->orWhere("b.mobile","like","%".$params["keywords"]."%");
            })
            ->count();

        if(empty($count))
            return ["list"=>[],"count"=>0];

        $data["count"] = $count;

        $list = DB::table($this->wait_done_event." as a")
            ->leftJoin($this->customer." as b","a.customer_id","=","b.customer_id")
            ->where($where)
            ->when(isset($params["keywords"]),function($query)use($params){
                $query->where('b.name',"like","%".$params["keywords"]."%")
                    ->orWhere("b.company_name","like","%".$params["keywords"]."%")
                    ->orWhere("b.mobile","like","%".$params["keywords"]."%");
            })
            ->orderby('a.'.$field,$sort)
            ->offset($offset)
            ->limit($page_nums)
            ->get(["a.*","b.name","b.is_company","b.company_name"]);

        $data["list"] = $list;
        return $data;
    }


    //crm属性用户列表
    protected function crmList($uid,$crm_user_id,$params,$offset,$page_nums)
    {
        $where[] = ["a.date","<",date("Y-m-d H:i:s")];
        $where[] = ["a.status","=","wait_done"];

        $field  = !empty($params["field"]) ? $params["field"]:"date";
        $sort   = !empty($params["sort"]) ? $params["sort"]:"asc";

        $userArr = getUserAuth($crm_user_id,$uid);

        $where = [];

        //获取数量
        $count = DB::table($this->wait_done_event." as a")
            ->leftJoin($this->customer." as b","a.customer_id","=","b.customer_id")
            ->where($where)
            ->whereIn("a.uid",$userArr)
            ->when(isset($params["keywords"]),function($query)use($params){
                $query->where('b.name',"like","%".$params["keywords"]."%")
                    ->orWhere("b.company_name","like","%".$params["keywords"]."%")
                    ->orWhere("b.mobile","like","%".$params["keywords"]."%");
            })
            ->count();

        if(empty($count))
            return ["list"=>[],"count"=>0];

        $data["count"] = $count;

        $list = DB::table($this->wait_done_event." as a")
            ->leftJoin($this->customer." as b","a.customer_id","=","b.customer_id")
            ->where($where)
            ->whereIn("a.uid",$userArr)
            ->when(isset($params["keywords"]),function($query)use($params){
                $query->where('b.name',"like","%".$params["keywords"]."%")
                    ->orWhere("b.company_name","like","%".$params["keywords"]."%")
                    ->orWhere("b.mobile","like","%".$params["keywords"]."%");
            })
            ->orderby('a.'.$field,$sort)
            ->offset($offset)
            ->limit($page_nums)
            ->get(["a.*","b.name","b.is_company","b.company_name"]);

        $data["list"] = $list;
        return $data;
    }
}
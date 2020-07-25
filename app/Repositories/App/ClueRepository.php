<?php

/**
 *  线索资源
 */
namespace App\Repositories\App;
use App\Repositories\Business\WaitDoneRepository;

use Illuminate\Support\Facades\DB;

class ClueRepository
{
    use \App\Helpers\ApiResponse;

    //客户表
    private $customer;
    private $source;
    private $clue;
    private $user;
    private $address;

    private $base_url;//图片地址

    //初始化需要用到的表名称
    public function __construct()
    {
        $this->customer                 = config('db_table.customer_table');
        $this->clue                     = config('db_table.clue_table');
        $this->user                     = config('db_table.user_table');
        $this->source                   = config('db_table.crm_source');
        $this->address                  = config('db_table.address_table');
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
        $data = [
            "uid"           => $params["uid"],
            "customer_id"   => $params["customer_id"],
            "request_remark"=> $params["request_remark"],
            "next_date"     => $params["next_date"],
            "create_time"   => date("Y-m-d H:i:s")
        ];

        isset($params["level"]) && $data["level"] = $params["level"];

        isset($params["province_id"]) && $data["province_id"] = $params["province_id"];
        isset($params["city_id"]) && $data["city_id"] = $params["city_id"];
        isset($params["source"]) && $data["source"] = $params["source"];
        //isset($params["next_date"]) && $data["next_date"] = $params["next_date"];

        $clueId = DB::table($this->clue)->insertGetId($data);
        if($clueId)
        {
            DB::table($this->customer)->where("customer_id",$params["customer_id"])->update(["last_follow_time"=>date("Y-m-d H:i:s")]);

            $waitDoneRepos = new WaitDoneRepository();
            $waitDoneRepos->add($params["uid"],$params["customer_id"],"clue",$clueId,$params["next_date"],$data["create_time"]);
        }

        return $clueId;
    }


    /**
     * 编辑
     */
    public function edit($params,$clueId)
    {
        $cRow = DB::table($this->clue)->where("clue_id",$clueId)->first();
        if(empty($cRow))
            $this->failed("访问出错");

        $data = [];
        isset($params["province_id"]) && $data["province_id"] = $params["province_id"];
        isset($params["city_id"]) && $data["city_id"] = $params["city_id"];
        isset($params["source"]) && $data["source"] = $params["source"];

        if(empty($data))
            return true;

        $bool = DB::table($this->clue)->where("clue_id",$clueId)->update($data);

        DB::table($this->customer)->where("customer_id",$cRow->customer_id)->update(["last_follow_time"=>date("Y-m-d H:i:s")]);

        return $bool;
    }

    /**
     * 详情
     */
    public function info($clueId)
    {
        $row = DB::table($this->clue." as a")
            ->leftJoin($this->customer." as b","a.customer_id","=","b.customer_id")
            ->leftJoin($this->source." as c","a.source","=","c.id")
            ->leftJoin($this->user." as d","d.uid","=","a.uid")
            ->leftJoin($this->address." as e","a.province_id","=","e.id")
            ->leftJoin($this->address." as f","a.city_id","=","f.id")
            ->where("a.clue_id",$clueId)
            ->first(["a.*","b.name","c.name as source_name","d.username","e.name as pname","f.name as cname"]);

        if(empty($row))
            $this->failed("访问出错");

        return $row;
    }


    /**
     * 线索历史
     * @param $params
     * @return object
     */
    public function  history($params,$uid,$crm_user_id,$is_admin,$isDepartAdmin=null,$level=null)
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
     * 我的客户列表
     */
    public function myCustomerList($uid)
    {
        $list = DB::table($this->customer)->where("uid",$uid)->get();

        return $list;
    }



    /**
     * 超级管理员查看列表
     * @param $params
     * @param $offset
     * @param $page_nums
     * @return object
     */
    protected  function adminList($params,$offset,$page_nums)
    {
        $field  = !empty($params["field"]) ? $params["field"]:"create_time";
        $sort   = !empty($params["sort"]) ? $params["sort"]:"desc";

        $where = [];
        isset($params["customer_id"]) && $where[] = ["a.customer_id","=",$params["customer_id"]];

        $list = DB::table($this->clue." as a")
            ->leftJoin($this->customer." as b","a.customer_id","=","b.customer_id")
            ->leftJoin($this->user." as c","c.uid","=","a.uid")
            ->where($where)
            ->orderby('a.'.$field,$sort)
            ->offset($offset)
            ->limit($page_nums)
            ->get(["a.*","b.*","c.username"]);

        return $list;
    }


    //不具备crm属性用户列表
    protected function normalList($uid,$isDepartAdmin,$level,$params,$offset,$page_nums)
    {
        $field  = !empty($params["field"]) ? $params["field"]:"create_time";
        $sort   = !empty($params["sort"]) ? $params["sort"]:"desc";

        isset($params["customer_id"]) && $where[] = ["a.customer_id","=",$params["customer_id"]];

        if($isDepartAdmin)
            $where[] = ['a.level', 'like', $level."%"];
        else
            $where[] = ['a.uid', '=', $uid];

        $list = DB::table($this->clue." as a")
            ->leftJoin($this->customer." as b","a.customer_id","=","b.customer_id")
            ->leftJoin($this->user." as c","c.uid","=","a.uid")
            ->where($where)
            ->orderby('a.'.$field,$sort)
            ->offset($offset)
            ->limit($page_nums)
            ->get(["a.*","b.*","c.username"]);

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
        isset($params["customer_id"]) && $where[] = ["a.customer_id","=",$params["customer_id"]];

        $list = DB::table($this->clue." as a")
            ->leftJoin($this->customer." as b","a.customer_id","=","b.customer_id")
            ->leftJoin($this->user." as c","c.uid","=","a.uid")
            ->where($where)
            ->whereIn("a.uid",$userArr)
            ->orderby('a.'.$field,$sort)
            ->offset($offset)
            ->limit($page_nums)
            ->get(["a.*","b.*","c.username"]);

        $data["list"] = $list;

        return $data;
    }
}
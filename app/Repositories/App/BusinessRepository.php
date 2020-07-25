<?php

/**
 *  商机资源
 */
namespace App\Repositories\App;
use App\Repositories\Common\AddressRepository;

use Illuminate\Support\Facades\DB;

class BusinessRepository
{
    use \App\Repositories\BaseRepository;
    use \App\Helpers\ApiResponse;

    //客户表
    private $customer;
    private $business;
    private $business_product;
    private $clue;
    private $user;
    private $source;

    private $base_url;//图片地址

    //初始化需要用到的表名称
    public function __construct()
    {
        $this->customer                 = config('db_table.customer_table');
        $this->clue                     = config('db_table.clue_table');
        $this->user                     = config('db_table.user_table');
        $this->business                 = config('db_table.business_table');
        $this->business_product         = config('db_table.business_product');
        $this->source                   = config('db_table.crm_source');
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
            "source"        => $params["source"],
            "province_id"   => $params["province_id"],
            "city_id"       => $params["city_id"],
            "plan_buy_time" => $params["plan_buy_time"],
            "plan_send_time"=> $params["plan_send_time"],
            "prepare_money" => $params["prepare_money"],
            "create_time"   => date("Y-m-d H:i:s")
        ];

        isset($params["level"]) && $data["level"] = $params["level"];
        isset($params["pay_type"]) && $data["pay_type"] = $params["pay_type"];
        isset($params["longitude"]) && $data["longitude"] = $params["longitude"];//经度
        isset($params["latitude"]) && $data["latitude"] = $params["latitude"];//维度

        isset($params["use_address"]) && $data["use_address"] = $params["use_address"];
        isset($params["remark"]) && $data["remark"] = $params["remark"];

        $businessId = DB::table($this->business)->insertGetId($data);
        if($businessId>0)
        {
            DB::table($this->customer)->where("customer_id",$params["customer_id"])->update(["last_follow_time"=>date("Y-m-d H:i:s")]);

            $bdata = [];

            foreach($params["product_data"] as $val)
            {
                $bdata[] = [
                    "business_id"   => $businessId,
                    "product_id"    => $val["product_id"],
                    "product_desc"  => $val["product_desc"],
                    "buy_num"       => $val["buy_num"],
                    "price"         => $val["price"],
                    "qa_time"       => $val["qa_time"],
                    "qa_month"      => $val["qa_month"],
                    "qa_unit"       => $val["qa_unit"],
                    "qa_value"      => $val["qa_value"],
                    "product_config_remark" => isset($val["product_config_remark"]) ? $val["product_config_remark"]:""
                ];
            }

            DB::table($this->business_product)->insert($bdata);
        }

        return $businessId;
    }


    /**
     * 编辑
     */
    public function edit($params,$businessId)
    {
        $cRow = DB::table($this->business)->where("business_id",$businessId)->first();
        if(empty($cRow))
            $this->failed("访问出错");

        $data = [];
        isset($params["pay_type"]) &&  $data["source"]  = $params["source"];
        isset($params["province_id"]) &&  $data["province_id"]      = $params["province_id"];
        isset($params["city_id"]) &&  $data["city_id"]              = $params["city_id"];
        isset($params["plan_buy_time"]) &&  $data["plan_buy_time"]  = $params["plan_buy_time"];
        isset($params["plan_send_time"]) &&  $data["plan_send_time"]  =$params["plan_send_time"];
        isset($params["prepare_money"]) &&  $data["prepare_money"]  = $params["prepare_money"];
        isset($params["pay_type"]) && $data["pay_type"] = $params["pay_type"];
        isset($params["use_address"]) && $data["use_address"] = $params["use_address"];
        isset($params["product_remark"]) && $data["product_remark"] = $params["product_remark"];

        if(empty($data))
            return true;

        $bool = DB::table($this->business)->where("business_id",$businessId)->update($data);

        DB::table($this->customer)->where("customer_id",$cRow->customer_id)->update(["last_follow_time"=>date("Y-m-d H:i:s")]);

        return $bool;
    }

    /**
     * 详情
     */
    public function info($businessId)
    {
        $row = DB::table($this->business." as a")
            ->leftJoin($this->customer." as b","a.customer_id","=","b.customer_id")
            ->leftJoin($this->user." as c","a.uid","=","c.uid")
            ->where("a.business_id",$businessId)
            ->first(["a.*","b.name","c.username as create_user"]);

        if(empty($row))
            $this->failed("访问出错");

        //查询cityName
        $addressRepos   = new AddressRepository();
        $city = $addressRepos->getCity($row->city_id,1);

        $row->city_name = isset($city->name) ? $city->name:"";
        $row->province_name = config("app.belong_province_name");

        //来源
        $srow = DB::table($this->source)->where("id",$row->source)->first();
        $row->source = $srow->name;

        //支付方式
        $type = ["全款","分期","自办融资","自办按揭"];

        $row->pay_type = $type[$row->pay_type];

        $product = $this->productList($businessId);

        $row->product_list = $product;

        return $row;
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


    /**
     * 超级管理员查看列表
     * @param $params
     * @param $offset
     * @param $page_nums
     * @return object
     */
    protected  function adminList($params,$offset,$page_nums)
    {
        $where = [];

        if(isset($params["customer_id"]))
        {
            $where[] = ["a.customer_id","=",$params["customer_id"]];
        }

        $list = DB::table($this->business." as a")
            ->leftJoin($this->customer." as b","a.customer_id","=","b.customer_id")
            ->leftJoin($this->user." as c","c.uid","=","a.uid")
            ->where($where)
            ->orderby('a.create_time',"desc")
            ->offset($offset)
            ->limit($page_nums)
            ->get(["a.*","b.*","c.username"]);

        if(!$list->isEmpty())
        {
            foreach($list as &$val)
            {
                $val->product_list =  $this->productList($val->business_id);
            }
        }

        return $list;
    }


    //不具备crm属性用户列表
    protected function normalList($uid,$isDepartAdmin,$level,$params,$offset,$page_nums)
    {
        if(isset($params["customer_id"]))
        {
            $where[] = ["a.customer_id","=",$params["customer_id"]];
        }

        if($isDepartAdmin)
            $where[] = ['a.level', 'like', $level."%"];
        else
            $where[] = ['a.uid', '=', $uid];

        $list = DB::table($this->business." as a")
            ->leftJoin($this->customer." as b","a.customer_id","=","b.customer_id")
            ->leftJoin($this->user." as c","c.uid","=","a.uid")
            ->where($where)
            ->orderby('a.create_time',"desc")
            ->offset($offset)
            ->limit($page_nums)
            ->get(["a.*","b.*","c.username"]);

        $data["list"] = $list;

        return $data;
    }

    //crm属性用户列表
    protected function crmList($uid,$crm_user_id,$params,$offset,$page_nums)
    {
        $userArr = getUserAuth($crm_user_id,$uid);

        $where = [];

        if(isset($params["customer_id"]))
        {
            $where[] = ["a.customer_id","=",$params["customer_id"]];
        }

        $list = DB::table($this->business." as a")
            ->leftJoin($this->customer." as b","a.customer_id","=","b.customer_id")
            ->leftJoin($this->user." as c","c.uid","=","a.uid")
            ->where($where)
            ->whereIn("a.uid",$userArr)
            ->orderby('a.create_time',"desc")
            ->offset($offset)
            ->limit($page_nums)
            ->get(["a.*","b.*","c.username"]);

        $data["list"] = $list;

        return $data;
    }


    /**
     * 产品列表
     * @param $businessId
     * @return object
     */
    protected function productList($businessId)
    {
        $list = DB::table($this->business_product)->where("business_id",$businessId)->get();
        if(!$list->isEmpty())
        {
            foreach($list as &$val)
            {
                if($val->qa_unit == 0)
                    $unit = "小时";
                elseif($val->qa_unit == 1)
                    $unit = "方量";
                else
                    $unit = "公里";

                $val->qa_unit  = $unit;
            }
        }

        return $list;
    }
}
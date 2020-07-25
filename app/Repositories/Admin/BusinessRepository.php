<?php

/**
 *  后台-商机资源
 */
namespace App\Repositories\Admin;
use App\Repositories\Common\AddressRepository;

use Illuminate\Support\Facades\DB;

class BusinessRepository
{
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
    public function lists($params)
    {
        $page       = !empty($params['page'])       ? (int)$params['page']:1;
        $page_nums  = !empty($params['page_nums'])  ? (int)$params['page_nums']:10;
        $offset     = ($page-1) * $page_nums;

        //如果是超级管理员

        $list = $this->adminList($params,$offset,$page_nums);
        return $list;
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
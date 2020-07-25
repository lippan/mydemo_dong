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
    private $customer;
    private $user;
    private $customer_normal_info;
    private $customer_company_info;

    //初始化需要用到的表名称
    public function __construct()
    {
        $this->user                     = config('db_table.user_table');
        $this->customer                 = config('db_table.customer_table');
        $this->customer_normal_info     = config('db_table.customer_normal_info');
        $this->customer_company_info    = config('db_table.customer_company_info');
    }


    /**
     * 陌客列表
     */
    public function lists($params)
    {
        $page       = !empty($params['page'])       ? (int)$params['page']:1;
        $page_nums  = !empty($params['page_nums'])  ? (int)$params['page_nums']:10;
        $offset     = ($page-1) * $page_nums;

        $list = $this->adminList($params,$offset,$page_nums);

        return $list;
    }


    /**
     * 客户详情
     */
    public function info($customerId)
    {
        $row = DB::table($this->customer." as a")
            ->leftJoin($this->user." as b","a.charge_uid","=","b.uid")
            ->where("a.customer_id", $customerId)
            ->first(["a.*","b.username as charge_name"]);

        if (empty($row))
            $this->failed("访问出错");

        //如果是公司客户
        if ($row->is_company)
            $info = DB::table($this->customer_company_info)->where("customer_id", $customerId)->first();
        else
            $info = DB::table($this->customer_normal_info)->where("customer_id", $customerId)->first();

        return $info;
    }


    //超级管理员列表
    protected function adminList($params,$offset,$page_nums)
    {
        $where = [];
        $field  = !empty($params["field"]) ? $params["field"]:"create_time";
        $sort   = !empty($params["sort"]) ? $params["sort"]:"desc";
        isset($params["charge_uid"]) && $where[] = ["a.charge_uid","=",$params["charge_uid"]];

        if(isset($params["keywords"]) && !empty($params["keywords"]))
        {

            $list = DB::table($this->customer." as a")
                ->where($where)
                ->where(function($query)use($params){
                    $query->where('a.name',"like","%".$params["keywords"]."%")
                        ->orWhere("a.company_name","like","%".$params["keywords"]."%")
                        ->orWhere("a.mobile","like","%".$params["keywords"]."%");
                })
                ->orderby('a.'.$field,$sort)
                ->offset($offset)
                ->limit($page_nums)
                ->get();

            $data["list"] = $list;
            return $data;
        }

        //获取数量
        $count = DB::table($this->customer." as a")->where($where)->count();
        if(empty($count))
            return ["list"=>[],"count"=>0];

        $data["count"] = $count;

        $list = DB::table($this->customer." as a")
            ->where($where)
            ->orderby('a.'.$field,$sort)
            ->offset($offset)
            ->limit($page_nums)
            ->get();

        $data["list"] = $list;

        return $data;
    }


}
<?php

/**
 *  后台-拜访资源
 */
namespace App\Repositories\Admin;

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


}
<?php

/**
 *  后台-线索资源
 */
namespace App\Repositories\Admin;
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
}
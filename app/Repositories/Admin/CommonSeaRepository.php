<?php

/**
 *  公海资源
 */
namespace App\Repositories\Admin;


use Illuminate\Support\Facades\DB;

class CommonSeaRepository
{
    use \App\Helpers\ApiResponse;

    //客户表
    private $business;
    private $business_log;
    private $user;

    //初始化需要用到的表名称
    public function __construct()
    {
        $this->user                     = config('db_table.user_table');
        $this->business                 = config('db_table.business_table');
        $this->business_log             = config('db_table.business_log');
    }


    /**
     * 列表（公海都可以看到）
     */
    public function lists($params,$uid,$isAdmin,$isDepartAdmin,$level)
    {
        $page       = !empty($params['page'])       ? (int)$params['page']:1;
        $page_nums  = !empty($params['page_nums'])  ? (int)$params['page_nums']:10;
        $offset     = ($page-1) * $page_nums;


        $list = $this->adminList($params,$offset,$page_nums);

        return $list;
    }



//超级管理员列表
    protected function adminList($params,$offset,$page_nums)
    {
        $where[] = ["a.uid","=",0];
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
        if(isset($params["type"]) && !empty($params["type"]))
        {
            $where[] = ["a.type","=",$params["type"]];
        }

        if(isset($params["keywords"]) && !empty($params["keywords"]))
        {

            $list = DB::table($this->business." as a")
                ->leftJoin($this->user." as b","a.uid","=","b.uid")
                ->where($where)
                ->where(function($query)use($params){
                    $query->where('a.name',"like","%".$params["keywords"]."%")
                        //->orWhere("a.company_name","like","%".$params["keywords"]."%")
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





}
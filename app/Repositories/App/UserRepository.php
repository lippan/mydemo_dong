<?php

/**
 *  app端用户资源
 */
namespace App\Repositories\App;

use Illuminate\Support\Facades\DB;

class UserRepository
{
    use \App\Helpers\ApiResponse;

    private $user_table;
    private $crm_employee;

    //初始化需要用到的表名称
    public function __construct()
    {
        $this->user_table       = config('db_table.user_table');
        $this->crm_employee     = config('db_table.crm_employee');
    }


    /**
     * 获取事业部列表
     * @return object
     */
    public function lists($params,$uid,$crm_user_id,$is_admin,$isDepartAdmin=null,$level=null)
    {
        $page       = !empty($params['page'])       ? (int)$params['page']:1;
        $page_nums  = !empty($params['page_nums'])  ? (int)$params['page_nums']:10;
        $offset     = ($page-1) * $page_nums;

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
     * 个人中心
     */
    public function my($uid)
    {
        $info = DB::table($this->user_table." as a")
            ->leftJoin($this->crm_employee." as b","a.crm_user_id","=","b.user_id")
            ->where("a.uid",$uid)
            ->first(["a.username","b.position_text"]);

        //获取代理商公司名称
        if(!empty($info))
            $info->agent_company_name = config("app.agent_company_name");

        return $info;
    }


    /**
     * 修改个人信息
     */
    public function modify($params,$type="account")
    {

    }


    /**
     * 超级管理员
     * @param $params
     * @param $offset
     * @param $page_nums
     * @return object
     */
    protected function adminList($params,$offset,$page_nums)
    {
        $where = [];
        $field  = !empty($params["field"]) ? $params["field"]:"create_time";
        $sort   = !empty($params["sort"]) ? $params["sort"]:"desc";

        $list = DB::table($this->user_table." as a")
            ->where($where)
            ->orderby('a.'.$field,$sort)
            ->offset($offset)
            ->limit($page_nums)
            ->get(["uid","username"]);

        $data["list"] = $list;

        return $data;
    }


    /**
     * @param $uid
     * @param $isDepartAdmin
     * @param $level
     * @param $params
     * @param $offset
     * @param $page_nums
     * @return object
     */
    protected function normalList($uid,$isDepartAdmin,$level,$params,$offset,$page_nums)
    {
        $field  = !empty($params["field"]) ? $params["field"]:"create_time";
        $sort   = !empty($params["sort"]) ? $params["sort"]:"desc";

        if($isDepartAdmin)
            $where[] = ['a.level', 'like', $level."%"];
        else
            $where[] = ['a.uid', '=', $uid];

        $list = DB::table($this->user_table." as a")
            ->where($where)
            ->orderby('a.'.$field,$sort)
            ->offset($offset)
            ->limit($page_nums)
            ->get(["uid","username"]);

        $data["list"] = $list;

        return $data;
    }


    /**
     * @param $crm_user_id
     * @param $params
     * @param $offset
     * @param $page_nums
     * @return object
     */
    protected function crmList($uid,$crm_user_id,$params,$offset,$page_nums)
    {
        $field  = !empty($params["field"]) ? $params["field"]:"create_time";
        $sort   = !empty($params["sort"]) ? $params["sort"]:"desc";

        $userArr = getUserAuth($crm_user_id,$uid);

        $list = DB::table($this->user_table." as a")
            ->whereIn("a.uid",$userArr)
            ->orderby('a.'.$field,$sort)
            ->offset($offset)
            ->limit($page_nums)
            ->get(["uid","username"]);

        $data["list"] = $list;

        return $data;
    }
}
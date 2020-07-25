<?php

/**
 *  公共地址资源
 */
namespace App\Repositories\Common;


use Illuminate\Support\Facades\DB;

class DepartRepository
{
    use \App\Repositories\BaseRepository;
    use \App\Helpers\ApiResponse;

    private $depart_table;

    //初始化需要用到的表名称
    public function __construct()
    {
        $this->depart_table       = config('db_table.crm_org');
    }


    /**
     * 获取事业部列表
     * @param null $pid
     * @return \Illuminate\Support\Collection
     */
    public function lists()
    {
        $list = DB::table($this->depart_table)->get();

        return $list;
    }



    /**
     * 部门层级列表
     * @param $lists
     * @param int $pid
     * @return array
     */
    public function getDepTree($lists,$pid = 0,&$data = []){
        foreach ($lists as $key => $vo){
            if ($vo['pid'] == $pid){
                $data[] = $vo;
                unset($lists[$key]);
                $this->getDepTree($lists,$vo['id'],$data);
            }
        }
    }


    public function syncLevel($agentId="")
    {
        $bool = DB::table($this->depart_table)->where("org_id",$agentId)->update(["level"=>1]);

        //获取二级分类id
        $orgArr = DB::table($this->depart_table)->where("org_up",$agentId)->pluck("org_id")->toArray();

        if(!empty($orgArr))
        {
            foreach($orgArr as $key=>$val)
            {
                $num = $key+1;
                DB::table($this->depart_table)->where("org_id",$val)->update(["level"=>'1/'.$num]);

                $a = DB::table($this->depart_table)->where("org_up",$val)->pluck("org_id")->toArray();
                if(!empty($a))
                {
                    foreach($a as $k=>$v)
                    {
                        $n = $k+1;
                        DB::table($this->depart_table)->where("org_id",$v)->update(["level"=>'1/'.$num.'/'.$n]);

                        $b = DB::table($this->depart_table)->where("org_up",$v)->pluck("org_id")->toArray();

                        if(!empty($b))
                        {
                            foreach($b as $k1=>$v1)
                            {
                                $n1 = $k1+1;
                                DB::table($this->depart_table)->where("org_id",$v1)->update(["level"=>'1/'.$num.'/'.$n."/".$n1]);

                                $c = DB::table($this->depart_table)->where("org_up",$v1)->pluck("org_id")->toArray();
                                if(!empty($c))
                                foreach($c as $k2=>$v2)
                                {
                                    $n2 = $k2+1;
                                    DB::table($this->depart_table)->where("org_id",$v1)->update(["level"=>'1/'.$num.'/'.$n."/".$n1."/".$n2]);
                                }
                            }
                        }
                    }

                }
            }
        }


    }


    public function updateTree($orgArr)
    {
        if(!empty($orgArr))
        {
            foreach($orgArr as $key=>$val) {
                $num = $key + 2;
                DB::table($this->depart_table)->where("org_id", $val)->update(["level" => '1/' . $num]);
            }
        }
    }
}
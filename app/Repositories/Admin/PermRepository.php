<?php

/**
 *  后台权限资源
 */
namespace App\Repositories\Admin;


use Illuminate\Support\Facades\DB;

class PermRepository
{
    use \App\Repositories\BaseRepository;
    use \App\Helpers\ApiResponse;

    private $perm_table;

    //初始化需要用到的表名称
    public function __construct()
    {
        $this->perm_table       = config('db_table.perm_table');
    }


    /**
     * 获取权限列表
     * @param null $pid
     * @return \Illuminate\Support\Collection
     */
    public function lists()
    {
        $list = DB::table($this->perm_table)->orderBy("module_name")->get();

        return $list;
    }



    /**
     * 同步权限节点
     * @param $perms
     * @return bool
     */
    public function sync($perms)
    {
        $time = date("Y-m-d H:i:s");
        //获取所有权限节点
        $routeArr = DB::table($this->perm_table)->pluck("route");

        if($routeArr->isEmpty())//如果权限节点为空,直接插入数据
        {
            $data = [];
            foreach($perms as $val)
            {
                $arr = explode("_",$val["name"]);
                $data[] = [
                    "module_name"   => $arr[0],
                    "perm_name"     => $arr[1],
                    "route"         => $val["uri"],
                    "create_time"   => $time
                ];
            }
            $bool = DB::table($this->perm_table)->insert($data);
            return $bool;
        }

        foreach($perms as $val)
        {
            $arr = explode("_",$val["name"]);
            $data = [
                "module_name"   => $arr[0],
                "perm_name"     => $arr[1],
                "route"         => $val["uri"],

            ];

            //不存在的自动添加，存在的 手动去更新
            if(!in_array($val["uri"],$routeArr->toArray()))
            {
                $data["create_time"]   = $time;
                DB::table($this->perm_table)->insert($data);
            }
        }

        return true;
    }
}
<?php

/**
 *  后台角色资源
 */
namespace App\Repositories\Common;


use Illuminate\Support\Facades\DB;

class RoleRepository
{
    use \App\Repositories\BaseRepository;
    use \App\Helpers\ApiResponse;

    private $role_table;

    //初始化需要用到的表名称
    public function __construct()
    {
        $this->role_table       = config('db_table.role_table');
    }


    /**
     * 获取事业部列表
     * @param null $pid
     * @return \Illuminate\Support\Collection
     */
    public function lists()
    {
        $list = DB::table($this->role_table)->get();

        return $list;
    }
}
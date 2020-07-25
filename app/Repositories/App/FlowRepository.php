<?php

/**
 *  工作流资源
 */
namespace App\Repositories\App;

use Illuminate\Support\Facades\DB;

class FlowRepository
{
    use \App\Repositories\BaseRepository;
    use \App\Helpers\ApiResponse;

    private $flow;
    private $flow_node;
    private $user;

    private $base_url;//图片地址

    //初始化需要用到的表名称
    public function __construct()
    {
        $this->flow                     = config('db_table.flow_table');
        $this->flow_node                = config('db_table.flow_node');
        $this->user                     = config('db_table.user_table');
        $this->base_url                 = config('oss.base_url');
    }


    /**
     * 列表
     */
    public function lists()
    {
        $list = DB::table($this->flow)->get();

        return $list;
    }







}
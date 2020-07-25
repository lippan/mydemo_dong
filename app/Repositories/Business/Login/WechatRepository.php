<?php

/**
 *  微信登陆资源
 */
namespace App\Repositories\Business\Login;

use Illuminate\Support\Facades\DB;

class WechatRepository
{
    use \App\Repositories\BaseRepository;
    use \App\Helpers\ApiResponse;

    protected $wechat;//微信用户表

    public function __construct(){
        $this->wechat = config("db_table.wechat_table");
    }


    /**
     * 添加微信用户数据
     * @param $params
     * @return mixed
     */
    public function add($params){

        $fansId = DB::table($this->wechat)->insertGetId($params);

        if(empty($fansId))
            $this->failed("新增微信用户失败");

        $data["fans_id"]    = $fansId;
        $data["header"]     = $params["header"];
        $data["nick"]       = $params["nick"];
        $data["status"]     = 1;
        return $data;
    }
}
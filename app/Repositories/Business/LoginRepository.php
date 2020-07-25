<?php

/**
 * 登陆资源
 */
namespace App\Repositories\Business;

use App\Repositories\Business\Login\AccountRepository;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class LoginRepository
{
    use \App\Repositories\BaseRepository;
    use \App\Helpers\ApiResponse;

    protected $user;

    public function __construct(){
        $this->user = config("db_table.user_table");
    }


    /**
     * 登陆逻辑
     * @param $params
     * @param $type
     */
    public function index($params,$type)
    {
        $user = null;
        $accountRepos = new AccountRepository();
        //账号密码登陆
        if($type == 1)
            $user = $accountRepos->login($params["account"],$params["password"]);

        if($type == 2)
            $user = $accountRepos->smsLogin($params["account"],$params["password"]);

        if($type == 3)
            $user = $accountRepos->crmLogin($params["account"],$params["password"]);

        return $user;
    }
}
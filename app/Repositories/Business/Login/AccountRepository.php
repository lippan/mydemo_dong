<?php

/**
 *  账号登陆资源
 */
namespace App\Repositories\Business\Login;

use App\Libraries\Crm;
use App\Libraries\Sap\Sap;
use App\Repositories\Common\SmsRepository;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Hash;
use stdClass;

class AccountRepository
{
    use \App\Repositories\BaseRepository;
    use \App\Helpers\ApiResponse;

    protected $user;//管理员用户表
    protected $depart;//部门表
    protected $crm_employee;//crm用户表
    protected $crm_org;//组织表

    public function __construct(){
        $this->user     = config("db_table.user_table");
        $this->depart   = config("db_table.depart_table");
        $this->crm_employee = config("db_table.crm_employee");
        $this->crm_org  = config("db_table.crm_org");
    }


    /**
     * 登陆
     * @param $account
     * @param $password
     * @return object
     */
    public function login($account,$password)
    {
        $where = [
            "a.mobile"    => $account,
        ];
        $user = DB::table($this->user." as a")
            ->leftJoin($this->depart." as b","a.depart_id","=","b.depart_id")
            ->where($where)
            ->first(["a.*","b.level"]);

        if(empty($user))
            $this->failed("账号不存在,请联系管理员");

        //验证密码
        if (!Hash::check($password.$user->slat, $user->password))
            $this->failed('账号或密码错误');

        return $user;
    }



    /**
     * 短信验证码登陆
     * @param  $mobile,$code
     * @return object
     */
    public function smsLogin($mobile,$code)
    {
        $smsRepository = new SmsRepository();
        $type = "login";
        $result = $smsRepository->checkSms($mobile,$code,$type);
        if(empty($result))
            $this->failed("验证码不正确");

        $where = [
            "a.mobile"    => $mobile,
        ];

        $user = DB::table($this->user." as a")
            ->leftJoin($this->depart." as b","a.depart_id","=","b.depart_id")
            ->where($where)
            ->first(["a.*","b.level"]);

        if(empty($user))
            $this->failed("账号不存在,请联系管理员");

        //如果登陆成功，则废弃掉原验证码
        $smsRepository->setUsed($mobile,$code,$type);

        return $user;
    }


    /**
     * crm登陆
     * @param  $mobile,$code
     * @return object
     */
    public function crmLogin($account,$password)
    {
        //如果该账号密码在系统中，那么绕过crm直接登录
        $user = DB::table($this->user)->where("crm_user_id",$account)->first();

        //如果账号存在则去 验证crm
        if(empty($user) || empty($user->password) || !Hash::check($password.$user->slat, $user->password))//为首次登陆 需要接口验证
        {
            try{
                $sap = new Sap();
                $bool = $sap->login($account,$password);

            }
            catch(Exception $e)
            {
                $this->failed("crm登陆失败:".$e->getMessage());
            }
            if(empty($bool))
                $this->failed("账号或密码错误");

            $slat = rand_str(6);
            $data = [
                "slat"      => $slat,
                "password"  => Hash::make($password.$slat)
            ];

            if(empty($user))
            {
                //获取crm账号信息
                $uRow = DB::table($this->crm_employee." as a")
                    ->leftJoin($this->crm_org." as b","a.org_id","=","b.org_id")
                    ->where("a.user_id",$account)
                    ->first();

                $user = $this->createCrmAccount($account,$slat,$data["password"],$uRow);
                return $user;
            }

            DB::table($this->user)->where("crm_user_id",$account)->update($data);

            return $user;
        }

        //用户权限



        return $user;
    }


    //创建crm账号
    protected function createCrmAccount($account,$slat,$password,$uRow)
    {
        $data = [
            "password"      => $password,
            "slat"          => $slat,
            //"username"      => $account,
            "crm_user_id"   => $account,
            "create_time"   => date("Y-m-d H:i:s")
        ];

        isset($uRow->partner_text) && $data["username"] = $uRow->partner_text;
        isset($uRow->org_id) && $data["crm_org_id"]     = $uRow->org_id;

        //创建crm用户
        $uid = DB::table($this->user)->insertGetId($data);
        if($uid>0)
        {
            $user = new StdClass();
            $user->uid          = $uid;
            $user->status       = 1;
            $user->username     = $account;
            $user->crm_user_id  = $account;

            if(isset($uRow->level) && !empty($uRow->level))
                $user->level  = $uRow->level;

            return $user;
        }
        else
            $this->failed("数据库写入记录失败");
    }


    //通过org_id 获取此人层级
    protected function getLevel($org_id)
    {
        //
    }

}
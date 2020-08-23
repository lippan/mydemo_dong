<?php
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * 生成随机字符串
 * @return string
 */
function rand_str($lenght = 6)
{
    $randStr = str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890');
    $rand = substr($randStr,0,$lenght);

    return $rand;
}


/**
 * 生成随机数字
 */
function rand_code($n = 4)
{
    $chars='0123456789';
    mt_srand((double)microtime() * pow(10, $n) * getmypid());

    $CheckCode="";
    while(strlen($CheckCode) < $n)
        $CheckCode .= substr($chars, (mt_rand()%strlen($chars)), 1);


    return $CheckCode;
}



/**
 * 获取毫秒级时间戳
 */

if (! function_exists('getMicroTime')) {
    function getMicroTime(){
        $mt = microtime();
        $pair = explode(' ', $mt);
        return $pair[1].substr($pair[0], 2, 6);
    }
}


/**
 * 获取线上ip
 * @return array|false|string
 */
function get_onlineip(){
    $onlineip = '';
    if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown'))
    {
        $onlineip = getenv('HTTP_CLIENT_IP');
    }
    elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown'))
    {
        $onlineip = getenv('HTTP_X_FORWARDED_FOR');
    }
    elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown'))
    {
        $onlineip = getenv('REMOTE_ADDR');
    }
    elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown'))
    {
        $onlineip = $_SERVER['REMOTE_ADDR'];
    }
    return $onlineip;
}

/**
 * 后台操作日志
 */
function admin_operate_log($user,$controller,$params,$operate_type="modify",$remark="")
{
    $data = [
        "user_id"       => isset($user["uid"]) ? $user["uid"]:1,
        "user_name"     => isset($user["username"])? $user["username"]:"admin",
        "controller"    => isset($controller) ? $controller:"",
        "type"          => $operate_type,
        "content"       => json_encode($params),
        "remark"        => $remark,
        "create_time"   => date("Y-m-d H:i:s")
    ];

    $bool = DB::table("admin_operate_log")->insert($data);
}


/**
 * 解析加密token(为了游客页面 与 需要登录页面)
 */
function expain_token($token)
{
    try
    {
        $params = Crypt::decrypt($token);
    }
    catch(DecryptException $e)//捕获异常信息
    {
        failed($e->getMessage());
    }

    //判断是否过期
    if(time()>$params['expire'])
        failed("token已过期！",401);

    if(!isset($params["person_id"]))
        failed("请求错误！",401);

    return $params["person_id"];
}

/**
 * 按用户uid获取权限
 * @param $uid
 * @param $isDepartAdmin
 * @param $level
 * @return mixed
 */
function getUserAuth($uid,$isDepartAdmin,$level)
{
    //如果不是部门管理员
    if(empty($isDepartAdmin))
        return [$uid];

    $depart_table       = config("db_table.depart_table");
    $user_table         = config("db_table.user_table");

    $uidArr = DB::table($depart_table." as a")
        ->leftJoin($user_table." as b","a.depart_id","=","b.depart_id")
        ->where("a.level","like","$level%")
        ->pluck("b.uid")
        ->toArray();

    return empty($uidArr) ? [] :$uidArr;
}

/**
 * 是否显示用户搜索
 * @param $crmUserId
 * @param $uid
 * @param int $isAdmin
 */
function isShowUserSearch($crmUserId,$uid,$isAdmin=0)
{
    if($isAdmin)
        return 1;

    $crm_org_admin  = config("db_table.crm_org_admin");

    if(!empty($crmUserId))
    {
        $row = DB::table($crm_org_admin)->where("crm_user_id",$crmUserId)->first();
        if(!empty($row))
            return 1;
        else
            return 0;
    }
    if(!empty($uid))
    {
        $row = DB::table($crm_org_admin)->where("uid",$uid)->first();
        if(!empty($row))
            return 1;
        else
            return 0;
    }

}



/**
 * 递归 树节点算法
 * @param array $array
 * @param number $pid
 */
 function getChild($array,$pid = 0){
    $data = array();

    foreach ($array as $k=>$v){

        //PID符合条件的
        if($v->org_up == $pid){

            //寻找子集
            $child = getChild($array,$v->org_id);
            //加入数组
            $v->child = $child?:array();
            $data[] = $v;//加入数组中
        }
    }

    return $data;
}


/**
 * 失败响应
 * @param $msg
 * @param int $code
 */
function failed($data,$code=4000,$msg="failed")
{
    $data = [
        "msg"   => $msg,
        "code"  => $code,
        "data"  => $data,
        "time"  => date("Y-m-d H:i:s")
    ];

    throw new HttpResponseException(response()->json($data));
}




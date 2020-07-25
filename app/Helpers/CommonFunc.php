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
 * @param $crmUserId
 */
function getUserAuth($crmUserId,$uid)
{
    $crm_employee   = config("db_table.crm_employee");
   // $crm_position   = config("db_table.crm_position");
    $crm_org_admin  = config("db_table.crm_org_admin");
    $crm_org        = config("db_table.crm_org");
    $user_table     = config("db_table.user_table");

    $row = DB::table($crm_org_admin)->where("crm_user_id",$crmUserId)->first();
    if(empty($row))
        return [$uid];


    //获取该员工的岗位信息
//    $row = DB::table($crm_employee." as a")
//        ->leftJoin($crm_position." as b","a.position_id","=","b.position_id")
//        ->leftJoin($user_table." as d","d.crm_user_id","=","a.user_id")
//        ->where("a.user_id",$crmUserId)
//        ->where("a.org_id",$adminRow->org_id)
//        ->first(["a.*","b.position_text","d.uid"]);

    //判断确认该组织下是否还有下级组织，如果存在下级组织 那么  下级组织的员工也是可以被领导者看到的

    //该员工是否是当前组织的管理者
    if($row->is_org_admin)//获取该组织下所有的员工userid(除开并行管理者)
    {
        //获取当前组织作为上级组织的 列表
        $orgList = DB::table($crm_org)->where("org_up",$row->org_id)->pluck("org_id")->toArray();
        //查询是否有其他管理者
        $list = DB::table($crm_org_admin)->where("org_id",$row->org_id)->whereNotIn("crm_user_id",[$crmUserId])->pluck("uid")->toArray();

        if(!empty($orgList))//如果存在下级组织
        {
            $thirdOrgId = DB::table($crm_org)->whereIn("org_up",$orgList)->pluck("org_id")->toArray();
            if(!empty($thirdOrgId))
            {
                 $orgList = array_merge($orgList,$thirdOrgId);
            }

            $otherUidArr = DB::table($crm_employee." as a")
                ->leftJoin($user_table." as b","b.crm_user_id","=","a.user_id")
                ->whereIn("a.org_id",$orgList)
                ->whereNotNull("b.uid")
                ->pluck("b.uid")
                ->toArray();
        }

        $userArr = DB::table($crm_employee." as a")
            ->leftJoin($user_table." as b","b.crm_user_id","=","a.user_id")
            ->where("a.org_id",$row->org_id)
            ->whereNotNull("b.uid")
            ->pluck("b.uid")
            ->toArray();

        if(!empty($otherUidArr))
            $userArr        = array_merge($userArr,$otherUidArr);

        if(!empty($list))
        {
            $remainArr      = array_diff($userArr,$list);
            return $remainArr;
        }

        return $userArr;
    }

    return [$row->uid];

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




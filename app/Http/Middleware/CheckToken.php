<?php
/**
 * Created by PhpStorm.
 * User: lippan
 * Date: 2019/4/15
 * Time: 下午2:24
 */

namespace App\Http\Middleware;
use Closure;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class CheckToken{

    use \App\Helpers\ApiResponse;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /*$token_data = [
            'uid'       => 4,
            'level'     => "",
            'is_depart_admin'=> 0,
            'status'    => 1,
            'username'  => "daiy11",
            'is_admin'  => 1,
            'role_id'   => "",
            'crm_user_id'=> "daiy11",
            'expire'    => time()+3600*24*17
        ];
        echo  Crypt::encrypt($token_data);exit;*/

        if("OPTIONS" == $request->method())//过滤掉嗅探请求
            $this->failed("请求方式不合法");

        $token = $request->input("token");
        //$token = $request->header("token");
        if(empty($token))
            $this->failed("token不能为空");

        try
        {
            $params = Crypt::decrypt($token);
        }
        catch(DecryptException $e)//捕获异常信息
        {
            $this->failed($e->getMessage(),4002);
        }

        //判断是否过期
        if(time()>$params['expire'])
            $this->failed("token已过期！",4001);


        $user = [
            'uid'       => $params["uid"],
            'status'    => $params["status"],
            'username'  => $params["username"],
            'is_admin'  => $params["is_admin"],
            'role_id'   => $params["role_id"],
            'level'     => $params["level"],//组织机构节点
            'crm_user_id'       => $params["crm_user_id"],//crm用户id
            'is_depart_admin'   => $params["is_depart_admin"],//是否为当前节点管理员
        ];

        if(isset($params["uid"]))
        {
            $request->attributes->add($user);
        }
        else
            $this->failed("请求错误！",4001);

        return $next($request);
    }

    /**
     * 检测是否使用手机访问
     * @access public
     * @return bool
     */
    public function isMobile()
    {
        if (isset($_SERVER['HTTP_VIA']) && stristr($_SERVER['HTTP_VIA'], "wap")) {
            return true;
        } elseif (isset($_SERVER['HTTP_ACCEPT']) && strpos(strtoupper($_SERVER['HTTP_ACCEPT']), "VND.WAP.WML")) {
            return true;
        } elseif (isset($_SERVER['HTTP_X_WAP_PROFILE']) || isset($_SERVER['HTTP_PROFILE'])) {
            return true;
        } elseif (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i', $_SERVER['HTTP_USER_AGENT'])) {
            return true;
        } else {
            return false;
        }
    }
}
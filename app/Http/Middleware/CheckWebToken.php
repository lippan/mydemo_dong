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

class CheckWebToken{

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

        if("OPTIONS" == $request->method())//过滤掉嗅探请求
            $this->failed("请求方式不合法");

        //if(config('app.env')=="local")
            //return $next($request);

        $token = $request->input("token");
        if(empty($token))
            $this->failed("token不能为空");

        try
        {
            $params = Crypt::decrypt($token);
        }
        catch(DecryptException $e)//捕获异常信息
        {
            $this->failed($e->getMessage());
        }

        //判断是否过期
        if(time()>$params['expire'])
            $this->failed("token已过期！",4001);

        if($params["is_admin"]!=1)
            $this->failed("您没有权限");

        $user = [
            'uid'       => $params["uid"],
            'status'    => $params["status"],
            'username'  => $params["username"],
            'is_admin'  => $params["is_admin"],
            'role_id'   => $params["role_id"],
            'level'     => $params["level"],//组织机构节点
            'is_depart_admin' => isset($params["is_depart_admin"]) ? $params["is_depart_admin"]:0,//是否为当前节点管理员
        ];


        if(isset($params["uid"]))
        {
            $request->attributes->add($user);
        }
        else
            $this->failed("请求错误！",4001);

        return $next($request);
    }



}
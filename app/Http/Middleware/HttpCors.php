<?php
/**
 * Created by PhpStorm.
 * User: lippan
 * Date: 2019/4/15
 * Time: 下午2:24
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Mockery\CountValidator\Exception;

class HttpCors{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
        header("Access-Control-Allow-Headers: DNT,Keep-Alive,User-Agent,Cache-Control,Content-Type,Authorization,token");
        if("OPTIONS" == $request->getMethod()){
            return response();
        }

        return $next($request);
    }
}
<?php
/**
 * api返回接口定义
 */

namespace App\Helpers;
use Illuminate\Http\Exceptions\HttpResponseException;

trait ApiResponse
{
    /**
     * 失败响应
     * @param $msg
     * @param int $code
     */
    public function failed($data,$code=4000,$msg="failed")
    {
        $data = [
            "msg"   => $msg,
            "code"  => $code,
            "data"  => $data,
            "time"  => date("Y-m-d H:i:s")
        ];

        throw new HttpResponseException(response()->json($data));
    }

    /**
     * 成功响应
     * @param $data
     * @param int $code
     * @param string $msg
     */
    public function success($data,$code=2000,$msg="success")
    {
        $data = [
            "msg"   => $msg,
            "code"  => $code,
            "data"  => $data,
            "time"  => date("Y-m-d H:i:s")
        ];

        throw new HttpResponseException(response()->json($data));
    }

}
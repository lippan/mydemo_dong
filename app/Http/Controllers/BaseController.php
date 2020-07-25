<?php

/**
 * 后台基础控制器
 */
namespace App\Http\Controllers;

use Curl\Curl;
use Exception;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;


header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Methods:POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers:Origin, X-Requested-With, Content-Type, Accept, token');

class BaseController  extends Controller
{

    private $curl;

    public function __construct(Request $request)
    {
        if("OPTIONS" == $request->method())//过滤掉嗅探请求
            $this->failed("请求方式不合法");
    }

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

    /**
     * 抛出异常
     * @param $msg
     * @throws Exception
     */
    public function excption($msg){
        throw new Exception($msg);
    }


    /**
     * 获取结果
     * @param $url
     * @param $post
     * @return mixed
     * @throws Exception
     */
    public function getResult($url,$requestData = NULL,$requestType = "post")
    {
        $data = !empty($requestData) ? $requestData:NULL;

        $this->curl = new Curl();
        $this->curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $this->curl->setOpt(CURLOPT_SSL_VERIFYHOST, false);

        $res    = $requestType == "get" ? $this->curl->get($url, $data) : $this->curl->post($url, $data);

        if ($this->curl->error)
            throw new Exception($this->curl->errorMessage);

        $res = is_object($res) ? $this->object_to_array($res) : json_decode($res, true);

        return $res;
    }

    /**
     * 对象转数组
     * @param $object
     * @return mixed
     */
    public function object_to_array(&$object){
        $object = json_decode(json_encode($object),true);
        return  $object;
    }
}

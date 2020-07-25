<?php

namespace App\Libraries\sso;

/**
 * sso基类
 */
class Base{

    public $appid;

    protected $appsecret;

    /**
     * @return \Redis
     */
    public $redis;

    public $redis_prefix;

    public $server_url;

    protected function __construct(){
        $this->appid = env('APP_ID');
        $this->appsecret = env('APP_SECRET');
        $this->redis = new \Redis();
        $this->redis->connect(env('REDIS_HOST'),env('REDIS_PORT'),3);
        $this->redis->auth(env('REDIS_PASSWORD'));
        $this->redis->select(2);
        $this->redis_prefix = env('APP_NAME');
        $this->server_url = env('OPENAPI_SSO');
    }

    protected function curl($url, $method = 'get', $data = [], $headers = []){
        // CURL 请求
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if ($method == 'post'){
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        $result = curl_exec($ch);
        $code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        curl_close($ch);
        $code == 200 or $this->exception('请求错误');
        return json_decode($result, true);
    }

    protected function exception($msg){
        throw new \Exception($msg);
    }

    public function json($code,$msg = '',$data = ''){
        $data = json_encode([
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        ],JSON_UNESCAPED_UNICODE);
        return response($data)
            ->header('Content-Type','application/json')
            ->header('Access-Control-Allow-Origin','*')
            ->header('Access-Control-Allow-Methods','POST, GET, OPTIONS, PUT, DELETE')
            ->header('Access-Control-Allow-Headers','DNT,Keep-Alive,User-Agent,Cache-Control,Content-Type,Authorization,token');
    }

    public function random_str($length = 8){
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = "";
        for ( $i = 0; $i < $length; $i++ )
        {
            $str .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }
        return $str ;
    }
}
<?php

namespace App\Libraries\sso;

/**
 * oauth2.0逻辑类
 */
class Oauth2 extends Base{

    private static $instance;

    private $expire = 7200; // 用户登录续期时间

    public static function getInstance(){
        if (!self::$instance instanceof self){
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * 获取全局token
     */
    public function getAccessToken(){
        // 缓存中获取
        $access_token = $this->redis->get("access_token:{$this->appid}");
        if ($access_token) return $access_token;
        $url = "{$this->server_url}/openapi/oauth2/access_token";
        $query = http_build_query([
            'appid' => $this->appid,
            'secret' => $this->appsecret,
            'grant_type' => 'client_credential'
        ]);
        $response = $this->curl($url.'?'.$query);
        $response['code'] == 1 or $this->exception($response['msg']);
        // 缓存access_token
        $this->redis->set("access_token:{$this->appid}",$response['data']['access_token'], $response['data']['expire']-100);
        return $response['data']['access_token'];
    }

    /**
     * 网页授权access_token
     * @param $code
     * @return bool|string
     * @throws \Exception
     */
    public function getOauth2AccessToken($code){
        $url = "{$this->server_url}/openapi/oauth2/access_token";
        $query = http_build_query([
            'appid' => $this->appid,
            'secret' => $this->appsecret,
            'code' => $code,
            'grant_type' => 'authorization_code'
        ]);
        $response = $this->curl($url.'?'.$query);
        $response['code'] == 1 or $this->exception($response['msg']);
        return $response['data']['access_token'];
    }

    /**
     * 拉取登录用户
     * @param $access_token
     * @return mixed
     * @throws \Exception
     */
    public function getUser($access_token){
        $url = "{$this->server_url}/openapi/user/get_user";
        $response = $this->curl($url.'?access_token='.$access_token);
        $response['code'] == 1 or $this->exception($response['msg']);
        return $response['data'];
    }

    /**
     * 获取当前用户
     * @param $token
     * @return array
     */
    public function user($token){
        // 验证是否为管理员
        $user = $this->redis->hGetAll("users:{$token}");
        // 登录续期
        $this->redis->expire("users:{$token}",$this->expire);
        return $user;
    }
}
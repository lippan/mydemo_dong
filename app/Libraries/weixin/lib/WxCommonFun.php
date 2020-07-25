<?php

namespace App\Libraries\weixin\lib;



class WxCommonFun{

    protected $config;

    /**
     * wxCommonFun constructor.
     */
    function __construct()
    {
        $this->config=new WxJsConfig();

    }


     function getAccessToken(){

            $config=new WxJsConfig();

            $baseurl=$config->access_token_url.'&appid='.$config->appid.'&secret='.$config->appsecret;

            $res=$this->get_curl_contents($baseurl);
            $res = json_decode($res, true);

            return isset($res['access_token']) ? $res['access_token']:null;
     }

        /**
         * @fun 返回用户授权的跳转url
         * https://open.weixin.qq.com/connect/oauth2/authorize?appid=APPID&redirect_uri=REDIRECT_URI&response_type=code&scope=SCOPE&state=STATE#wechat_redirect
         */
        function urlOAuthRedirect($scope){

            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $redirect_url   = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

            return $this->config->oauth_url.'?appid='.$this->config->appid.'&redirect_uri='.urlencode($redirect_url).'&response_type=code&scope='.$scope.'&state=STATE#wechat_redirect';
        }

    /**
     * @fun 返回获取网页授权access_token的url
     * 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=APPID&secret=SECRET&code=CODE&grant_type=authorization_code '
     * @returnstring
     */
     function urlOAuthAccessToken($code,$type){

         if($type=='wx'){

             $url = $this->config->access_token.'?appid='.$this->config->appid.'&secret='.$this->config->appsecret.'&code='.$code.'&grant_type=authorization_code';

             return $url;
         }

         if($type=='pc_wx'){

             return $this->config->access_token.'?appid='.$this->config->pc_appid.'&secret='.$this->config->pc_appsecret.'&code='.$code.'&grant_type=authorization_code';
         }

     }


    /**
     * @fun 返货获取微信用户信息的url https://api.weixin.qq.com/sns/userinfo?access_token=ACCESS_TOKEN&openid=OPENID&lang=zh_CN
     * @param $accessToken
     * @param $openid
     * @return string
     */
     function urlOAuthUserInfo($accessToken, $openid){

         return $this->config->user_info.'?access_token='.$accessToken.'&openid='.$openid.'&lang=zh_CN';

     }

    /**
     * @param $url
     * @param string $method
     * @param array $data
     * @return object
     */
      function get_curl_contents($url, $method ='GET', $data = array()) {

            if ($method == 'POST') {
                //使用crul模拟
                $ch = curl_init();

                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
                curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_URL, $url);
                $result = curl_exec($ch); //执行发送
                curl_close($ch);
            }else {
                if (ini_get('allow_<a href="/tags.php/fopen/" target="_blank">fopen</a>_url') == '1') {
                    $result = file_get_contents($url);
                }else {
                    //使用crul模拟
                    $ch = curl_init();
                    //允许请求以文件流的形式返回
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                    //禁用https
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                    curl_setopt($ch, CURLOPT_URL, $url);
                    $result = curl_exec($ch); //执行发送
                    curl_close($ch);
                }
            }
            return $result;
      }

    }

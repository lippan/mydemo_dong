<?php

namespace App\Libraries\weixin;

use App\Libraries\weixin\lib\WxCommonFun;

use App\Libraries\weixin\lib\WxJsConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class wxjs{

    public function __construct()
    {
        $this->WxcommonFun=new WxCommonFun();
    }

    //获取微信信息
    //getFullInfo是否获取全部资料，false，只获取openid
    public function getWxInfo($code,$getFullInfo=true,$type='wx'){

        if(empty($code))
            return null;

        $scope = $getFullInfo ?  'snsapi_userinfo':'snsapi_base';


        $ret = self::getOAuthAccessToken($code,$type);


        if(!isset($ret['openid']))
            return null;


        if ($getFullInfo) {
            $userInfo = self::getOAuthUserInfo($ret['access_token'], $ret['openid']);


            if(!isset($userInfo['openid'])){
                return null;
            }else{
                return $userInfo;
            }
        }else{
            return $ret['openid'];
        }
    }


    /**
     * 获取网页授权 access_token
     * @param string $code
     * @return Array
     */
    protected  function getOAuthAccessToken($code,$type)
    {
        //获取accessToken的url
        $url = $this->WxcommonFun->urlOAuthAccessToken($code,$type);
        //执行
        $res = $this->WxcommonFun->get_curl_contents($url);

        $result = json_decode($res, true);


        if (!isset($result['access_token'])) {
            Log::stack(['daily'])->emergency('获取网页授权access_token失败，errrmsg='.$result['errmsg'].'错误代码='.$result['errcode'].'code='.$code);
        }

        return array(
            'access_token' =>  isset($result['access_token']) ? $result['access_token'] :'',
            'scope'        =>  isset($result['scope']) ? $result['scope'] :'',
            'openid'       =>  isset($result['openid']) ? $result['openid'] :'',
        );

    }


    protected  function getOAuthUserInfo($accessToken, $openid)
    {

        $url    = $this->WxcommonFun->urlOAuthUserInfo($accessToken, $openid);
        $res    = $this->WxcommonFun->get_curl_contents($url);
        $result = json_decode($res, true);

        if (!isset($result['openid'])) {

            Log::stack(['daily'])->emergency('拉取用户信息失败，errrmsg='.$result['errmsg'].'错误代码='.$result['errcode']);
        }

        return $result;
    }


    //微信JsSdk分享所需要参数
    function getWxShareParams(){

        $param['timestamp'] =   time();
        $param['noncestr']  =   'Wm3WZYTPz0wzccnW';
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $param['url']=$protocol.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        //var_dump($_SERVER['HTTP_REFERER']);
        //如果是ajax请求获取分享参数
        if($_SERVER['HTTP_HOST']=='superadmin.shikee.tv'||$_SERVER['HTTP_HOST']=='ph.shikee.tv'||$_SERVER['HTTP_HOST']=='plive.shikee.tv'){
            $param['url']=isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] :'';
        }

        $config=new WxJsConfig();
        $param['appid']=$config->appid;

        $param['jsapi_ticket']=$this->wxGetJsapiTicket();



        //生成签名
        $string = 'jsapi_ticket='.$param['jsapi_ticket'].'&noncestr='. $param['noncestr'].'&timestamp='.$param['timestamp'].'&url='. $param['url'];
        $param['signature'] = sha1($string);


        return $param;
    }


    //获取微信公从号ticket
    function wxGetJsapiTicket() {

        $ticket=Cache::get('wx_share_js_params_ticket');

        //先判断token是否有效
        $token=$this->getAccessToken();

        if(empty($ticket)||!self::verfyToken($token['token'])){

                $parmas=$this->getAccessToken();

                $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.$parmas['token'].'&type=jsapi';
                $res = $this->WxcommonFun->get_curl_contents($url);

                $res = json_decode($res, true);

                if(isset($res['ticket'])&&!empty($res['ticket'])){

                    $ticket=$res['ticket'];

                    //缓存ticket
                    Cache::put('wx_share_js_params_ticket',$ticket,60);

                }else{
                    $ticket=null;
                }
        }

        return $ticket;
    }

    /**
     * 发送模板消息
     */
     function sendTemplate($params,$template_id,$ds_id,$openid){

        $access_token=$this->getAccessToken();

        foreach ($params as $k=>$v){
            $data[$k]=array("value"=>$v,"color"=>"#173177");
        }

        $template = array(
            "touser"=>$openid,
            "template_id"=>$template_id,
            "url"=>"ph.shikee.tv/?ds_id=".$ds_id,
            "data"=>$data
        );

        $json_template = json_encode($template);


        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=" .$access_token['token'];

        $ret=request_post($url,urldecode($json_template));

        return $ret;
    }

    //获取accesstoken的方法
    function getAccessToken(){

        //存储一天
        $token = Cache::get('wx_share_js_params_token');

        $verfyRet=self::verfyToken($token);


        if(empty($token)||!$verfyRet)
        {
            $config=new WxJsConfig();

            $appId=$config->appid;
            $appsecret=$config->appsecret;

            $baseurl=$config->access_token_url.'&appid='.$appId.'&secret='.$appsecret;

            $res=$this->WxcommonFun->get_curl_contents($baseurl);

            $res = json_decode($res, true);
            $token = !empty($res['access_token'])?$res['access_token']:'';

            if(!empty($token))
                Cache::put('wx_share_js_params_token',$token,60);

        }

        $token= !empty($token) ? $token:null;

        return compact('appId','appsecret','token');
    }

    protected function verfyToken($token){

        $config=new WxJsConfig();

        $ip_token_url=$config->ip_token_url;

        $res=$this->WxcommonFun->get_curl_contents($ip_token_url.$token);

        $res = json_decode($res, true);

        if(isset($res['errcode'])&&$res['errcode']==40001){
            return false;
        }

        return true;
    }
}

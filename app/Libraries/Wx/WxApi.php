<?php

namespace App\Libraries\Wx;

use Curl\Curl;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 *  微信接口类
 * Class WxApi
 * @package weixin
 */
class WxApi{

    /**
     * 公众号接口
     * @var array
     */
    public static $api = [
        'component_access_token'   => ['name' => '第三方平台component_access_token', 'url' => 'https://api.weixin.qq.com/cgi-bin/component/api_component_token'],
        'pre_auth_code' => ['name' => '获取与授权码', 'url' => 'https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode'],
        'authorization_access_token'    => ['name' => '获取授权令牌', 'url' => 'https://api.weixin.qq.com/cgi-bin/component/api_query_auth'],
        'authorization_refresh_token'   => ['name' => '获取刷新令牌', 'url' => 'https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token'],
        'authorizer_info'  => ['name' => '获取授权方基本信息', 'url' => 'https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info'],
        'create_menu'   => ['name' => '创建菜单', 'url' => 'https://api.weixin.qq.com/cgi-bin/menu/create'],
        'get_menu'  => ['name' => '查询菜单', 'url' => 'https://api.weixin.qq.com/cgi-bin/menu/get'],
        'del_menu'  => ['name' => '删除菜单', 'url' => 'https://api.weixin.qq.com/cgi-bin/menu/delete'],
        'user_get'  => ['name' => '获取关注者列表', 'url' => 'https://api.weixin.qq.com/cgi-bin/user/get'],
        'user_info'  => ['name' => '获取用户基本信息', 'url' => 'https://api.weixin.qq.com/cgi-bin/user/info'],
        'user_info_batchget'  => ['name' => '批量获取用户信息', 'url' => 'https://api.weixin.qq.com/cgi-bin/user/info/batchget'],
        'add_material'  => ['name' => '新增永久媒体素材', 'url' => 'https://api.weixin.qq.com/cgi-bin/material/add_material'],
        'batchget_material'  => ['name' => '获取素材列表', 'url' => 'https://api.weixin.qq.com/cgi-bin/material/batchget_material'],
        'get_tmp_material'  => ['name' => '获取临时素材', 'url' => 'https://api.weixin.qq.com/cgi-bin/media/get'],
        'get_material'  => ['name' => '获取永久素材', 'url' => 'https://api.weixin.qq.com/cgi-bin/material/get_material'],
        'del_material'  => ['name' => '删除永久素材', 'url' => 'https://api.weixin.qq.com/cgi-bin/material/del_material'],
        'add_news'  => ['name' => '新增图文素材', 'url' => 'https://api.weixin.qq.com/cgi-bin/material/add_news'],
        'uploadimg'  => ['name' => '上传图文消息图片', 'url' => 'https://api.weixin.qq.com/cgi-bin/media/uploadimg'],
        'oauth2_authorize'  => ['name' => '网页授权获取code', 'url' => 'https://open.weixin.qq.com/connect/oauth2/authorize'],
        'oauth2_access_token'  => ['name' => '通过code换取access_token', 'url' => 'https://api.weixin.qq.com/sns/oauth2/component/access_token'],
        'oauth2_refresh_token'  => ['name' => '刷新access_token', 'url' => 'https://api.weixin.qq.com/sns/oauth2/component/refresh_token'],
        'get_jsapi_ticket'  => ['name' => '获取jsapi_ticket', 'url' => 'https://api.weixin.qq.com/cgi-bin/ticket/getticket'],
        'get_all_private_template'  => ['name' => '获取模板列表', 'url' => 'https://api.weixin.qq.com/cgi-bin/template/get_all_private_template'],
        'template_send'  => ['name' => '发送模板消息', 'url' => 'https://api.weixin.qq.com/cgi-bin/message/template/send'],
        'shorturl'  => ['name' => '微信短链接', 'url' => 'https://api.weixin.qq.com/cgi-bin/shorturl'],
        'qrcode'    => ['name' => '生成二维码', 'url' => 'https://api.weixin.qq.com/cgi-bin/qrcode/create'],
        'kfaccount_add'  => ['name' => '添加客服', 'url' => 'https://api.weixin.qq.com/customservice/kfaccount/add'],
        'kfaccount_edit'    => ['name' => '修改客服', 'url' => 'https://api.weixin.qq.com/customservice/kfaccount/update'],
        'kfaccount_del' => ['name' => '删除客服', 'url' => 'https://api.weixin.qq.com/customservice/kfaccount/del'],
        'kfaccount_inviteworker'  => ['name' => '邀请绑定客服', 'url' => 'https://api.weixin.qq.com/customservice/kfaccount/inviteworker'],
        'getkflist' => ['name' => '获取客服列表', 'url' => 'https://api.weixin.qq.com/cgi-bin/customservice/getkflist'],
        'custom_send'  => ['name' => '发送客服消息', 'url' => 'https://api.weixin.qq.com/cgi-bin/message/custom/send'],
        'uploadheadimg'  => ['name' => '上传客服头像', 'url' => 'https://api.weixin.qq.com/customservice/kfaccount/uploadheadimg'],
        'getmsglist'  => ['name' => '获取客服聊天记录', 'url' => 'https://api.weixin.qq.com/customservice/msgrecord/getmsglist'],
        'showqrcode'  => ['name' => 'ticket换取二维码', 'url' => 'https://mp.weixin.qq.com/cgi-bin/showqrcode'],
        'sns_oauth2_access_token'  => ['name' => '微信登录获取access_token', 'url' => 'https://api.weixin.qq.com/sns/oauth2/access_token'],
        'sns_oauth2_refresh_token' => ['name' => '微信登录刷新access_token', 'url' => 'https://api.weixin.qq.com/sns/oauth2/refresh_token'],
        'sns_userinfo'  => ['name' => '网页授权获取用户信息', 'url' => 'https://api.weixin.qq.com/sns/userinfo'],
        'get_wifi_toke'  => ['name' => '获取开插件wifi_toke', 'url' => 'https://api.weixin.qq.com/bizwifi/openplugin/token'],
        'get_access_token'  => ['name' => '获取全局access_token','url' => 'https://api.weixin.qq.com/cgi-bin/token']
    ];


    /**
     * 授权方类型
     * @var array
     */
    public static $service_type_info = [
        0   => '订阅号',
        1   => '升级后的订阅号',
        2   => '服务号',
    ];

    /**
     * 认证类型
     * @var array
     */
    public static $verify_type_info = [
        -1  => '未认证',
        0   => '微信认证',
        1   => '新浪微博认证',
        2   => '腾讯微博认证',
        3   => '已资质认证通过但还未通过名称认证',
        4   => '已资质认证通过、还未通过名称认证，但通过了新浪微博认证',
        5   => '已资质认证通过、还未通过名称认证，但通过了腾讯微博认证',
    ];

    public $_curl;
    protected $module;
    private static $_instance;

    private function __construct($module){
        $this->_curl = new Curl();
        $this->_curl->setOpt(CURLOPT_SSL_VERIFYPEER, FALSE);
        $this->_curl->setOpt(CURLOPT_SSL_VERIFYHOST, FALSE);
        $this->module = $module;
    }

    public static function getInstance($module = 'weixin'){
        if (!self::$_instance instanceof self){
            self::$_instance = new self($module);
        }
        return self::$_instance;
    }

    /**
     * 获取第三方平台access_token
     * @return $component_access_token
     */
    public function getComponentAccessToken(){
        $data = [
            'component_appid'           => Config::get('component_appid'),
            'component_appsecret'       => Config::get('component_appsecret'),
            'component_verify_ticket'   => Config::get('component_verify_ticket')
        ];

        $url = self::$api['component_access_token']['url'];
        $res = $this->getResult($url,$data);

        return $res["component_access_token"];
    }

    /**
     * 获取预授权码
     * @return $pre_auth_code
     */
    public function getPreAuthCode($component_access_token){
        $data = [
            'component_appid'   => Config::get('component_appid'),
        ];
        $url = self::$api['pre_auth_code']['url'] . '?component_access_token=' . $component_access_token;
        $res = $this->getResult($url,$data);

        return $res["pre_auth_code"];

    }

    /**
     * 获取授权方接口调用凭证
     * @param $authorization_code
     * @return $authorization_info
     */
    public function getAuthorizationAccessToken($component_access_token, $authorization_code){
        $data = [
            'component_appid'       => Config::get('component_appid'),
            'authorization_code'    => $authorization_code
        ];
        $url = self::$api['authorization_access_token']['url'] . "?component_access_token=" . $component_access_token;
        $res = $this->getResult($url,$data);

        return $res["authorization_info"];
    }

    /**
     * 获取刷新令牌
     * @param $component_access_token
     * @param $appid
     * @param $authorizer_refresh_token
     * @return array()
     */
    public function getAuthorizationRefreshToken($component_access_token, $appid, $authorizer_refresh_token){
        $data = [
            'component_appid'           => Config::get('component_appid'),
            'authorizer_appid'          => $appid,
            'authorizer_refresh_token'  => $authorizer_refresh_token,
        ];

        $url = self::$api['authorization_refresh_token']['url'] . "?component_access_token=" . $component_access_token;
        $res = $this->getResult($url,$data);

        return $res;
    }

    /**
     * 获取授权方基本信息
     * @param $component_access_token
     * @param $appid
     * @return mixed
     * @throws Exception
     */
    public function getAuthorizerInfo($component_access_token, $appid){
        $data = [
            'component_appid'   => Config::get('component_appid'),
            'authorizer_appid'  => $appid
        ];
        $url = self::$api['authorizer_info']['url'] . "?component_access_token=" . $component_access_token;
        $res = $this->getResult($url,$data);

        return $res["authorizer_info"];
    }

    /**
     * 创建菜单
     * @param $access_token
     * @param $appid
     * @return array
     */
    public function createMenu($access_token, $data){
        $url = self::$api['create_menu']['url'] . "?access_token=$access_token";
        $res = $this->getResult($url,$data);

        return $res;
    }

    /**
     * 创建菜单
     * @param $access_token
     * @return mixed
     * @throws Exception
     */
    public function getMenu($access_token){
        $data= [
            'access_token' => $access_token
        ];

        $url = self::$api['get_menu']['url'];
        $res = $this->getResult($url,$data,"get");

        return $res["menu"];
    }

    /**
     * 删除菜单
     * @param $access_token
     * @return mixed
     * @throws Exception
     */
    public function delMenu($access_token){
        $data= [
            'access_token' => $access_token
        ];
        $url = self::$api['del_menu']['url'];
        $res = $this->getResult($url,$data,"get");

        return $res;
    }

    /**
     * 获取关注者列表
     * @param $access_token
     * @param $next_openid
     * @return mixed
     * @throws Exception
     */
    public function userGet($access_token, $next_openid = ''){
        $data = [
                'access_token'  => $access_token,
                'next_openid'   => $next_openid
            ];
        $url = self::$api['user_get']['url'];
        $res = $this->getResult($url,$data,"get");

        return $res;
    }

    /**
     * 获取用户基本信息
     * @param $access_token
     * @param $openid
     * @return mixed
     * @throws Exception
     */
    public function getUserInfo($access_token, $openid){
        $data= [
            'access_token'  => $access_token,
            'openid'        => $openid,
            'lang'          => 'zh_CN',
        ];
        $url = self::$api['user_info']['url'];
        $res = $this->getResult($url,$data,"get");

        return $res;
    }

    /**
     * 批量获取用户信息
     * @param $access_token
     * @param $post
     * @return mixed
     * @throws Exception
     */
    public function batchgetUserInfo($access_token, $post){
        $url = self::$api['user_info_batchget']['url'] . "?access_token=$access_token";
        $res = $this->getResult($url,$post);

        return $res;
    }

    /**
     * 上传永久素材
     * @param $access_token
     * @param $type
     * @return mixed
     * @throws Exception
     */
    public function addMaterial($access_token, $type, $file_path, $title = '', $introduce = ''){
        $url = self::$api['add_material']['url'] . "?access_token=$access_token&type=$type";
        $data = null;
        switch ($type){
            case 'image':
                $data = ['media' => '@'. $file_path];
                break;
            case 'voice':
                $data = ['media' => '@'. $file_path];
                break;
            case 'video':
                $data = [
                    'media' => '@'. $file_path,
                    'description'   => json_encode(['title' => $title, 'introduction' => $introduce], JSON_UNESCAPED_UNICODE),
                ];
                break;
            case 'thumb':
                $data = ['media' => '@'. $file_path];
                break;
            default:;
        }

        $res = $this->getResult($url,$data);

        return $res;
    }

    /**
     * 上传图文消息图片
     * @param $access_token
     * @param $file_path
     * @return mixed
     * @throws Exception
     */
    public function uploadimg($access_token, $file_path){

        $data = [
                'media' => '@' . $file_path
             ];
        $url = self::$api['uploadimg']['url'] . "?access_token=$access_token";

        $res = $this->getResult($url,$data);

        return $res;
    }

    /**
     * 获取素材列表
     * @param $access_token
     * @param $type
     * @param int $offset
     * @param int $count
     * @return mixed
     * @throws Exception
     */
    public function batchgetMaterial($access_token, $type, $offset = 0, $count = 20){
        $data = [
            'type'  => $type,
            'offset'    => $offset,
            'count' => $count
        ];

        $url = self::$api['batchget_material']['url'] . '?access_token=' . $access_token;
        $res = $this->getResult($url,$data);

        return $res;
    }

    /**
     * 获取临时素材
     * @param $access_token
     * @param $media_id
     * @return mixed
     * @throws Exception
     */
    public function getTmpMaterial($access_token, $media_id){
        $data = [
            'access_token'  => $access_token,
            'media_id'      => $media_id
        ];
        $url  = self::$api['get_tmp_material']['url'];
        $res  = $this->getResult($url,$data);

        return $res;
    }

    /**
     * 获取永久素材
     * @param $access_token
     * @param $media_id
     * @return mixed
     * @throws Exception
     */
    public function getMaterial($access_token, $media_id){
        $data   = ['media_id' => $media_id];
        $url    = self::$api['get_material']['url'] . '?access_token=' . $access_token;
        $res    = $this->getResult($url,$data);

        return $res;
    }

    /**
     * 删除永久素材
     * @param $access_token
     * @param $media_id
     * @return mixed
     * @throws Exception
     */
    public function delMaterial($access_token, $media_id){
        $data   = ['media_id'  => $media_id];
        $url    = self::$api['del_material']['url'] . '?access_token=' . $access_token;
        $res    = $this->getResult($url,$data);

        return $res;
    }

    /**
     * 获取jsapid_ticket
     * @param $access_token
     * @return mixed|null
     * @throws Exception
     */
    public function getJsapiTicket($access_token){
        $data   = [
            'access_token' => $access_token,
            'type' => 'jsapi'
        ];
        $url = self::$api['get_jsapi_ticket']['url'];
        $res = $this->getResult($url,$data,"get");

        return $res;
    }

    /**
     * 获取模板列表
     * @param $access_token
     * @return mixed|null
     * @throws Exception
     */
    public function getAllPrivateTemplate($access_token){
        $data = ['access_token' => $access_token];
        $url = self::$api['get_all_private_template']['url'];
        $res = $this->getResult($url,$data);

        return $res;
    }

    /**
     * 发送模板消息
     * @param $access_token
     * @param $data
     * @return mixed|null
     * @throws Exception
     */
    public function templateSend($access_token, $data){
        $url = self::$api['template_send']['url'] . '?access_token='. $access_token;
        $res = $this->getResult($url,$data);

        return $res;
    }

    /**
     * 获取短链接
     * @param $access_token
     * @param $long_url
     * @return mixed|null
     * @throws Exception
     */
    public function shorturl($access_token, $long_url){
        $data = [
            'access_token'  => $access_token,
            'action'        => 'long2short',
            'long_url'      => $long_url
        ];

        $url = self::$api['shorturl']['url'] . '?access_token=' . $access_token;
        $res = $this->getResult($url,$data);

        return $res;
    }

    /**
     * 生成二维码
     * @param $access_token
     * @return mixed|null
     * @throws Exception
     */
    public function qrcode($access_token, $type = 'QR_STR_SCENE', $expire = 2592000, $str = ''){
        $data = [
            'expire_seconds'  => $expire,
            'action_name'   => $type,
            'action_info'   => [
                'scene' => ['scene_str' => strval($str)]
            ]
        ];
        $url = self::$api['qrcode']['url'] . '?access_token=' . $access_token;
        $res = $this->getResult($url,$data);

        return $res;
    }

    /**
     * 添加客服
     * @param $access_token
     * @param $nickname
     * @param $account
     * @param $password
     * @return mixed|null
     * @throws Exception
     */
    public function addKfaccount($access_token, $nickname, $account){
        $data = [
            'kf_account'  => $account,
            'nickname'   => $nickname,
        ];

        $url = self::$api['kfaccount_add']['url'] . '?access_token=' . $access_token;
        $res = $this->getResult($url,$data);

        return $res;
    }

    /**
     * 修改客服
     * @param $access_token
     * @param $nickname
     * @param $account
     * @param $password
     * @return mixed|null
     * @throws Exception
     */
    public function editKfaccount($access_token, $nickname, $account){
        $data = [
            'kf_account'  => $account,
            'nickname'   => $nickname,
        ];
        $url = self::$api['kfaccount_edit']['url'] . '?access_token=' . $access_token;
        $res = $this->getResult($url,$data);

        return $res;
    }

    /**
     * 删除客服
     * @param $access_token
     * @param $nickname
     * @param $account
     * @param $password
     * @return mixed|null
     * @throws Exception
     */
    public function delKfaccount($access_token, $account){
        $data = [
            'access_token'  => $access_token,
            'kf_account'  => $account,
        ];
        $url = self::$api['kfaccount_del']['url'];
        $res = $this->getResult($url,$data);

        return $res;
    }

    /**
     * 获取客服列表
     * @param $access_token
     * @return mixed|null
     * @throws Exception
     */
    public function getkflist($access_token){
        $data = null;
        $url = self::$api['getkflist']['url'] . '?access_token=' . $access_token;
        $res = $this->getResult($url,$data,"get");

        return $res;
    }

    /**
     * 发送客服消息
     * @param $access_token
     * @param $openid
     * @param $content
     * @param string $type
     */
    public function customSend($access_token, $openid, $type = 'text', $content){
        $data = [
            'touser'    => $openid,
            'msgtype'   => $type,
            $type  => $content,
        ];

        $url = self::$api['custom_send']['url'] . '?access_token=' . $access_token;
        $res = $this->getResult($url,$data);

        return $res;
    }

    /**
     * 邀请绑定客服
     * @param $access_token
     * @param $kf_account
     * @param $invite_wx
     * @return mixed|null
     * @throws Exception
     */
    public function kfaccountInviteworker($access_token, $kf_account, $invite_wx){
        $data = [
            'kf_account'    => $kf_account,
            'invite_wx'   => $invite_wx,
        ];
        $url = self::$api['kfaccount_inviteworker']['url'] . '?access_token=' . $access_token;
        $res = $this->getResult($url,$data);

        return $res;
    }

    /**
     * 上传客服头像
     * @param $access_token
     * @param $kf_account
     * @param $tmp_name
     * @param $type
     * @param $file_name
     * @return mixed|null
     * @throws Exception
     */
    public function uploadheadimg($access_token, $kf_account, $tmp_name){
        $this->_curl->setOpt(CURLOPT_SAFE_UPLOAD, true);
        $url = self::$api['uploadheadimg']['url'] . "?access_token=$access_token&kf_account=$kf_account";
        $tmp_name = new \CURLFile($tmp_name);
        $data = [
            'media' => $tmp_name,
        ];
        $this->_curl->post($url, $data);
        return $this->result();
    }

    /**
     * 获取客服聊天记录
     * @param $access_token
     * @param $start
     * @param $end
     * @param int $msg_id
     * @param int $number
     * @return mixed|null
     * @throws Exception
     */
    public function getmsglist($access_token, $start, $end, $msg_id = 1, $number = 100){
        $data = [
            'starttime' => $start,
            'endtime'   => $end,
            'msgid' => $msg_id,
            'number'    => $number,
        ];
        $url = self::$api['getmsglist']['url'] . "?access_token=$access_token";
        $res = $this->getResult($url,$data);

        return $res;
    }

    /**
     * 换取二维码
     * @param $ticket
     * @return null
     * @throws Exception
     */
    public function showQrcode($ticket){
        $url = self::$api['showqrcode']['url'];
        $this->_curl->get($url, [
            'ticket'    => $ticket
        ]);
        if ($this->_curl->httpStatusCode == 200)
            return $this->_curl->response;
        else
            throw new Exception($this->_curl->errorMessage);
    }

    /**
     * 获取access_token
     * @return mixed
     */
    public function accessToken(){
        $appid      = config("app.appid");
        $appSecret  = config("app.appsecret");
        $strKey     = "_accessToken_global";
        $res     = Cache::get($appid.$strKey);

        // 从缓存获取
        if(isset($res['access_token']) && $res['expires_time'] > time()) {
            return $res['access_token'];
        }
        $result = $this->getAccessToken($appid,$appSecret);
        Cache::forever($appid.$strKey,[
            'access_token' => $result['access_token'],
            'expires_time' => time() + $result['expires_in'] - 100
        ]);
        return $result["access_token"];
    }


    /**
     * (非第三方)获取全局access_token
     * @param $appid
     * @param $secret
     * @return mixed
     */
    public function getAccessToken($appid, $secret)
    {
        $data = [
            'appid'     => $appid,
            'secret'    => $secret,
            'grant_type'    => 'client_credential',
        ];

        $url = self::$api['get_access_token']['url'];
        $res = $this->getResult($url,$data,"get");

        return $res;
    }


    /**
     * (非第三方)获取access_token
     * @param $appid
     * @param $secret
     * @param $code
     * @return mixed|null
     * @throws Exception
     */
    public function snsOauth2AccessToken($appid, $secret, $code){
        $data = [
            'appid'     => $appid,
            'secret'    => $secret,
            'code'      => $code,
            'grant_type'    => 'authorization_code',
        ];
        $url = self::$api['sns_oauth2_access_token']['url'];
        $res = $this->getResult($url,$data,"get");

        return $res;
    }

    /**
     * (非第三方)刷新网页授权access_token
     * @param $appid
     * @param $refresh_token
     * @return mixed
     * @throws Exception
     */
    public function snsOauth2RefreshToken($appid, $refresh_token){
        $url  = WxApi::$api['sns_oauth2_refresh_token']['url'];
        $data = [
            'appid'         => $appid,
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refresh_token,
        ];

        $res = $this->getResult($url,$data,"get");

        return $res;
    }

    /**
     * (非第三方)拉取用户信息(需scope为snsapi_userinfo)
     * @param $openid
     * @param $accessToken
     * @return mixed
     */
    public function snsUserInfo($openid,$accessToken)
    {
        $url  = WxApi::$api['sns_userinfo']['url'];
        $data = [
            'access_token'  => $accessToken,
            'openid'        => $openid,
            'lang'          => 'zh_CN',
        ];

        $res = $this->getResult($url,$data,"get");

        return $res;
    }

    /**
     * 获取开插件wifi_token
     * @param $access_token
     * @param $callback_url
     * @return mixed|null
     * @throws Exception
     */
    public function getWifiToke($access_token, $callback_url){
        $data = ['callback_url'  => $callback_url];

        $url = self::$api['get_wifi_toke']['url'] . "?access_token=$access_token";
        $res = $this->getResult($url, $data);

        return $res;
    }



    /**
     * 验证接口请求结果
     * @return mixed|null
     * @throws Exception
     */
    public function result(){
        if ($this->_curl->error){
            throw new Exception($this->_curl->errorMessage);
        }else{
            $result = $this->_curl->response;
        }
        $result = is_object($result) ? $this->object_to_array($result) : json_decode($result, true);
        if (isset($result['errcode']) && $result['errcode'] != 0) throw new Exception("{$result['errcode']}:{$result['errmsg']}");
        return $result;
    }

    /**
     * 记录微信错误日志
     * @param $name
     * @param string $err_result
     */
    public static function errLog($name, $err_result, $url, $post = ''){
        DB::table('wx_errlog')->insert([
            'name'  => $name,
            'url'   => $url,
            'post'  => $post,
            'err_result'    => $err_result,
            'create_time'   => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * 获取结果
     * @param $url
     * @param $post
     * @return mixed
     * @throws Exception
     */
    public function getResult($url,$requestData = null,$requestType = "post")
    {
        $data = !empty($requestData) && $requestType == "post" ? json_encode($requestData, JSON_UNESCAPED_UNICODE):$requestData;

        $res    = $requestType == "get" ? $this->_curl->get($url, $data) : $this->_curl->post($url, $data);


        if ($this->_curl->error)
            throw new Exception($this->_curl->errorMessage);

        $res = is_object($res) ? $this->object_to_array($res) : json_decode($res, true);


        if (isset($res['errcode']) && $res['errcode'] != 0)
        {
            //file_put_contents("../storage/res.txt",json_encode($res));
            throw new Exception("{$res['errcode']}:{$res['errmsg']}");
        }


        return $res;
    }


    public function __destruct(){
        $this->_curl->close();
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
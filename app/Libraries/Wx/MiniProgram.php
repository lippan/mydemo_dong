<?php

namespace App\Libraries\Wx;

use Curl\Curl;
use think\Config;
use think\Db;
use think\Exception;

/**
 *  微信小程序接口类
 * Class WxApi
 * @package weixin
 */
class MiniProgram{

    /**
     * 小程序接口
     * @var array
     */
    public static $wxapi = [
        'modify_domain'  => ['name' => '设置小程序服务器域名', 'url' => 'https://api.weixin.qq.com/wxa/modify_domain'],
        'setwebviewdomain'  => ['name' => '设置小程序业务域名', 'url' => 'https://api.weixin.qq.com/wxa/setwebviewdomain'],
        'bind_tester'   => ['name' => '绑定体验者', 'url' => 'https://api.weixin.qq.com/wxa/bind_tester'],
        'unbind_tester' => ['name' => '解绑体验者', 'url' => 'https://api.weixin.qq.com/wxa/unbind_tester'],
        'memberauth'    => ['name' => '获得体验者列表', 'url' => 'https://api.weixin.qq.com/wxa/memberauth'],
        'commit'  => ['name' => '上传小程序代码', 'url' => 'https://api.weixin.qq.com/wxa/commit'],
        'get_qrcode'  => ['name' => '获取小程序体验二维码', 'url' => 'https://api.weixin.qq.com/wxa/get_qrcode'],
        'gettemplatedraftlist'  => ['name' => '获取草稿箱内的所有临时代码草稿', 'url' => 'https://api.weixin.qq.com/wxa/gettemplatedraftlist'],
        'gettemplatelist'   => ['name' => '获取代码库中代码模板', 'url' => 'https://api.weixin.qq.com/wxa/gettemplatelist'],
        'addtotemplate'  => ['name' => '将草稿箱的草稿选为小程序代码模版', 'url' => 'https://api.weixin.qq.com/wxa/addtotemplate'],
        'deletetemplate'  => ['name' => '删除指定小程序代码模版', 'url' => 'https://api.weixin.qq.com/wxa/deletetemplate'],
        'get_category'  => ['name' => '获取小程序类目', 'url' => 'https://api.weixin.qq.com/wxa/get_category'],
        'get_page'  => ['name' => '获取小程序页面配置', 'url' => 'https://api.weixin.qq.com/wxa/get_page'],
        'jscode2session'    => ['name' => 'code换取session_key', 'url' => 'https://api.weixin.qq.com/sns/component/jscode2session'],
        'template_send'  => ['name' => '发送模版消息', 'url' => 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send'],
        'getaccountbasicinfo'  => ['name' => '获取小程序基本信息', 'url' => 'https://api.weixin.qq.com/cgi-bin/account/getaccountbasicinfo'],
        'submit_audit'  => ['name' => '代码包提交审核', 'url' => 'https://api.weixin.qq.com/wxa/submit_audit'],
        'get_auditstatus'  => ['name' => '查询审核状态', 'url' => 'https://api.weixin.qq.com/wxa/get_auditstatus'],
        'release'  => ['name' => '发布小程序', 'url' => 'https://api.weixin.qq.com/wxa/release'],
        'createwxaqrcode'  => ['name' => '生成小程序二维码', 'url' => 'https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode'],
        'getwxacodeunlimit'  => ['name' => '生成小程序码', 'url' => 'https://api.weixin.qq.com/wxa/getwxacodeunlimit'],
        'undocodeaudit'  => ['name' => '小程序审核撤回', 'url' => 'https://api.weixin.qq.com/wxa/undocodeaudit'],
        'uniform_send'  => ['name' => '统一服务消息', 'url' => 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/uniform_send'],
        'getwxamplinkforshow'  => ['name' => '获取可设置展示的公众号列表', 'url' => 'https://api.weixin.qq.com/wxa/getwxamplinkforshow'],
        'getshowwxaitem'  => ['name' => '获取展示公众号详情', 'url' => 'https://api.weixin.qq.com/wxa/getshowwxaitem'],
        'updateshowwxaitem'  => ['name' => '设置展示公众号', 'url' => 'https://api.weixin.qq.com/wxa/updateshowwxaitem'],
        'fastregisterweapp'  => ['name' => '创建小程序', 'url' => 'https://api.weixin.qq.com/cgi-bin/component/fastregisterweapp'],
        'upload_temp_media'  => ['name' => '上传临时素材', 'url' => 'https://api.weixin.qq.com/cgi-bin/media/upload'],
        'setnickname'  => ['name' => '设置小程序名称', 'url' => 'https://api.weixin.qq.com/wxa/setnickname'],
        'wxa_querynickname'  => ['name' => '改名审核状态查询', 'url' => 'https://api.weixin.qq.com/wxa/api_wxa_querynickname'],
        'checkwxverifynickname'  => ['name' => '微信认证名称检测', 'url' => 'https://api.weixin.qq.com/cgi-bin/wxverify/checkwxverifynickname'],
        'modifyheadimage'  => ['name' => '修改小程序头像', 'url' => 'https://api.weixin.qq.com/cgi-bin/account/modifyheadimage'],
        'img_sec_check'  => ['name' => '图片鉴黄', 'url' => 'https://api.weixin.qq.com/wxa/img_sec_check'],
        'msg_sec_check'  => ['name' => '内容鉴黄', 'url' => 'https://api.weixin.qq.com/wxa/msg_sec_check'],
        'media_check_async'  => ['name' => '异步校验图片/音频是否违法', 'url' => 'https://api.weixin.qq.com/wxa/media_check_async'],
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

    /* ---------------------------------------------------------------------------------------------- 小程序接口 ----------------------------------------------------------------------------------------------*/

    /**
     * 设置小程序服务器域名
     * @param $access_token
     * @param string $action
     * @return mixed|null
     * @throws Exception
     */
    public function modifyDomain($access_token, $action = 'get', $post = []){
        $data = [
            'action' => $action,
        ];
        $url = self::$wxapi['modify_domain']['url'] . '?access_token=' . $access_token;
        $res = $this->getResult($url,array_merge($data, $post));

        return $res;
    }

    /**
     * 设置小程序业务域名
     * @param $access_token
     * @param string $action
     * @param array $post
     * @return mixed|null
     * @throws Exception
     */
    public function setwebviewdomain($access_token, $action = 'get', $post = []){
        $data = ['action' => $action];
        $url = self::$wxapi['setwebviewdomain']['url'] . '?access_token=' . $access_token;
        $res = $this->getResult($url,array_merge($data, $post));

        return $res;
    }

    /**
     * 绑定体验着
     */
    public function bindTester($access_token, $wechatid){
        $data = ['wechatid'  => $wechatid];
        $url = self::$wxapi['bind_tester']['url'] . "?access_token=$access_token";
        $res = $this->getResult($url,$data);

        return $res;
    }

    /**
     * 解绑体验者
     */
    public function unbindTester($access_token, $userstr){
        $data = ['userstr'  => $userstr];

        $url = self::$wxapi['unbind_tester']['url'] . "?access_token=$access_token";
        $res = $this->getResult($url,$data);

        return $res;
    }

    /**
     * 获得体验者列表
     */
    public function memberauth($access_token){
        $data = [
            'action'  => 'get_experiencer',
        ];

        $url = self::$wxapi['memberauth']['url'] . "?access_token=$access_token";

        $res = $this->getResult($url,$data);
        return $res;
    }

    /**
     * 上传小程序代码
     * @param $access_token
     * @param $data
     * @return mixed|null
     * @throws Exception
     */
    public function commit($access_token, $data){
        $url = self::$wxapi['commit']['url'] . "?access_token=$access_token";
        $res = $this->getResult($url,$data);

        return $res;
    }

    /**
     * 获得小程序体验二维码
     * @param $access_token
     * @param string $path
     * @return mixed|null
     * @throws Exception
     */
    public function getQrcode($access_token, $path = ''){
        $data = [
            'access_token'  => $access_token,
            'path'  => urlencode($path),
        ];
        $url = self::$wxapi['get_qrcode']['url'];

        $res = $this->getResult($url,$data,"get");
        return $res;
    }

    /**
     * 获取草稿箱内的所有临时代码草稿
     * @param $access_token
     * @return mixed|null
     * @throws Exception
     */
    public function gettemplatedraftlist($access_token){
        $data = [];
        $url = self::$wxapi['gettemplatedraftlist']['url'] . '?access_token=' . $access_token;

        $res = $this->getResult($url,$data,"get");
        return $res;
    }

    /**
     * 获取代码库中代码模板
     * @param $access_token
     * @return mixed|null
     * @throws Exception
     */
    public function gettemplatelist($access_token){
        $data = [];
        $url = self::$wxapi['gettemplatelist']['url'] . '?access_token=' . $access_token;
        $res = $this->getResult($url,$data,"get");

        return $res;
    }

    /**
     * 将草稿箱的草稿选为小程序代码模版
     * @param $access_token
     * @param $draft_id
     * @return mixed|null
     * @throws Exception
     */
    public function addtotemplate($access_token, $draft_id){
        $data = [
            'draft_id'  => $draft_id,
        ];
        $url = self::$wxapi['addtotemplate']['url'] . "?access_token=$access_token";

        $res = $this->getResult($url,$data);
        return $res;
    }

    /**
     * 删除指定小程序代码模版
     * @param $access_token
     * @param $template_id
     * @return mixed|null
     * @throws Exception
     */
    public function deletetemplate($access_token, $template_id){
        $data = [
            'template_id'  => $template_id,
        ];
        $url = self::$wxapi['deletetemplate']['url'] . "?access_token=$access_token";

        $res = $this->getResult($url,$data);
        return $res;
    }

    /**
     * 获取小程序类目
     * @param $access_token
     * @return mixed|null
     * @throws Exception
     */
    public function getCategory($access_token){
        $data=[];
        $url = self::$wxapi['get_category']['url'] . '?access_token=' . $access_token;

        $res = $this->getResult($url,$data,"get");
        return $res;
    }

    /**
     * 获取小程序类目
     * @param $access_token
     * @return mixed|null
     * @throws Exception
     */
    public function getPage($access_token){
        $data=[];
        $url = self::$wxapi['get_page']['url'] . '?access_token=' . $access_token;

        $res = $this->getResult($url,$data,"get");
        return $res;
    }

    /**
     * code换取session_key
     * @return mixed|null
     * @throws Exception
     */
    public function jscode2session($appid, $js_code, $component_appid, $component_access_token){
        $data=[];
        $url = self::$wxapi['jscode2session']['url'] . "?appid={$appid}&js_code={$js_code}&grant_type=authorization_code&component_appid={$component_appid}&component_access_token={$component_access_token}";

        $res = $this->getResult($url,$data,"get");
        return $res;
    }

    /**
     * 发送小程序模版消息
     * @param $access_token
     * @param $data
     */
    public function wxaTemplateSend($access_token, $data){
        $url = self::$wxapi['template_send']['url'] . "?access_token=$access_token";
        $res = $this->getResult($url,$data);
        return $res;
    }

    /**
     * 发送统一服务消息
     * @param $access_token
     * @param $data
     * @return mixed|null
     * @throws Exception
     */
    public function uniformSend($access_token, $data){
        $url = self::$wxapi['uniform_send']['url'] . "?access_token=$access_token";
        $res = $this->getResult($url,$data);
        return $res;
    }

    /**
     * 代码包提交审核
     * @param $access_token
     * @param $data
     * @return mixed|null
     * @throws Exception
     */
    public function submitAudit($access_token, $data){
        $url = self::$wxapi['submit_audit']['url'] . "?access_token=$access_token";
        $res = $this->getResult($url,$data);
        return $res;
    }

    /**
     * 查询指定版本审核状态
     * @param $access_token
     * @param $auditid
     * @return mixed|null
     * @throws Exception
     */
    public function getAuditstatus($access_token, $auditid){
        $data=['auditid' => $auditid];
        $url = self::$wxapi['get_auditstatus']['url'] . "?access_token=$access_token";

        $res = $this->getResult($url,$data);
        return $res;
    }

    /**
     * 发布小程序
     * @param $access_token
     * @return mixed|null
     * @throws Exception
     */
    public function release($access_token){
        $url = self::$wxapi['release']['url'] . "?access_token=$access_token";
        $this->_curl->post($url, '{}');
        return $this->result();
    }

    /**
     * 小程序审核撤回
     * @param $access_token
     * @return mixed|null
     * @throws Exception
     */
    public function undocodeaudit($access_token){
        $data=[];
        $url = self::$wxapi['undocodeaudit']['url'] . "?access_token=$access_token";

        $res = $this->getResult($url,$data,"get");
        return $res;
    }

    /**
     * 生成小程序二维码
     * @param $access_token
     * @return mixed|null
     * @throws Exception
     */
    public function createwxaqrcode($access_token, $path, $width){
        $data = ['path' => $path, 'width' => $width];
        $url = self::$wxapi['createwxaqrcode']['url'] . "?access_token=$access_token";

        $res = $this->getResult($url,$data);
        return $res;
    }

    /**
     * 生成带参数的小程序码(数量不限)
     * @param $access_token
     * @param $data
     * @return null
     * @throws Exception
     */
    public function getwxacodeunlimit($access_token, $data){
        $url = self::$wxapi['getwxacodeunlimit']['url'] . "?access_token=$access_token";

        $res = $this->getResult($url,$data);
        return $res;
    }

    /**
     * 获取小程序基本信息
     * @param $access_token
     * @return mixed|null
     * @throws Exception
     */
    public function getaccountbasicinfo($access_token){
        $data=['access_token' => $access_token];
        $url = self::$wxapi['getaccountbasicinfo']['url'];

        $res = $this->getResult($url,$data,"get");
        return $res;
    }

    /**
     * 获取可展示公众号列表
     * @param $access_token
     * @return mixed|null
     * @throws Exception
     */
    public function getwxamplinkforshow($access_token, $page = 1, $num = 20){
        $page--;
        $page = max(0, $page);
        $data = ['access_token' => $access_token, 'page' => $page, 'num' => $num];
        $url = self::$wxapi['getwxamplinkforshow']['url'];

        $res = $this->getResult($url,$data,"get");
        return $res;
    }

    /**
     * 获取可展示公众号详情
     * @param $access_token
     * @return mixed|null
     * @throws Exception
     */
    public function getshowwxaitem($access_token){
        $data = ['access_token' => $access_token];
        $url = self::$wxapi['getshowwxaitem']['url'];

        $res = $this->getResult($url,$data,"get");
        return $res;
    }

    /**
     * 设置可展示公众号
     * @param $access_token
     * @param $appid
     * @param $wxa_subscribe_biz_flag
     * @return mixed|null
     * @throws Exception
     */
    public function updateshowwxaitem($access_token, $appid, $wxa_subscribe_biz_flag){
        $data = ['wxa_subscribe_biz_flag' => $wxa_subscribe_biz_flag, 'appid' => $appid];
        $url = self::$wxapi['updateshowwxaitem']['url'] . "?access_token=$access_token";

        $res = $this->getResult($url,$data);
        return $res;
    }

    /**
     * 创建小程序
     * @param $component_access_token
     * @param $data
     * @return mixed|null
     * @throws Exception
     */
    public function fastregisterweapp($component_access_token, $data){
        $url = self::$wxapi['fastregisterweapp']['url'] . "?action=create&component_access_token=$component_access_token";

        $res = $this->getResult($url,$data);
        return $res;
    }

    /**
     * 上传临时素材
     * @param $access_token
     * @param $file_path
     * @return mixed
     * @throws Exception
     */
    public function uploadTempMedia($access_token, $file_path){
        $data = ['media' => '@'. $file_path];
        $url = self::$wxapi['upload_temp_media']['url'] . "?access_token=$access_token&type=image";

        $res = $this->getResult($url,$data);
        return $res;
    }

    /**
     * 设置小程序名称
     * @param $access_token
     * @param $data
     * @return mixed|null
     * @throws Exception
     */
    public function setnickname($access_token, $data){
        $url = self::$wxapi['setnickname']['url'] . "?access_token=$access_token";

        $res = $this->getResult($url,$data);
        return $res;
    }

    /**
     * 查询改名审核状态
     * @param $access_token
     * @param $audit_id
     * @return mixed|null
     * @throws Exception
     */
    public function wxaQuerynickname($access_token, $audit_id){
        $data = ['audit_id' => $audit_id];
        $url = self::$wxapi['wxa_querynickname']['url'] . "?access_token=$access_token";

        $res = $this->getResult($url,$data);
        return $res;
    }

    /**
     * 微信认证名称检测
     * @param $access_token
     * @param $nickname
     * @return mixed|null
     * @throws Exception
     */
    public function checkwxverifynickname($access_token, $nickname){
        $data= ['nick_name' => $nickname];
        $url = self::$wxapi['checkwxverifynickname']['url'] . "?access_token=$access_token";

        $res = $this->getResult($url,$data);
        return $res;
    }

    /**
     * 修改小程序头像
     * @param $access_token
     * @return mixed|null
     * @throws Exception
     */
    public function modifyheadimage($access_token, $data){
        $url = self::$wxapi['modifyheadimage']['url'] . "?access_token=$access_token";

        $res = $this->getResult($url,$data);
        return $res;
    }

    /**
     * 内容鉴黄
     * @param $access_token
     * @param $data
     * @return mixed|null
     * @throws Exception
     */
    public function msg_sec_check($access_token, $pdata){
        $data = ['content' => $pdata];
        $url = self::$wxapi['msg_sec_check']['url'] . "?access_token=$access_token";

        $res = $this->getResult($url,$data);
        return $res;
    }

    /**
     * 图片鉴黄
     * @param $access_token
     * @param $img
     * @return mixed|null
     * @throws Exception
     */
    public function img_sec_check($access_token, $img){
        $data = ['media' => '@'. $img];
        $url = self::$wxapi['img_sec_check']['url'] . "?access_token=$access_token";

        $res = $this->getResult($url,$data);
        return $res;
    }

    /**
     * 图片/音频异步鉴黄
     * @param $access_token
     * @param $media_url
     * @param $media_type
     * @return mixed|null
     * @throws Exception
     */
    public function mediaCheckAsync($access_token, $media_url, $media_type){
        $data = ['media_url' => $media_url, 'media_type' => $media_type];
        $url = self::$wxapi['media_check_async']['url'] . "?access_token=$access_token";

        $res = $this->getResult($url,$data);
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
        $result = is_object($result) ? object_to_array($result) : json_decode($result, true);
        if (isset($result['errcode']) && $result['errcode'] != 0) throw new Exception("{$result['errcode']}:{$result['errmsg']}");
        return $result;
    }

    /**
     * 记录微信错误日志
     * @param $name
     * @param string $err_result
     */
    public static function errLog($name, $err_result, $url, $post = ''){
        Db::name('wx_errlog')->insert([
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
        $data = $requestType == "post" ? json_encode($requestData, JSON_UNESCAPED_UNICODE):$requestData;

        $res    = $requestType == "get" ? $this->_curl->get($url, $data) : $this->_curl->post($url, $data);
        if ($this->_curl->error)
            throw new Exception($this->_curl->errorMessage);

        $res = is_object($res) ? object_to_array($res) : json_decode($res, true);
        if (isset($res['errcode']) && $res['errcode'] != 0)
            throw new Exception("{$res['errcode']}:{$res['errmsg']}");

        return $res;
    }


    public function __destruct(){
        $this->_curl->close();
    }
}
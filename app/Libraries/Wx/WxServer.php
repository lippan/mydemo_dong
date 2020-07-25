<?php

namespace App\Libraries\Wx;
use Curl\MultiCurl;


/**
 * 微信服务类
 * Class WxServer
 * @package weixin
 */
class WxServer{

    public $wxApi;
    public $module;
    public static $msg_type = [
        'text'  => '文本',
        'image' => '图片',
        'voice' => '语音',
        'video' => '视频',
        'shortvideo'    => '小视频',
        'location'  => '地理位置',
        'link'  => '链接',
        'miniprogrampage'   => '小程序卡片'
    ];

    public function __construct($wxApi, $module = 'weixin'){
        $this->wxApi = $wxApi;
        $this->module = $module;
    }

    /**
     * 设置ticket
     * @param $ticket
     * @param string $module
     */
    public static function setVerifyTicket($ticket){
        Config::set('component_verify_ticket', $ticket);
    }

    /**
     * 确认授权
     * @param $arr
     * @return bool
     * @throws \think\Exception
     */
    public function authorized($arr){
        $datetime = date('Y-m-d H:i:s');
        $row  = Db::name('wx_authorizer_users')->where('appid', $arr['AuthorizerAppid'])->find();
        // 获取授权方基本信息
        $info = $this->wxApi->getAuthorizerInfo($this->getComponentAccessToken(), $arr['AuthorizerAppid']);
        $type = isset($info['MiniProgramInfo']) ? 2 : 1;

        $data = [
            'appid'             => $arr['AuthorizerAppid'],
            'is_authorizer'     => 1,
            'type'              => $type,
            'authorizer_time'   => $datetime,
            'nick_name'         => $info['nick_name'],
            'head_img'          => $info['head_img'],
            'service_type_info' => isset(WxApi::$service_type_info[$info['service_type_info']['id']]) ? WxApi::$service_type_info[$info['service_type_info']['id']] : '',
            'verify_type_info'  => isset(WxApi::$verify_type_info[$info['verify_type_info']['id']]) ? WxApi::$verify_type_info[$info['verify_type_info']['id']] : '',
            'user_name'         => $info['user_name'],
            'principal_name'    => $info['principal_name'],
            'qrcode_url'        => $info['qrcode_url'],
            'signature'         => isset($info['signature']) ? $info['signature'] : '',
        ];

        // 判断以前是否授权过
        if (empty($row))
        {
            $data["create_time"] = $datetime;
            $res = Db::name('wx_authorizer_users')->insert($data);
        }
        else
        {
            $data["update_time"] = $datetime;
            $res = Db::name('wx_authorizer_users')->where('appid', $arr['AuthorizerAppid'])->update($data);
        }


        // 配置小程序域名
        if ($type == 2){
            try{
                $this->setWeappDomain($arr['AuthorizerAppid']);
            }catch (Exception $e){
                Log::error($e->getMessage());
            }
        }

        return $res ? true : false;
    }

    /**
     * 更新授权
     * @param $arr
     * @return bool
     * @throws \think\Exception
     */
    public function updateauthorized($arr){
        $datetime = date('Y-m-d H:i:s');
        // 获取授权方基本信息
        $info = $this->wxApi->getAuthorizerInfo($this->getComponentAccessToken(), $arr['AuthorizerAppid']);
        $type = isset($info['MiniProgramInfo']) ? 2 : 1;
        $row = Db::name('wx_authorizer_users')->where('appid', $arr['AuthorizerAppid'])->find();

        $data = [
            'appid' => $arr['AuthorizerAppid'],
            'is_authorizer' => 1,
            'type'  => $type,
            'authorizer_time'   => $datetime,
            'nick_name' => $info['nick_name'],
            'head_img'  => $info['head_img'],
            'service_type_info' => isset(WxApi::$service_type_info[$info['service_type_info']['id']]) ? WxApi::$service_type_info[$info['service_type_info']['id']] : '',
            'verify_type_info'  => isset(WxApi::$verify_type_info[$info['verify_type_info']['id']]) ? WxApi::$verify_type_info[$info['verify_type_info']['id']] : '',
            'user_name' => $info['user_name'],
            'principal_name'    => $info['principal_name'],
            'qrcode_url'    => $info['qrcode_url'],
            'signature' => isset($info['signature']) ? $info['signature'] : '',
        ];
        // 判断以前是否授权过
        if (empty($row)){
            $data["create_time"] = $datetime;
            $res = Db::name('wx_authorizer_users')->insert($data);
        }else{
            $data["update_time"] = $datetime;
            $res = Db::name('wx_authorizer_users')->where('appid', $arr['AuthorizerAppid'])->update($data);
        }
        return $res ? true : false;
    }

    /**
     * 取消授权
     * @param $appid
     * @return int|string
     * @throws \think\Exception
     */
    public static function unauthorized($appid){
        $result = Db::name('wx_authorizer_users')->where('appid', $appid)->update([
            'is_authorizer' => 0,
            'admin_user_ids'=> '',
            'update_time'   => date('Y-m-d H:i:s')
        ]);
        return $result;
    }

    /**
     * 获取平台component_access_token
     * @return mixed
     * @throws \think\Exception
     */
    public function getComponentAccessToken(){
        // access_token过期时间
        $component_access_token_expire = Config::get('component_access_token_expire');
        // 缓存平台access_token
        if (time() < $component_access_token_expire)
            return Config::get('component_access_token');

        $result = $this->wxApi->getComponentAccessToken();
        $component_access_token = $result->component_access_token;

        // 缓存access_token
        Config::set('component_access_token', $component_access_token);
        // 缓存access_token过期时间
        Config::set('component_access_token_expire', time() + $result->expires_in - 10);

        return $component_access_token;
    }

    /**
     * 生成授权链接
     * @param $redirect_uri
     * @return string
     */
    public function authUrl($redirect_uri){
        $component_appid = Config::get('component_appid');
        $pre_auth_code = $this->wxApi->getPreAuthCode($this->getComponentAccessToken());
        $url = "https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid=$component_appid&pre_auth_code=$pre_auth_code&redirect_uri=$redirect_uri";
        return $url;
    }

    /**
     * 获取授权方access_token
     * @param $appid
     * @return bool|mixed
     * @throws \think\Exception
     */
    public function getAuthorizerAccessToken($appid){
        $row = Db::name('wx_access_token')->where('appid', $appid)->find();
        if (empty($row)) return false;
        // 从缓存获取token
        if (time() < $row['expire_time']) return $row['authorizer_access_token'];
        // 从接口获取token
        $result = $this->wxApi->getAuthorizationRefreshToken($this->getComponentAccessToken(), $appid, $row['authorizer_refresh_token']);
        Db::name('wx_access_token')->where('appid', $appid)->update([
            'authorizer_access_token'   => $result->authorizer_access_token,
            'authorizer_refresh_token'  => $result->authorizer_refresh_token,
            'expire_time'   => time() + $result->expires_in - 100,
        ]);
        return $result->authorizer_access_token;
    }

    /**
     * 通过授权码设置access_token
     * @param $auth_code
     * @return mixed
     * @throws Exception
     */
    public function setAuthorizerAccessToken($auth_code){
        $result = $this->wxApi->getAuthorizationAccessToken($this->getComponentAccessToken(), $auth_code);

        // 判断access_token缓存是否存在
        $wx_access_token = Db::name('wx_access_token')->where('appid', $result->authorizer_appid)->find();
        if (empty($wx_access_token)){
            $res = Db::name('wx_access_token')->insert([
                'appid' => $result->authorizer_appid,
                'authorizer_access_token'   => $result->authorizer_access_token,
                'authorizer_refresh_token'  => $result->authorizer_refresh_token,
                'expire_time'    => $result->expires_in + time() - 100,
            ]);
        }else{
            $res = Db::name('wx_access_token')->where('appid', $result->authorizer_appid)->yycache(true)->update([
                'authorizer_access_token'   => $result->authorizer_access_token,
                'authorizer_refresh_token'  => $result->authorizer_refresh_token,
                'expire_time'    => $result->expires_in + time() - 100,
            ]);
        }
        if (!$res) throw new Exception('设置access_token失败');
        return $result->authorizer_access_token;
    }

    /**
     * 创建小程序
     * @param $arr
     * @throws Exception
     */
    public function createWeapp($arr){
        $appid = $arr['appid'];
        $auth_code = $arr['auth_code'];
        $datetime = date('Y-m-d H:i:s');
        Db::startTrans();
        // 注册成功
        if ($arr['status'] == 0){
            try{
                $this->setAuthorizerAccessToken($auth_code);    // 设置access_token
                $info = $this->wxApi->getAuthorizerInfo($this->getComponentAccessToken(), $appid);  // 获取小程序信息
            }catch (Exception $e){
                Log::error($e->getMessage());
                return false;
            }

            // 创建后台管理员
            $admin_id = Db::name('admin_users')->where('username', $arr['info']['legal_persona_wechat'])->value('id');
            if (!$admin_id){
                $salt = substr(uniqid(), -6);
                $password = md5($salt . md5(123456));
                $admin_id = Db::name('admin_users')->insertGetId([
                    'username'  => $arr['info']['legal_persona_wechat'],
                    'password'  => $password,
                    'salt'  => $salt,
                    'name'  => $arr['info']['legal_persona_name'],
                    'role_id'   => 6,
                    'status'    => 1,
                    'create_time'   => $datetime,
                ]);
                if (!$admin_id){
                    Log::error('创建管理员失败');
                    return false;
                }
            }

            // 创建小程序
            $res = Db::name('wx_authorizer_users')->insert([
                'appid' => $appid,
                'is_authorizer' => 1,
                'type'  => 2,
                'authorizer_time'   => $datetime,
                'create_time'   => $datetime,
                'nick_name' => isset($info['nick_name']) ? $info['nick_name'] : '',
                'head_img'  => isset($info['head_img']) ? $info['head_img'] : '',
                'service_type_info' => isset(WxApi::$service_type_info[$info['service_type_info']['id']]) ? WxApi::$service_type_info[$info['service_type_info']['id']] : '',
                'verify_type_info'  => isset(WxApi::$verify_type_info[$info['verify_type_info']['id']]) ? WxApi::$verify_type_info[$info['verify_type_info']['id']] : '',
                'user_name' => $info['user_name'],
                'principal_name'    => $info['principal_name'],
                'qrcode_url'    => $info['qrcode_url'],
                'signature' => isset($info['signature']) ? $info['signature'] : '',
                'admin_user_ids' => $admin_id,
            ]);
            if (!$res){
                Db::rollback();
                Log::error('创建小程序失败');
                return false;
            }
        }
        // 更新注册申请结果
        $res = Db::name('weapp_apply')->where('code', $arr['info']['code'])->where('status', 0)->update([
            'appid' => $appid,
            'status'    => 1,
            'result_code'   => $arr['status'],
            'msg'   => $arr['msg'],
            'update_time'   => $datetime,
        ]);
        if (!$res){
            Db::rollback();
            Log::error('更新注册结果失败');
            return false;
        }
        // 设置小程序域名
        try{
            $this->setWeappDomain($appid);
        }catch (Exception $e){
            Log::error($e->getMessage());
        }

        Db::commit();
        return true;
    }

    /**
     * 回复普通消息
     * @param $appid
     * @param $msg
     * @return mixed|string
     */
    public function subscribeReply($appid, $timeStamp, $nonce, $msg){
        $xml = Db::name('wx_msg_event')->where('appid', $appid )->value('subscribe_reply');
        if (empty($xml)) return '';
        $arr = xml_to_array($xml);
        if (!isset($arr['MsgType'])) return '';
        switch ($arr['MsgType']){
            case 'text':
                if (!isset($arr['Content']) || empty($arr['Content'])) return '';
                break;
            case 'image':
                if (!isset($arr['Image']) || empty($arr['Image'])) return '';
                break;
            case 'video':
                if (!isset($arr['Video']) || empty($arr['Video'])) return '';
                break;
            default:
                return '';
        }
        $xml = sprintf($xml, $msg['FromUserName'], $msg['ToUserName'], $msg['CreateTime']);
        $encrypt_type = input('encrypt_type', '');
        if ($encrypt_type == 'aes'){
            $xml = $this->encrypt($xml, $timeStamp, $nonce);
        }else{
            return '';
        }
        return $xml;
    }

    /**
     * 关键词回复
     * @param $appid
     * @param $timeStamp
     * @param $nonce
     * @param $msg
     * @return string
     */
    public function keywordReply($appid, $timeStamp, $nonce, $msg){
        $reply = Db::name('wx_keyword_reply')->where('appid', $appid)->where('keyword', $msg['Content'])->find();
        if (empty($reply)) return '';
        // 记录关键词触发次数
        Db::name('wx_keyword_reply')->where('appid', $appid)->where('keyword', $msg['Content'])->setInc('trigger_num');
        switch ($reply['reply_msg_type']){
            case 'text':
                $xml = array_to_xml([
                    'ToUserName'    => '%s',
                    'FromUserName'  => '%s',
                    'CreateTime'    => '%s',
                    'MsgType'       => 'text',
                    'Content'       => $reply['reply_msg'],
                ]);
                break;
            case 'image':
                $xml = array_to_xml([
                    'ToUserName'    => '%s',
                    'FromUserName'  => '%s',
                    'CreateTime'    => '%s',
                    'MsgType'       => 'image',
                    'Image'       => ['MediaId' => $reply['reply_msg']],
                ]);
                break;
            case 'video':
                $info = Db::name('resources')->where('id', $reply['resource_id'])->value('info');
                $info = json_decode($info, true);
                $xml = array_to_xml([
                    'ToUserName'    => '%s',
                    'FromUserName'  => '%s',
                    'CreateTime'    => '%s',
                    'MsgType'       => 'video',
                    'Video'       => ['MediaId' => $reply['reply_msg'], 'Title' => $info['title'], 'Description' => $info['introduce']],
                ]);
                break;
            default:
                return '';
        }
        $xml = sprintf($xml, $msg['FromUserName'], $msg['ToUserName'], $msg['CreateTime']);
        $encrypt_type = input('encrypt_type', '');
        if ($encrypt_type == 'aes'){
            $xml = $this->encrypt($xml, $timeStamp, $nonce);    // 加密xml
        }else{
            return '';
        }
        return $xml;
    }

    /**
     * AI自动回复
     * @param $appid
     * @param $timeStamp
     * @param $nonce
     * @param $msg
     * @return string
     */
    public function aiReply($appid, $timeStamp, $nonce, $msg){
        try{
            $result = XfAi::getInstance()->aiui(['auth_id' => md5($msg['FromUserName'])], $msg['Content']);
        }catch (Exception $e){
            file_put_contents(UPLOAD_PATH . 'ai.log', $e->getMessage()."\n");
            return '';
        }
        if (!isset($result['data'][0]['intent']['answer']['text'])){
            return '';
        }
        $xml = array_to_xml([
            'ToUserName'    => '%s',
            'FromUserName'  => '%s',
            'CreateTime'    => '%s',
            'MsgType'       => 'text',
            'Content'       => $result['data'][0]['intent']['answer']['text'],
        ]);
        $xml = sprintf($xml, $msg['FromUserName'], $msg['ToUserName'], $msg['CreateTime']);
        $encrypt_type = input('encrypt_type', '');
        if ($encrypt_type == 'aes'){
            $xml = $this->encrypt($xml, $timeStamp, $nonce);    // 加密xml
        }else{
            return '';
        }
        return $xml;
    }

    /**
     * 自动回复
     * @param $appid
     * @param $timeStamp
     * @param $nonce
     * @param $msg
     * @return string
     * @throws \think\Exception
     */
    public function autoReply($appid, $timeStamp, $nonce, $msg){
        $xml = Db::name('wx_msg_event')->where('appid', $appid )->value('auto_reply');
        if (empty($xml)) return '';
        $arr = xml_to_array($xml);
        if (!isset($arr['MsgType'])) return '';
        switch ($arr['MsgType']){
            case 'text':
                if (!isset($arr['Content']) || empty($arr['Content'])) return '';
                break;
            case 'image':
                if (!isset($arr['Image']) || empty($arr['Image'])) return '';
                break;
            case 'video':
                if (!isset($arr['Video']) || empty($arr['Video'])) return '';
                break;
            default:
                return '';
        }
        $xml = sprintf($xml, $msg['FromUserName'], $msg['ToUserName'], $msg['CreateTime']);
        $encrypt_type = input('encrypt_type', '');
        if ($encrypt_type == 'aes'){
            $xml = $this->encrypt($xml, $timeStamp, $nonce);
        }else{
            return '';
        }
        return $xml;
    }

    /**
     * 更新微信用户
     * @param $appid
     * @param $openid
     * @return bool
     * @throws \think\Exception
     */
    public function updateUser($appid, $openid){
        $user = $this->wxApi->getUserInfo($this->getAuthorizerAccessToken($appid), $openid);
        $count = Db::name('wx_users')->where('appid', $appid)->where('openid', $openid)->count('id');
        $unionid = isset($user['unionid']) ? $user['unionid'] : '';
        if ($count > 0){
            $result = Db::name('wx_users')->where('appid', $appid)->where('openid', $openid)->update([
                'subscribe' => $user['subscribe'],
                'nickname'  => $user['nickname'],
                'sex'   => $user['sex'],
                'country'   => $user['country'],
                'province'  => $user['province'],
                'city'  => $user['city'],
                'headimgurl'    => $user['headimgurl'],
                'subscribe_time'    => date('Y-m-d H:i:s', $user['subscribe_time']),
                'unionid'   => $unionid,
                'remark'    => $user['remark'],
                'groupid'   => $user['groupid'],
                'subscribe_scene'   => isset($user['subscribe_scene']) ? $user['subscribe_scene'] : '',
                'update_time'   => date('Y-m-d H:i:s')
            ]);
        }else{
            $result = Db::name('wx_users')->insert([
                'appid' => $appid,
                'subscribe' => $user['subscribe'],
                'openid'  => $openid,
                'nickname'  => $user['nickname'],
                'sex'   => $user['sex'],
                'country'   => $user['country'],
                'province'  => $user['province'],
                'city'  => $user['city'],
                'language'  => 'zh_CN',
                'headimgurl'    => $user['headimgurl'],
                'subscribe_time'    => date('Y-m-d H:i:s', $user['subscribe_time']),
                'unionid'   => $unionid,
                'remark'    => $user['remark'],
                'groupid'   => $user['groupid'],
                'subscribe_scene'   => isset($user['subscribe_scene']) ? $user['subscribe_scene'] : '',
                'create_time'   => date('Y-m-d H:i:s')
            ]);
        }
        return $result ? true : false;
    }

    /**
     * 上传文件到微信素材库
     * @param $appid
     * @param $type
     * @param $file_path
     * @return mixed
     * @throws \think\Exception
     */
    public function uploadMaterial($appid, $type, $file_path, $title = '', $introduce = ''){
        $result = $this->wxApi->addMaterial($this->getAuthorizerAccessToken($appid), $type, $file_path, $title, $introduce);
        return $result;
    }

    /**
     * 发送模板消息
     * @param $appid
     * @param $openid
     * @param $template_id
     * @param $data
     * @param string $url
     * @param array $miniprogram
     * @return mixed|null
     */
    public function sendTemplateMsg($appid, $openid, $template_id, $data, $url = '', $miniprogram = []){
        $content = [
            'touser'        => $openid,
            'template_id'   => $template_id,
            'url'           => $url,
            'data'          => $data
        ];
        if (!empty($miniprogram) && isset($miniprogram['appid']) && isset($miniprogram['pagepath'])){
            $content['miniprogram'] = $miniprogram;
        }
        $content = json_encode($content, JSON_UNESCAPED_UNICODE);
        $result = $this->wxApi->templateSend($this->getAuthorizerAccessToken($appid), $content);
        return $result;
    }

    /**
     * 并发模板消息通知
     * @param $appid
     * @param $openids
     * @param $template_id
     * @param $data
     * @param $complete_func
     * @param $success_func
     * @param $error_func
     * @param string $url
     * @param array $miniprogram
     * @throws \ErrorException
     */
    public function multiSendTemplateMsg($appid, $openids, $template_id, $data, $complete_func, $success_func, $error_func, $url = '', $miniprogram = []){
        $access_token = $this->getAuthorizerAccessToken($appid);
        $multi_curl = new MultiCurl();
        // 成功返回
        $multi_curl->success($success_func);
        // 错误返回
        $multi_curl->error($error_func);
        // 发送完成
        $multi_curl->complete($complete_func);

        foreach ($openids as $vo){
            $content = [
                'touser'        => $vo,
                'template_id'   => $template_id,
                'url'           => $url,
                'data'          => $data,
                'miniprogram'   => $miniprogram,
            ];
            $content = json_encode($content, JSON_UNESCAPED_UNICODE);

            // 并发请求
            $multi_curl->addPost(WxApi::$api['template_send']['url'] . '?access_token='. $access_token, $content);
        }
        $multi_curl->start();
    }


    /**
     * 统一发送模板消息
     * @param $appid
     * @param $openid
     * @param $template_id
     * @param $data
     * @param string $url
     * @param string $formId
     * @return mixed|null
     */
    public function uniformSend($appid, $openid, $template_id, $data, $url = '', $miniprogram = [], $formId = ''){
        //如果formid不为空，则代表为小程序
        if(!empty($formId))
            $content = [
                'touser'        => $openid,//小程序openid与公众号openid区分开
                'weapp_template_msg' => [
                    'template_id'   => $template_id,
                    'page'          => $url,
                    'form_id'       => $formId,
                    'data'          => $data,
                    'emphasis_keyword'=> $data["keyword1"]["value"]
                ]
            ];
        else
        {
            $content = [
                'touser'        => $openid,
                'mp_template_msg' => [
                    'appid'         => $appid,
                    'template_id'   => $template_id,
                    'url'          => $url,
                    'data'          => $data,
                ]
            ];
            if (!empty($miniprogram) && isset($miniprogram['appid']) && isset($miniprogram['pagepath'])){
                $content['mp_template_msg']['miniprogram'] = $miniprogram;
            }
        }

        //$content = json_encode($content, JSON_UNESCAPED_UNICODE);
        $result = $this->wxApi->uniformSend($this->getAuthorizerAccessToken($appid), $content);
        return $result;
    }


    /**
     * 网页授权登录
     * @param $appid
     * @param $redirect_uri
     * @throws Exception
     */
    public function authLogin($appid, $redirect_uri){
        $code = input('get.code', '');
        $access_token_cache = session("weixin.$appid");
        // 判断access_token是否有效
        if (isset($access_token_cache['access_token']) && isset($access_token_cache['expires_time']) && time() < $access_token_cache['expires_time']){
            // 从缓存获取access_token
            $result = $access_token_cache;
        }else{
            if (isset($access_token_cache['refresh_token'])){
                // 刷新access_token
                $result = $this->oauth2RefreshToken($appid, $access_token_cache['refresh_token']);
            }
            if (!isset($access_token_cache['refresh_token']) || isset($result['errcode'])){
                $component_appid = \Config::get('component_appid', $this->module);
                if (empty($code)){
                    // 发起网页授权登录,获取code
                    $url = WxApi::$api['oauth2_authorize']['url'];
                    $params = [
                        'appid' => $appid,
                        'redirect_uri'  => $redirect_uri,
                        'response_type' => 'code',
                        'scope' => 'snsapi_userinfo',
                        'state' => 'IWZN',
                        'component_appid'   => $component_appid,
                    ];
                    $url .= '?'. http_build_query($params) . '#wechat_redirect';
                    return redirect($url)->send();
                }
                // 通过code换取access_token
                $result = $this->oauth2AccessToken($appid, $code);
            }
            $result['expires_time'] = time() + $result['expires_in'] - 100;   // access_token过期时间
            session("weixin.$appid", $result);  // 缓存access_token信息
        }
        // 从数据库获取用户信息
        $userinfo = Db::name('wx_users')->field('id,appid,openid,nickname,mobile,subscribe,sex,language,city,province,country,headimgurl,unionid')
            ->where('openid', $result['openid'])
            ->find();
        if ($code || empty($userinfo)){
            // 拉取用户信息
            $user = $this->getUserInfo($result['access_token'], $result['openid']);
            if (isset($user['errcode']) && $user['errcode'] > 0) throw new Exception("{$user['errcode']}:{$user['errmsg']}");
            if (!empty($userinfo)){
                // 更新
                Db::name('wx_users')
                    ->where('openid', $result['openid'])
                    ->update([
                        'nickname'  => $user['nickname'],
                        'sex'   => $user['sex'],
                        'language'  => $user['language'],
                        'city'  => $user['city'],
                        'province'  => $user['province'],
                        'country'   => $user['country'],
                        'headimgurl'    => isset($user['headimgurl']) ? $user['headimgurl'] : '',
                        'unionid'   => isset($user['unionid']) ? $user['unionid'] : '',
                        'update_time'   => date('Y-m-d H:i:s'),
                    ]);
                $user['subscribe'] = $userinfo['subscribe'];
                $user['mobile'] = $userinfo['mobile'];
            }else{
                // 插入
                $id = Db::name('wx_users')->insertGetId([
                    'appid' => $appid,
                    'subscribe' => 0,
                    'openid'    => $user['openid'],
                    'nickname'  => $user['nickname'],
                    'sex'   => $user['sex'],
                    'language'  => $user['language'],
                    'city'  => $user['city'],
                    'province'  => $user['province'],
                    'country'   => $user['country'],
                    'headimgurl'    => isset($user['headimgurl']) ? $user['headimgurl'] : '',
                    'unionid'   => isset($user['unionid']) ? $user['unionid'] : '',
                    'create_time'   => date('Y-m-d H:i:s'),
                ]);
                $user['id'] = $id;
                $user['subscribe'] = 0;
                $user['mobile'] = '';
            }
            $user['appid'] = $appid;
            $user['unionid'] = isset($user['unionid']) ? $user['unionid'] : '';
            return $user;
        }else{
            return $userinfo;
        }
    }

    /**
     * 获取网页授权access_token
     * @param $appid
     * @param $code
     * @return mixed
     * @throws Exception
     */
    public function oauth2AccessToken($appid, $code){
        $url = WxApi::$api['oauth2_access_token']['url'];
        $params = [
            'appid' => $appid,
            'code'  => $code,
            'grant_type'    => 'authorization_code',
            'component_appid'   => \Config::get('component_appid', $this->module),
            'component_access_token'    => $this->getComponentAccessToken()
        ];
        $this->wxApi->_curl->get($url, $params);
        if ($this->wxApi->_curl->error){
            throw new Exception($this->wxApi->_curl->errorMessage);
        }else{
            $result = json_decode($this->wxApi->_curl->response, true);
            if (isset($result['errcode'])) throw new Exception("{$result['errcode']}:{$result['errmsg']}");
        }
        return $result;
    }

    /**
     * 刷新网页授权access_token
     * @param $appid
     * @param $refresh_token
     * @return mixed
     * @throws Exception
     */
    public function oauth2RefreshToken($appid, $refresh_token){
        $url = WxApi::$api['oauth2_refresh_token']['url'];
        $params = [
            'appid' => $appid,
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refresh_token,
            'component_appid'   => \Config::get('component_appid', $this->module),
            'component_access_token'    => $this->getComponentAccessToken()
        ];
        $this->wxApi->_curl->get($url, $params);
        if ($this->wxApi->_curl->error){
            throw new Exception($this->wxApi->_curl->errorMessage);
        }else{
            $result = json_decode($this->wxApi->_curl->response, true);
            return $result;
        }
    }

    /**
     * 获取jsapi_ticket
     * @param $appid
     */
    public function getJsapiTicket($appid){
        $row = Db::name('wx_jsapi_ticket')->where('appid', $appid)->find();
        // 缓存中获取
        if (!empty($row) && $row['expire_time'] > time()){
            return $row['jsapi_ticket'];
        }
        $access_token = $this->getAuthorizerAccessToken($appid);
        $jsapi_ticket = $this->wxApi->getJsapiTicket($access_token);
        if (empty($row)){
            Db::name('wx_jsapi_ticket')->insert([
                'appid' => $appid,
                'jsapi_ticket'  => $jsapi_ticket['ticket'],
                'expire_time'   => $jsapi_ticket['expires_in'] + time() - 100,
            ]);
        }else{
            Db::name('wx_jsapi_ticket')->where('appid', $appid)->update([
                'jsapi_ticket'  => $jsapi_ticket['ticket'],
                'expire_time'   => $jsapi_ticket['expires_in'] + time() - 100,
            ]);
        }
        return $jsapi_ticket['ticket'];
    }

    /**
     * 获取jssdk
     * @param $appid
     * @return array
     */
    public function getJsSdk($appid, $url = ''){
        $noncestr = get_random_str();
        $timestamp = time();
        $jsapi_ticket = $this->getJsapiTicket($appid);
        $url = empty($url) ? request()->url(true) : $url;
        $url = urldecode($url);
        $string = "jsapi_ticket=$jsapi_ticket&noncestr=$noncestr&timestamp=$timestamp&url=$url";
        $signature = sha1($string);
        return [
            'appId' => $appid,
            'timestamp' => $timestamp,
            'nonceStr'  => $noncestr,
            'signature' => $signature,
            'url'   => $url,
            'rawString' => $string,
        ];
    }

    /**
     * 获取用户基本信息
     * @param $access_token
     * @param $openid
     * @return mixed
     * @throws Exception
     */
    public function getUserInfo($access_token, $openid){
        $url = WxApi::$api['sns_userinfo']['url'];
        $this->wxApi->_curl->get($url, [
            'access_token'  => $access_token,
            'openid'    => $openid,
            'lang'  => 'zh_CN',
        ]);
        if ($this->wxApi->_curl->error){
            throw new Exception($this->wxApi->_curl->errorMessage);
        }else{
            $result = is_object($this->wxApi->_curl->response) ? object_to_array($this->wxApi->_curl->response) : json_decode(json_decode(wx_trim(json_encode($this->wxApi->_curl->response)), true), true);
            return $result;
        }
    }

    /**
     * 设置小程序域名
     * @param $appid
     */
    public function setWeappDomain($appid){
        $request_domain = \Config::get('weapp_request_domain', 'weixin');
        $socket_domain = \Config::get('weapp_socket_domain', 'weixin');
        $upload_domain = \Config::get('weapp_upload_domain', 'weixin');
        $download_domain = \Config::get('weapp_download_domain', 'weixin');
        $business_domain = \Config::get('weapp_business_domain', 'weixin');

        $access_token = $this->getAuthorizerAccessToken($appid);
        // 设置服务域名
        $this->wxApi->modifyDomain($access_token, 'add', [
            'requestdomain' => empty($request_domain) ? [] : explode(',', $request_domain),
            'wsrequestdomain' => empty($socket_domain) ? [] : explode(',', $socket_domain),
            'uploaddomain' => empty($upload_domain) ? [] : explode(',', $upload_domain),
            'downloaddomain' => empty($download_domain) ? [] : explode(',', $download_domain),
        ]);
        // 设置业务域名
        $this->wxApi->setwebviewdomain($access_token, 'add', [
            'webviewdomain' => empty($business_domain) ? [] : explode(',', $business_domain),
        ]);
    }

    /**
     * 添加客服消息
     * @param $msg
     */
    public function addCustomerServiceNews($appid, $msg){
        $data = [
            'appid' => $appid,
            'openid'    => $msg['FromUserName'],
            'msg_id'    => $msg['MsgId'],
            'type'  => $msg['MsgType'],
            'con'   => $msg['Content'],
            'create_time'   => date('Y-m-d H:i:s',$msg['CreateTime'])
        ];
        Db::name('wx_customer_service_news')->insert($data);
    }

    /**
     * 全网发布
     */
    public function whole_public($appid, $timeStamp, $nonce, $msg){
        $xmlTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[text]]></MsgType>
            <Content><![CDATA[%s]]></Content>
            </xml>";
        switch ($msg['MsgType']){
            case 'text':
                if (isset($msg['Content']) && $msg['Content'] == 'TESTCOMPONENT_MSG_TYPE_TEXT'){
                    $content = $msg['Content'] . '_callback';
                }elseif (strpos ($msg['Content'], "QUERY_AUTH_CODE:") !== false){
                    $ticket = str_replace ("QUERY_AUTH_CODE:", "", $msg['Content']);
                    $content = $ticket . "_from_api";
                    $authorizer_access_token = $this->getAuthorizerAccessToken($appid);
                    // 发送客服消息接口
                    $this->wxApi->customSend($authorizer_access_token, $msg['FromUserName'], 'text', ['content' => $content]);
                }
                break;
            case 'event':
                $content = $msg['Event'] . 'from_callback';
                break;
            default:;
        }
        //file_put_contents(RUNTIME_PATH . 'event.log', var_export(['url' => request()->url(), 'content' => $content], true), FILE_APPEND);
        if (!empty($content)){
            $result = sprintf($xmlTpl, $msg['FromUserName'], $msg['ToUserName'], time(), $content);
            $encrypt_type = input('encrypt_type', '');
            if ($encrypt_type == 'aes'){
                $encryptMsg = $this->encrypt($result, $timeStamp, $nonce);
                return $encryptMsg;
            }
        }
    }

    /**
     *
     * @param $from
     * @param $to
     * @param $content
     * @return string
     */
    public function xml($from, $to, $content){
        $xml = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[text]]></MsgType>
            <Content><![CDATA[%s]]></Content>
            </xml>";
        return sprintf($xml, $from, $to, time(), $content);
    }

    /**
     * xml加密
     * @param $xml
     * @param $timeStamp
     * @param $nonce
     * @return string
     */
    public function encrypt($xml, $timeStamp, $nonce){
        $component_appid = \Config::get('component_appid', $this->module);
        $encoding_aes_key = \Config::get('encoding_aes_key', $this->module);
        $token = \Config::get('weixin_token', $this->module);
        $msgCryptObj = new WxBizMsgCrypt($token, $encoding_aes_key, $component_appid);
        $encryptMsg = '';
        $msgCryptObj->encryptMsg($xml, $timeStamp, $nonce, $encryptMsg);
        return $encryptMsg;
    }
}
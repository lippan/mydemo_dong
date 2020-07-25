<?php

namespace App\Libraries\pay\wxpay;

class WxjsLib
{
    //接口API URL前缀
    const API_URL_PREFIX = 'https://api.mch.weixin.qq.com';

    //下单地址URL

    const UNIFIEDORDER_URL = "/pay/unifiedorder";
    //查询订单URL
    const ORDERQUERY_URL = "/pay/orderquery";
    //关闭订单URL
    const CLOSEORDER_URL = "/pay/closeorder";
    //企业付款到零钱
    const ENCHASHMENT = "/mmpaymkttransfers/promotion/transfers";
    // 现金红包
    const CASH_REDBAG = '/mmpaymkttransfers/sendredpack';

    //公众账号ID
    private $appid;
    //商户号
    private $mch_id;
    //随机字符串
    private $nonce_str;
    //签名
    private $sign;
    //商品描述
    private $body;
    //商户订单号
    private $out_trade_no;
    //支付总金额
    private $total_fee;
    //终端IP
    private $spbill_create_ip;
    //支付结果回调通知地址
    private $notify_url;
    //交易类型
    private $trade_type;
    //支付密钥
    private $key;
    //附加数据
    private $attach;


    //支付场景参数
    private $scene_info;

    //证书路径
    private $SSLCERT_PATH;

    private $SSLKEY_PATH;
    //所有参数
    private $params = array();


    public function __construct($appid, $mch_id, $key, $notify_url)
    {
        $this->appid = $appid;
        $this->mch_id = $mch_id;
        $this->notify_url = $notify_url;
        $this->key = $key;
    }

    public function setSoftWareParams(){


    }

    public function setNotifyUrl($url){
        $this->notify_url = $url;
    }

    /**
     * 下单方法
     * @param $params 下单参数
     **/
    public function unifiedOrder( $params )
    {

        $this->out_trade_no     = $params['out_trade_no'];
        $this->total_fee        = $params['total_fee'];
        $this->trade_type       = $params['trade_type'];
        $this->body             = $params['body'];
        $this->attach           = $params['attach'];

        $this->spbill_create_ip = $params['ip'];
        $this->nonce_str        = $this->genRandomString();


        $this->params['appid']            = $this->appid;
        $this->params['mch_id']           = $this->mch_id;
        $this->params['nonce_str']        = $this->nonce_str;
        $this->params['body']             = $this->body;
        $this->params['out_trade_no']     = $this->out_trade_no;
        $this->params['total_fee']        = $this->total_fee;
        $this->params['spbill_create_ip'] = $this->spbill_create_ip;
        $this->params['notify_url']       = $this->notify_url;
        $this->params['trade_type']       = $this->trade_type;
        $this->params['attach']           = $this->attach;


        //非共同参数
        !empty($params['openid']) && $this->openid = $params['openid'];
        !empty($params['openid']) && $this->params['openid'] = $this->openid;

        !empty($params['scene_info']) &&   $this->scene_info = $params['scene_info'];;
        !empty($params['scene_info']) &&   $this->params['scene_info'] = $this->scene_info;
        //获取签名数据
        $this->sign           = $this->MakeSign($this->params);
        $this->params['sign'] = $this->sign;
        $xml                  = $this->data_to_xml($this->params);



        $response = $this->postXmlCurl($xml, self::API_URL_PREFIX.self::UNIFIEDORDER_URL);


        if (!$response) {
            return false;
        }
        $result = $this->xml_to_data($response);
        if (!empty($result['result_code']) && !empty($result['err_code'])) {
            $result['err_msg'] = $this->error_code($result['err_code']);
        }
        return $result;
    }

    /**
     * 查询订单信息
     * @param $out_trade_no 订单号
     * @return array
     */
    public function orderQuery($out_trade_no)
    {
        $this->params['appid']        = $this->appid;
        $this->params['mch_id']       = $this->mch_id;
        $this->params['nonce_str']    = $this->genRandomString();
        $this->params['out_trade_no'] = $out_trade_no;
        //获取签名数据
        $this->sign           = $this->MakeSign($this->params);
        $this->params['sign'] = $this->sign;
        $xml                  = $this->data_to_xml($this->params);
        $response             = $this->postXmlCurl($xml, self::API_URL_PREFIX . self::ORDERQUERY_URL);
        if (!$response) {
            return false;
        }
        $result = $this->xml_to_data($response);
        if (!empty($result['result_code']) && !empty($result['err_code'])) {
            $result['err_msg'] = $this->error_code($result['err_code']);
        }
        return $result;
    }

    /*
     * 企业付款到零钱
     */
    public function enchashOrder( $params ,$type=null){

        $this->params['mch_appid']        = $this->appid;
        $this->params['mchid']            = $this->mch_id;
        $this->params['nonce_str']        = $this->genRandomString();
        $this->params['partner_trade_no'] = $params['partner_trade_no'];
        $this->params['openid']           = $params['openid'];
        $this->params['amount']           = $params['amount'];
        $this->params['desc']             = $params['desc'];
        $this->params['spbill_create_ip'] = $params['ip'];
        $this->params['check_name']       = 'NO_CHECK';




        //获取签名数据
        $this->sign           = $this->MakeSign($this->params);
        $this->params['sign'] = $this->sign;
        $xml                  = $this->data_to_xml($this->params);


        $response             = $this->postXmlCurl($xml, self::API_URL_PREFIX . self::ENCHASHMENT,true);
        if (!$response) {
            return false;
        }
        $result = $this->xml_to_data($response);
        if (!empty($result['result_code']) && !empty($result['err_code'])) {
            $result['err_msg'] = $this->error_code($result['err_code']);
        }
        return $result;




    }

    /**
     * 现金红包
     * @param array $params
     * @return bool|mixed
     * @throws \Exception
     */
    public function cashRedbag(array $params){
        $send_name = $params['send_name'] ?? '三一互动云';
        $total_num = $params['total_num'] ?? 1;
        $wishing = $params['wishing'] ?? '恭喜您获取红包';
        $client_ip = request()->getClientIp();
        $data['re_openid'] = $params['openid'];
        $data['total_amount'] = $params['total_amount'];
        $data['act_name'] = $params['act_name'];
        $data['mch_billno'] = $params['order_no'];
        $data['nonce_str'] = $this->genRandomString();
        $data['mch_id'] = $this->mch_id;
        $data['wxappid'] = $this->appid;
        $data['send_name'] = $send_name;
        $data['total_num'] = $total_num;
        $data['wishing'] = $wishing;
        $data['client_ip'] = $client_ip;
        $data['remark'] = '猜越多得越多，快来抢！';
        
        if(isset($params["scene_id"]) && !empty($params["scene_id"]))
        $data['scene_id'] = $params["scene_id"];

        $data['sign'] = $this->MakeSign($data);
        $xml = $this->data_to_xml($data);

        $response = $this->postXmlCurl($xml, self::API_URL_PREFIX.self::CASH_REDBAG, true);
        if (!$response) {
            throw new \Exception('无法发送请求');
        }
        $result = $this->xml_to_data($response);
        if (!isset($result['result_code']) || $result['result_code'] != 'SUCCESS') {
            throw new \Exception($result['err_code_des']);
        }
        return $result;
    }

    /**
     * 关闭订单
     * @param $out_trade_no 订单号
     * @return array
     */
    public function closeOrder( $out_trade_no )
    {
        $this->params['appid']        = $this->appid;
        $this->params['mch_id']       = $this->mch_id;
        $this->params['nonce_str']    = $this->genRandomString();
        $this->params['out_trade_no'] = $out_trade_no;
        //获取签名数据
        $this->sign           = $this->MakeSign($this->params);
        $this->params['sign'] = $this->sign;
        $xml                  = $this->data_to_xml($this->params);
        $response             = $this->postXmlCurl($xml, self::API_URL_PREFIX . self::CLOSEORDER_URL);
        if (!$response) {
            return false;
        }
        $result = $this->xml_to_data( $response );
        return $result;
    }
    /**
     *
     * 获取支付结果通知数据
     * return array
     */
    public function getNotifyData(){
        //获取通知的数据
        $xml = file_get_contents('php://input');
        $data = array();
        if( empty($xml) ){
            return false;
        }
        $data = $this->xml_to_data( $xml );
        if( !empty($data['return_code']) ){
            if( $data['return_code'] == 'FAIL' ){
                return false;
            }
        }
        return $data;
    }
    /**
     * 接收通知成功后应答输出XML数据
     * @param string $xml
     */
    public function replyNotify(){
        $data['return_code'] = 'SUCCESS';
        $data['return_msg'] = 'OK';
        $xml = $this->data_to_xml( $data );
        echo $xml;

    }
    /**
     * 生成APP端支付参数
     * @param $prepayid 预支付id
     */
    public function getAppPayParams( $prepayid ){
        $data['appid'] = $this->appid;
        $data['partnerid'] = $this->mch_id;
        $data['prepayid'] = $prepayid;
        $data['package'] = 'Sign=WXPay';
        $data['noncestr'] = $this->genRandomString();
        $data['timestamp'] = time();
        $data['sign'] = $this->MakeSign( $data );
        return $data;
    }
    /**
     * 生成签名
     * @return 签名
     */
    public function MakeSign( $params ){
        //签名步骤一：按字典序排序数组参数
        ksort($params);
        $string = $this->ToUrlParams($params);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=".$this->key;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }
    /**
     * 将参数拼接为url: key=value&key=value
     * @param $params
     * @return string
     */
    public function ToUrlParams( $params ){
        $string = '';
        if( !empty($params) ){
            $array = array();
            foreach( $params as $key => $value ){
                $array[] = $key.'='.$value;
            }
            $string = implode("&",$array);
        }
        return $string;
    }
    /**
     * 输出xml字符
     * @param $params 参数名称
     * return string 返回组装的xml
     **/
    public function data_to_xml( $params ){
        if(!is_array($params)|| count($params) <= 0)
        {
            return false;
        }
        $xml = "<xml>";
        foreach ($params as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }
    /**
     * 将xml转为array
     * @param string $xml
     * return array
     */
    public function xml_to_data($xml){
        if(!$xml){
            return false;
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $data;
    }
    /**
     * 获取毫秒级别的时间戳
     */
    private static function getMillisecond(){
        //获取毫秒的时间戳
        $time = explode ( " ", microtime () );
        $time = $time[1] . ($time[0] * 1000);
        $time2 = explode( ".", $time );
        $time = $time2[0];
        return $time;
    }
    /**
     * 产生一个指定长度的随机字符串,并返回给用户
     * @param type $len 产生字符串的长度
     * @return string 随机字符串
     */
    public function genRandomString($len = 32) {
        $chars = array(
            "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
            "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
            "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
            "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
            "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
            "3", "4", "5", "6", "7", "8", "9"
        );
        $charsLen = count($chars) - 1;
        // 将数组打乱
        shuffle($chars);
        $output = "";
        for ($i = 0; $i < $len; $i++) {
            $output .= $chars[mt_rand(0, $charsLen)];
        }
        return $output;
    }

    /*
     * 以post方式提交xml到对应的接口url
     * @param string $xml 需要post的xml数据
     * @param string $url url
     * @param bool $useCert 是否需要证书，默认不需要
     * @param int $second url执行超时时间，默认30s
     * @throws WxPayException
    */

    private function postXmlCurl($xml,$url ,$useCert = false, $second = 120){

        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        if($useCert == true){

            curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLCERT,    base_path('cert/apiclient_cert.pem'));

            curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLKEY,    base_path('cert/apiclient_key.pem'));

        }
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);

        //返回结果
        if($data){
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            return false;
        }
    }


    /**
     * 错误代码
     * @param $code 服务器输出的错误代码
     * return string
     */
    public function error_code( $code ){
        $errList = array(
            'NOAUTH' => '商户未开通此接口权限',
            'NOTENOUGH' => '用户帐号余额不足',
            'ORDERNOTEXIST' => '订单号不存在',
            'ORDERPAID' => '商户订单已支付，无需重复操作',
            'ORDERCLOSED' => '当前订单已关闭，无法支付',
            'SYSTEMERROR' => '系统错误!系统超时',
            'APPID_NOT_EXIST' => '参数中缺少APPID',
            'MCHID_NOT_EXIST' => '参数中缺少MCHID',
            'APPID_MCHID_NOT_MATCH' => 'appid和mch_id不匹配',
            'LACK_PARAMS' => '缺少必要的请求参数',
            'OUT_TRADE_NO_USED' => '同一笔交易不能多次提交',
            'SIGNERROR' => '参数签名结果不正确',
            'XML_FORMAT_ERROR' => 'XML格式错误',
            'REQUIRE_POST_METHOD' => '未使用post传递参数 ',
            'POST_DATA_EMPTY' => 'post数据不能为空',
            'NOT_UTF8' => '未使用指定编码格式',
        );
        if( array_key_exists( $code , $errList ) ){
            return $errList[$code];
        }
    }
}

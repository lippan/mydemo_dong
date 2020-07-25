<?php
/**
 * Created by PhpStorm.
 * User: 吴亚敏
 * Date: 2020/2/19
 * Time: 16:11
 */

namespace App\Libraries\aliyun;
class Sms{

    /**
     * 配置信息
     * @var array
     */
    private $_config = [
        'url' => 'http://dysmsapi.aliyuncs.com',
        'accesskeyId' => 'LTAI4FmrNVS9z8ZQR5QqPXjJ',
        'accessKeySecret' => 'us3XEOMH9G644iRQeOUgbvflZH9nug',
        'SignName'  => '三一互动平台',
    ];
    private $_timezone;
    private $_commonHeaders = [];

    public static $instance;
    public static function getInstance(){
        if (!self::$instance instanceof self){
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function __construct(){
        // 设置时区
        $this->_timezone = date_default_timezone_get();
        date_default_timezone_set('GMT');
        // 构建公共头
        $this->_commonHeaders = [
            'Format'    => 'json',
            'AccessKeyId'   => $this->_config['accesskeyId'],
            'Action'    => 'SendSms',
            'SignatureMethod'   => 'HMAC-SHA1',
            'SignatureNonce'    => uniqid(),
            'SignatureVersion'  => '1.0',
            'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'Version'   => '2017-05-25',
        ];
    }

    /**
     * 发送短信验证码
     * @param $phone
     * @param $verify_code
     * @return bool
     * @throws \Exception
     */
    public function send_verify_code($phone, $verify_code){
        $params = array_merge([
            'PhoneNumbers'  => $phone,
            'SignName'  => $this->_config['SignName'],
            'TemplateCode'  => 'SMS_183762672',
            'TemplateParam' => '{"code":"'.$verify_code.'"}',
        ], $this->_commonHeaders);
        $params['Signature'] = $this->sign($params);
        $result = $this->curl($this->_config['url'].'/?'.http_build_query($params));
        if (isset($result['Code']) && $result['Code'] == 'OK'){
            return true;
        }else{
            throw new \Exception($result['Message']);
        }
    }

    /**
     * 生成签名
     */
    private function sign($params){
        ksort($params);
        $signatureStr = '';
        foreach ($params as $key => $val){
            $signatureStr .= '&' . $this->url_encode($key) . '=' . $this->url_encode($val);
        }
        $signature = 'GET&%2F&'.$this->url_encode(substr($signatureStr, 1));
        $signature = base64_encode(hash_hmac('sha1', $signature, $this->_config['accessKeySecret'] . '&', true));
        return $signature;
    }

    private function url_encode($string){
        $string = urlencode($string);
        $string = preg_replace('/\+/', '%20', $string);
        $string = preg_replace('/\*/', '%2A', $string);
        $string = preg_replace('/%7E/', '~', $string);
        return $string;
    }

    private function curl($url, $method = 'get', $data = [], $headers = []){
        // CURL 请求
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if ($method == 'post'){
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result, true);
    }

    public function __destruct(){
        // 还原时区
        date_default_timezone_set($this->_timezone);
    }
}

?>
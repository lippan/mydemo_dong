<?php

namespace App\Libraries\Crm;
use Illuminate\Support\Facades\DB;

/**
 * 三一CRM接口类
 * Class Crm
 * @package sany
 */
class Crm{

    // 手机号查用户id
    public $getUrl = 'http://poweb.sanygroup.com:50000/dir/wsdl?p=ic/6d297855c1663dfaa57a028a88bcda1c';  // 生产
    //public $getUrl = 'http://podev01v-ap.sanygroup.com:50000/dir/wsdl?p=ic/6d297855c1663dfaa57a028a88bcda1c'; // 测试
    // 创建线索
    public $postUrl = 'http://poweb.sanygroup.com:50000/dir/wsdl?p=ic/60d34768ac33394991653b3f96586327'; // 生产
    //public $postUrl = 'http://podev01v-ap.sanygroup.com:50000/dir/wsdl?p=ic/60d34768ac33394991653b3f96586327';    // 测试

    private $_account = 'Srv_OTO_POP'; // 生产
    private $_password = 'O71boAtk';    // 生产

    //private $_account = 'srv_test_pod'; // 测试
    //private $_password = 'pod123456';    // 测试

    private $_expire_time = 10;  // wsdl 缓存时间

    public function getFunctionsAndTypes($url){

        $this->_account = 'srv_test_pod'; // 测试
        $this->_password = 'pod123456';    // 测试
        $url = 'http://podev01v-ap.sanygroup.com:50000/dir/wsdl?p=ic/6d297855c1663dfaa57a028a88bcda1c';

        $cacheFile = md5($url);
        $file_name =  base_path("storage/rundata/{$cacheFile}.wsdl");
        if (file_exists($file_name) && ($atime = filemtime($file_name) + $this->_expire_time) > time()){
            // 从本地读取wsdl
        }else{
            // 从服务端更新wsdl
            try{
                if (empty($wsdl)){
                    $wsdl = $this->_curl($url, [], "{$this->_account}:{$this->_password}");
                    file_put_contents($file_name, $this->_prepareWsdl($wsdl));
                }
            }catch (\Exception $e){
                throw new \Exception($e->getMessage());
            }
        }
        libxml_disable_entity_loader(false);
        $soap = new \SoapClient($file_name, [
            'login' => $this->_account,
            'password' => $this->_password,
            'soap_version' => SOAP_1_2,
            'trace' => true,
        ]);
        return [
            'functions' => $soap->__getFunctions(),
            'types'     => $soap->__getTypes()
        ];
    }

    /**
     * 推送线索给crm
     * @param $mobile
     * @return mixed
     * @throws \Exception
     */
    public function getUserId($mobile){
        $cacheFile = md5($this->getUrl);
        $file_name = base_path("storage/rundata/{$cacheFile}.wsdl");
        // 从服务端更新wsdl
        try{
            $wsdl = $this->_curl($this->getUrl, [], "{$this->_account}:{$this->_password}");
            file_put_contents($file_name, $this->_prepareWsdl($wsdl));
        }catch (\Exception $e){
            // 记录失败日志
            throw new \Exception($e->getMessage());
        }
        libxml_disable_entity_loader(false);
        $soap = new \SoapClient($file_name, [
            'login' => $this->_account,
            'password' => $this->_password,
            'soap_version' => SOAP_1_2,
            'trace' => true,
        ]);

        $params = [
            'IV_TEL' => $mobile,
        ];
        try{
            $res = $soap->__soapCall('osQueryAccountByTel_OTO', [$params]);
        }catch (\SoapFault $e){
            // 记录失败日志
            throw new \Exception($e->getMessage());
        }
        $res = $this->object_to_array($res);
        $msg = isset($res['ES_RETURN']['MESSAGE']) ? $res['ES_RETURN']['MESSAGE'] : '';
        if (!isset($res['ET_USER']['USER_ID'])) throw new \Exception($msg);
        return $res['ET_USER']['USER_ID'];
    }

    /**
     * 推送线索
     * @param array $data
     * @return mixed
     * @throws \Exception
     */
    public function postCrm($params){

        $cacheFile = md5($this->postUrl);
        $file_name = base_path("storage/rundata/{$cacheFile}.wsdl");

        // 从服务端更新wsdl
        try{
            $wsdl = $this->_curl($this->postUrl, [], "{$this->_account}:{$this->_password}");
            file_put_contents($file_name, $this->_prepareWsdl($wsdl));
        }catch (\Exception $e){
            // 记录失败日志
            throw new \Exception($e->getMessage()."-写文件报错");
        }

        libxml_disable_entity_loader(false);
        $soap = new \SoapClient($file_name, [
            'login'         => $this->_account,
            'password'      => $this->_password,
            'soap_version'  => SOAP_1_2,
            'trace'         => true,
        ]);


        try
        {
            $res = $soap->__soapCall('osCreateClueList_OTO', [$params]);
        }
        catch (\SoapFault $e)
        {
            throw new \Exception($e->getMessage()."请求反馈报错");
        }

        $res = $this->object_to_array($res);

        if (!isset($res['ES_RETURN']['TYPE']) || $res['ES_RETURN']['TYPE'] != 'S')
            throw new \Exception($res['ES_RETURN']['MESSAGE']);

        return $res['EV_OBJECT_ID'];
    }

    /**
     * 更新线索支付信息
     * @param array $data
     * @return mixed
     * @throws \Exception
     */
    public function putCrm(array $data){
        $cacheFile = md5($this->postUrl);
        $file_name = base_path("storage/rundata/{$cacheFile}.wsdl");
        // 从服务端更新wsdl
        try{
            $wsdl = $this->_curl($this->postUrl, [], "{$this->_account}:{$this->_password}");
            file_put_contents($file_name, $this->_prepareWsdl($wsdl));
        }catch (\Exception $e){
            // 记录失败日志
            throw new \Exception($e->getMessage());
        }
        libxml_disable_entity_loader(false);
        $soap = new \SoapClient($file_name, [
            'login'         => $this->_account,
            'password'      => $this->_password,
            'soap_version'  => SOAP_1_2,
            'trace'         => true,
        ]);

        $params = [
            'IS_HEADER' => [
                'OBJECT_ID' => $data['object_id'],
            ],
            'IS_PAYMENT' => [
                'FKBH'      => $data['order_sn'],
                'FKJE'      => $data['order_money']/100,
                'FKRQ'      => date('Ymd',strtotime($data['pay_time'])),
                'FKSJ'      => date('His',strtotime($data['pay_time'])),
                'DDXDBH'    => isset($data['shop_id']) ? $data['shop_id'] : 0,
                'ddbz'      => isset($data['order_remark']) ? $data['order_remark'] : '',
            ],
        ];
        try{
            $res = $soap->__soapCall('osCreateClueList_OTO', [$params]);
        }catch (\SoapFault $e){
            // 记录失败日志
            throw new \Exception($e->getMessage());
        }
        $res = $this->object_to_array($res);

        if (!isset($res['ES_RETURN']['TYPE']) || $res['ES_RETURN']['TYPE'] != 'S') throw new \Exception($res['ES_RETURN']['MESSAGE']);
        return true;
    }

    /**
     * curl请求
     * @param $url
     * @param array $header
     * @param string $auth
     * @return mixed
     * @throws Exception
     */
    private function _curl($url, $header = [], $auth = ''){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        $header && curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        if ($auth){
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl, CURLOPT_USERPWD, $auth);
        }
        $content = curl_exec($curl);
        if (curl_errno($curl)){
            $res = curl_error($curl);
            curl_close($curl);
            throw new \Exception($res);
        }
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($code != 200){
            curl_close($curl);
            throw new \Exception("{$url} 错误码：{$code}，".curl_errno($curl));
        }
        curl_close($curl);
        return $content;
    }

    /**
     * 预处理wsdl文件
     * @param $wsdl
     * @return mixed
     */
    private function _prepareWsdl($wsdl){
        // 去掉头部XML 避免SOAP抛出异常
        if (preg_match('/<\?+\s*xml\s+version=\"\d*\.\d*\"\s+encoding=\"utf-8\"\?*>/i', $wsdl, $match)){
            $wsdl = str_replace($match[0], '', $wsdl);
        }
        // 修改UsingPolicy为false 避免SOAP抛出异常
        if (preg_match('/<wsp:UsingPolicy\s+wsdl:required="true"\/>/i', $wsdl, $match)){
            $wsdl = str_replace($match[0], "<wsp:UsingPolicy wsdl:required=\"false\"/>", $wsdl);
        }
        // 修改UsingPolicy为false 避免SOAP抛出异常
        if (preg_match('/<p4:UsingPolicy\s+wsdl:required="true"/i', $wsdl, $match)){
            $wsdl = str_replace($match[0], '<p4:UsingPolicy wsdl:required="false"', $wsdl);
        }


        return $wsdl;
    }

    public function object_to_array($object){
        $object = json_decode(json_encode($object),true);
        return  $object;
    }
}
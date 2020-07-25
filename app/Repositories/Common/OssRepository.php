<?php

/**
 *  上传图片资源
 */
namespace App\Repositories\Common;


use DateTime;
use Exception;
use Illuminate\Http\Request;
use OSS\Core\OssException;
use OSS\OssClient;

class OssRepository
{
    use \App\Repositories\BaseRepository;
    use \App\Helpers\ApiResponse;

    private $bucket;
    private $endpoint;
    private $host;
    private $accessKeyId;
    private $accessKeySecret;
    private $timeout;
    private $dir;
    private $max_size= 31457280;   // 最大文件限制(字节);


    public function __construct(){
        $this->bucket       = config('oss.bucket');
        $this->endpoint     = config('oss.endpoint');
        $this->host         = $this->bucket . '.' . $this->endpoint;
        $this->accessKeyId  = config('oss.accessKeyId');
        $this->accessKeySecret = config('oss.accessKeySecret');
        $this->timeout      = config('oss.timeout');
        $this->dir          = config('oss.dir');
    }

    /**
     * 上传配置
     * @param $config
     */
    public function set($config){
        $this->accessKeyId      = isset($config['access_key_id']) ? $config['access_key_id'] : $this->accessKeyId;
        $this->accessKeySecret  = isset($config['access_key_secret']) ? $config['access_key_secret'] : $this->accessKeySecret;
        $this->timeout          = isset($config['timeout']) ? $config['timeout'] : $this->timeout;
        $this->dir              = isset($config['dir']) ? $config['dir'] : $this->dir;
        $this->max_size         = isset($config['max_size']) ? intval($config['max_size']) : $this->max_size;
        $this->bucket           = isset($config['bucket']) ? $config['bucket'] : $this->bucket;
        if (isset($config['bucket']))
            $this->host = $this->bucket . '.' . $this->endpoint;
    }


    /**
     * 获取服务器地址
     * @return mixed|string
     */
    public function getHost(){
        return $this->host;
    }

    /**
     * 获取上传目录
     * @return mixed|string
     */
    public function getDir(){
        return $this->dir;
    }

    /**
     * 获取上传大小
     * @return int
     */
    public function getMaxSize(){
        return $this->max_size;
    }

    /**
     * 上传签名
     * @return array
     */
    public function policy(){
        $now = time();
        $end = $now + $this->timeout;
        $expiration = $this->gmtIso8601($end);

        //最大文件大小.用户可以自己设置
        $condition = [
            0 => 'content-length-range',
            1 => 0,
            2 => $this->max_size,
        ];
        $conditions[] = $condition;

        // 表示用户上传的数据，必须是以$dir开始，不然上传会失败，这一步不是必须项，只是为了安全起见，防止用户通过policy上传到别人的目录。
        $start = [
            0 => 'starts-with',
            1 => '$key',
            2 => $this->dir
        ];
        $conditions[] = $start;


        $arr = [
            'expiration' => $expiration,
            'conditions' => $conditions
        ];
        $policy = json_encode($arr);
        $base64_policy = base64_encode($policy);
        $string_to_sign = $base64_policy;
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $this->accessKeySecret, true));

        $response = [];
        $response['accessid']   = $this->accessKeyId;
        $response['host']       = $this->host;
        $response['policy']     = $base64_policy;
        $response['signature']  = $signature;
        $response['expire']     = $end;
        $response['dir']        = $this->dir;  // 这个参数是设置用户上传文件时指定的前缀。
        return $response;
    }


    /**
     * 表单上传
     */
    public function upload(Request $request, $user_id = ''){

        $file = $request->file('file');
        $now = time();
        $end = $now + $this->timeout;
        $expiration = $this->gmtIso8601($end);

        //最大文件大小.用户可以自己设置
        $conditions[] = [
            0 => 'content-length-range',
            1 => 0,
            2 => $this->max_size,
        ];

        // 表示用户上传的数据，必须是以$dir开始，不然上传会失败，这一步不是必须项，只是为了安全起见，防止用户通过policy上传到别人的目录。
        $conditions[] = [
            0 => 'starts-with',
            1 => '$key',
            2 => $this->dir
        ];
        $conditions[] = [
            0 => 'eq',
            1 => '$x-oss-meta-uid',
            2 => "$user_id"
        ];

        $arr = [
            'expiration' => $expiration,
            'conditions' => $conditions
        ];
        $policy = json_encode($arr);
        $base64_policy = base64_encode($policy);
        $signature = base64_encode(hash_hmac('sha1', $base64_policy, $this->accessKeySecret, true));
        //$ext = pathinfo($file->getInfo()['name'], PATHINFO_EXTENSION);  // 文件扩展名
        $ext  = $file->getClientOriginalExtension();
        $filename = $this->dir . date('Ymd') . '/' . time() . rand(10000, 99999) . '.' . $ext;
        $post = [
            'OSSAccessKeyId'    => $this->accessKeyId,
            'policy'            => $base64_policy,
            'Signature'         => $signature,
            'key'               => $filename,
            'success_action_status'   => 200,
            'x-oss-meta-uid'    => $user_id,
            'file'              => file_get_contents($file->getFilename()),
        ];
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->host);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
        $res = curl_exec($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);
        if (200 != $info['http_code']){
            throw new Exception($this->xml_to_array($res)['Message']);
        }
        return $filename;
    }

    /**
     * 上传二进制文件
     * @param $content 文件内容
     * @param $ext 文件扩展名
     * @param string $user_id
     * @return string
     * @throws \Exception
     */
    public function uploadBinary($content, $ext, $user_id = ''){
        $now = time();
        $end = $now + $this->timeout;
        $expiration = $this->gmtIso8601($end);

        //最大文件大小.用户可以自己设置
        $conditions[] = [
            0 => 'content-length-range',
            1 => 0,
            2 => $this->max_size,
        ];

        // 表示用户上传的数据，必须是以$dir开始，不然上传会失败，这一步不是必须项，只是为了安全起见，防止用户通过policy上传到别人的目录。
        $conditions[] = [
            0 => 'starts-with',
            1 => '$key',
            2 => $this->dir
        ];
        $conditions[] = [
            0 => 'eq',
            1 => '$x-oss-meta-uid',
            2 => "$user_id"
        ];

        $arr = [
            'expiration' => $expiration,
            'conditions' => $conditions
        ];
        $policy = json_encode($arr);
        $base64_policy = base64_encode($policy);
        $signature = base64_encode(hash_hmac('sha1', $base64_policy, $this->accessKeySecret, true));
        $filename = $this->dir . date('Ymd') . '/' . time() . rand(10000, 99999) . '.' . $ext;
        $post = [
            'OSSAccessKeyId'    => $this->accessKeyId,
            'policy'            => $base64_policy,
            'Signature'         => $signature,
            'key'               => $filename,
            'success_action_status'   => 200,
            'x-oss-meta-uid'    => $user_id,
            'file'              => $content,
        ];
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->host);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
        $res = curl_exec($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);
        if (200 != $info['http_code']){
            throw new Exception($this->xml_to_array($res)['Message']);
        }
        return $filename;
    }

    /**
     * 删除文件
     * @param $object
     * @throws Exception
     */
    public function delete($object){
        try{
            $ossClient = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint);
            $ossClient->deleteObject($this->bucket, $object);
        } catch(OssException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 获取文件头信息
     * @param $object
     * @return array
     * @throws Exception
     */
    public function header($object){
        try{
            $ossClient = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint);
            $res = $ossClient->getObjectMeta($this->bucket, $object);
        } catch(OssException $e) {
            throw new Exception($e->getMessage());
        }
        return $res;
    }

    /**
     * 获取文件对象
     * @param $object
     * @return string
     * @throws Exception
     */
    public function get($object){
        try{
            $ossClient = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint);
            $res = $ossClient->getObject($this->bucket, $object);
        } catch(OssException $e) {
            throw new Exception($e->getMessage());
        }
        return $res;
    }

    /**
     * 获取文件列表
     * @param $bucket
     * @param $prefix
     * @param int $pagesize
     * @param string $next
     * @return array
     * @throws Exception
     */
    public function getlists($bucket, $prefix, $pagesize = 20, $next = ''){
        try{
            $ossClient = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint);
            $res = $ossClient->listObjects($bucket, ['prefix' => $prefix, 'delimiter' => '', 'marker' => $next, 'max-keys' => $pagesize]);
        } catch(OssException $e) {
            throw new Exception($e->getMessage());
        }
        $lists = $res->getObjectList();
        $data = [];
        foreach ($lists as $vo){
            $data[] = [
                'key' => $vo->getKey(),
                'size' => $vo->getSize(),
                'time' => $vo->getLastModified()
            ];
        }
        return $data;
    }

    protected function gmtIso8601($time) {
        $dtStr      = date("c", $time);
        $mydatetime = new DateTime($dtStr);
        $expiration = $mydatetime->format(DateTime::ISO8601);
        $pos        = strpos($expiration, '+');
        $expiration = substr($expiration, 0, $pos);

        return $expiration."Z";
    }


    /**
     * xml转数组
     * @param $xml
     * @return mixed
     */
    protected function xml_to_array($xml){
    //禁止引用外部xml实体
    libxml_disable_entity_loader(true);
    $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    return $values;
}
}
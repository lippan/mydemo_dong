<?php

namespace App\Libraries\GdMap;

use Curl\Curl;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 *  高德地图接口类
 * Class WxApi
 * @package weixin
 */
class GdMap{

    /**
     * map接口
     * @var array
     */
    public static $api = [
        'gd_get_around'  => ['name' => '高德地图获取周边','url'=> 'https://restapi.amap.com/v3/place/around?parameters']
    ];

    public $_curl;
    private static $_instance;

    private function __construct(){
        $this->_curl = new Curl();
        $this->_curl->setOpt(CURLOPT_SSL_VERIFYPEER, FALSE);
        $this->_curl->setOpt(CURLOPT_SSL_VERIFYHOST, FALSE);
    }

    public static function getInstance(){
        if (!self::$_instance instanceof self){
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    /**
     * 获取周边
     * @param $location
     * @param null $keywords
     * @param null $types
     * @param null $city
     * @param null $radius
     * @param null $sortrule
     * @param null $offset
     * @param null $page
     * @return mixed
     */
    public function getAround($location,$keywords=null,$types=null,$city=null,$radius=null,$sortrule=null,$offset=null,$page=null)
    {
        $data= [
            "location"  => $location,
            'key'       => config("app.gd_map_key")
        ];
        isset($keywords) && $data["keywords"] = $keywords;
        isset($types) && $data["types"] = $types;
        isset($city) && $data["city"] = $city;
        isset($radius) && $data["radius"] = $radius;
        isset($sortrule) && $data["sortrule"] = $sortrule;
        isset($offset) && $data["offset"] = $offset;
        isset($page) && $data["page"] = $page;

        $url = self::$api['gd_get_around']['url'];
        $res = $this->getResult($url,$data,"get");

        return $res;
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
<?php


namespace App\Libraries\pay\wxpay;



class PayApi{

    private $wxpay;

    public function __construct($appId,$notify_url=null)
    {
        $conf['appid']      = $appId;
        $conf['mch_id']     = config("app.mch_id");
        $conf['key']        = config("app.key");
        $conf['notify_url'] = config("app.notify_url");

        if(isset($notify_url))
        {
            $conf['notify_url']=$notify_url;
            if(config('app.env')=='local')
                $conf['notify_url'] = config("app.wxpay_callback_url_test");

        }


          $this->wxpay = new WxjsLib($conf['appid'], $conf['mch_id'], $conf['key'], $conf['notify_url']);
    }

    /*
     * 微信支付的下单接口
     */
    function order($params){
        //微信公众号支付
            $params['trade_type']       = 'JSAPI';
            $params['attach']           = 'JSPAY';
            $params['scene_info']       = '';

            $ret=$this->wxpay->unifiedOrder($params);

            if(!isset($ret['prepay_id'])){
                return $ret;
            }

            return $this->h5pay($ret);
    }


    /**
     * 微信支付的下单接口
     */
    function otherOrder($params){
        //微信公众号支付
        $params['trade_type']       = 'MWEB';
        $params['attach']           = 'JSPAY';
        $params['scene_info']       = '';

        $ret=$this->wxpay->unifiedOrder($params);

        if(!isset($ret['prepay_id'])){
            return $ret;
        }

        return $this->h5pay($ret,true);
    }



    /*
     * 生成H5支付需要的参数
     */

    public function h5pay($ret,$outWechat=false){

        $params['appId']        = $ret['appid'];
        $params['timeStamp']    = time();
        $params['nonceStr']     = $this->wxpay->genRandomString();
        $params['package']      = "prepay_id=".$ret['prepay_id'];
        $params['signType']     = 'MD5';
        $params['paySign']      = $this->wxpay->MakeSign($params);

        if($outWechat)
        {
            $params['trade_type']      = "MWEB";
            $params['prepay_id']       = $ret['prepay_id'];
            $params['mweb_url']        = $ret['mweb_url'];
        }

        return $params;
    }

    /*
     * 企业付款到零钱
     */
    public function enchashOrder( $params ){

        return $this->wxpay->enchashOrder($params);
    }

    /**
     * 发红包
     * @param $params
     * @return bool|mixed
     * @throws \Exception
     */
    public function sendRedbag($params){
        return $this->wxpay->cashRedbag($params);
    }
}
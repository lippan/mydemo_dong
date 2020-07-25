<?php

namespace App\Libraries\weixin\lib;

class WxJsConfig
{


    public  $mchid='1455400602';
    public  $key='89e56e057f20f883ebbe56e057f20f88';
    public  $access_token_url='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential';
    public  $oauth_url="https://open.weixin.qq.com/connect/oauth2/authorize";
    public  $access_token="https://api.weixin.qq.com/sns/oauth2/access_token";
    public  $ip_token_url="https://api.weixin.qq.com/cgi-bin/getcallbackip?access_token=";
    public  $user_info='https://api.weixin.qq.com/sns/userinfo';


    //回调公众号信息
    public  $appid='wx8477a2ce3526a2ea';
    public  $appid_bak='wx523ab5f5922a022b';
    public  $appsecret='07d925908ef215158ae14c36ed54d3e6';
    public  $appsecret_bak='2d52986dd0d3612b4a7960421896b06d';

//    public  $appid='wx8b551960dbd1daee';
//    public  $appsecret='b730a3f7ee0e3d01e9c16d94b2d3d1b9';

    //pc回调公众号信息
    public  $pc_appid='wx9c3df3afd31d6d15';
    public  $pc_appsecret='08fd6e8e297dc2b8229e4289dfa70884';

}

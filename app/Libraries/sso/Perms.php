<?php

namespace App\Libraries\sso;
use Illuminate\Support\Facades\DB;

/**
 * 权限逻辑类
 */
class Perms extends Base{

    private static $instance;

    public static function getInstance(){
        if (!self::$instance instanceof self){
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * 权限同步
     * @param array $lists
     * @throws \Exception
     */
    public function permsSync(array $lists){
        $datastr = json_encode($lists,JSON_UNESCAPED_UNICODE);
        $access_token = Oauth2::getInstance()->getAccessToken();
        $url = "{$this->server_url}/openapi/perm/sync?access_token={$access_token}";
        $response = $this->curl($url,'post',$datastr,[
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: '.strlen($datastr)
        ]);
        $response['code'] == 1 or $this->exception($response['msg']);
    }

    /**
     * 验证操作权限
     * @param $token
     * @param $path
     * @return bool
     */
    public function checkPerm($user,$path){
        if ($user['is_admin'] == 1) return true;
        $user['perms'] = empty($user['perms']) ? [] : json_decode($user['perms'],true);
        return in_array($path,$user['perms']) ? true : false;
    }

    /**
     * @param $query
     * @param $table
     * @param string $data_id
     * @param string $field
     * @param string $where
     * @return mixed
     */
    public function dataPermWhere($query,$table,$data_id = '',$field = '',$where = '='){
        $result = $this->checkDataPerm($table,$data_id);
        if ($result === true){
            // 有指定id的数据权限
            return $query;
        }elseif(is_array($result)){
            // 查询所有数据权限
            if ($where == '='){
                return $query->whereIn($field,$result);
            }elseif($where == 'like'){
                foreach ($result as $v){
                    $query->where($field,'like',"{$v}%");
                }
                return $query;
            }
        }else{
            // 无数据权限
            return $query->where(DB::raw('1=0'));
        }
    }

    public function checkDataPerm($user,$table,$data_id = ''){
        // 需要验证的表
        $need_perms = isset($user['data_perms.need']) ? json_decode($user['data_perms.need'],true) : [];
        if (!in_array($table,$need_perms)) return true;//查看所有的

        // 获取已有权限
        $has_perms = isset($user['data_perms.has']) ? json_decode($user['data_perms.has'],true) : [];
        $has_perms = $has_perms[$table] ?? [];
        if ($data_id){
            if (!in_array($data_id,$has_perms)) return false;//无数据权限
        }else{
            // 返回已有权限配置
            return $has_perms;//满足条件的数组
        }
        return true;
    }
}
<?php

/**
 *  crm资源
 */
namespace App\Repositories\Api;
use App\Libraries\Crm\Crm;

use Illuminate\Support\Facades\DB;

class CrmRepository
{
    use \App\Repositories\BaseRepository;
    use \App\Helpers\ApiResponse;

    //crm请求日志表
    private $crm_request_log;

    //初始化需要用到的表名称
    public function __construct()
    {
        $this->crm_request_log       = config('db_table.crm_request_log');
    }

    public function getApiList()
    {
        $url = 'http://poweb.sanygroup.com:50000/dir/wsdl?p=ic/6d297855c1663dfaa57a028a88bcda1c';
        $crm = new Crm();
        $data = $crm->getFunctionsAndTypes($url);

        return $data;
    }


    /**
     * 推送数据到crm
     * @param $data
     */
    public function push($data)
    {
        $crm_data       = $data['crm_data'] ? $data['crm_data']: '';
        $crm_data       = $crm_data ? json_decode($crm_data,true) : [];

        $params = [
            'IS_HEADER' => [
                'CUST_NAME'     => $data['cust_name'] ? $data['cust_name']: '',
                'CUST_TEL'      => $data['cust_tel'] ?  $data['cust_tel']:'',
                'CUST_TYPE'     => isset($data['cust_type']) ? $data['cust_type']:1,
                'SOURCE'        => 'Z10',
                'USER_ID'       => $data['crm_id'] ? $data['crm_id']: '',
                'ZZPROVINCE'    => $data['province'] ? $data['province']: '',
                'CITY'          => $data['city'] ? $data['city']: '',
                'STREET'        => $crm_data['street'] ? $crm_data['street']:'',
                'SIGN_TEL'      => $crm_data['sign_tel'] ? $crm_data['sign_tel']:'',
                'DESCRIPTION'   => $crm_data['description'] ? $crm_data['description']:'',
                'ZZVSOURCE'     => isset($data['source']) ? $data['source'] : '',
            ],
            'IT_ITEM' => [
                'CATEGORY_ID' => $data['category'] ? $data['category']: '',
            ],
            'IS_PAYMENT' => [
                'FKBH'      => '',
                'FKJE'      => '',
                'FKRQ'      => '',
                'FKSJ'      => '',
                'DDXDBH'    => '',
                'ddbz'      => '',
            ],
        ];

        //记录请求数据体
        $logId = $this->write_log($params,"push_clue_data");

        $crm = new Crm();
        try{
            $res = $crm->postCrm($params);
        }
        catch (\Exception $e)
        {
            //记录请求异常日志
            $this->write_log($params,"push_clue_data",$logId,"failed",null,"请求异常:".$e->getMessage());
            exit;
        }
        //记录请求结果
        $this->write_log($params,"push_clue_data",$logId,"success","EV_OBJECT_ID:".$res);

        return $res;
    }


    /**
     * 按营销代表手机号获取crm系统标识id
     * @param $mobile
     * @return $crmId
     */
    public function getCrmId($mobile)
    {
        //TODO  通过手机号查询crmid是否存在于营销代表中

        $logId = $this->write_log($mobile,"get_crm_id");

        $crmId = 0;
        $crm = new Crm();

        try{
            $crmId = $crm->getUserId($mobile);
        }catch (\Exception $e){
            //记录请求异常日志
            $this->write_log($mobile,"get_crm_id",$logId,"failed",null,"请求异常:".$e->getMessage());
            exit;
        }

        //记录请求结果
        $this->write_log($mobile,"push_clue_data",$logId,"success","ET_USER_USER_ID:".$crmId);

        return $crmId;
    }


    /**
     * 更新数据
     * @param $data
     */
    public function update($data)
    {
        if (!isset($data['object_id']) || !isset($data['order_money']) || !isset($data['order_sn']) || !isset($data['pay_time'])){
            return response()->json([
                'code' => 0,
                'msg' => 'CRM订单ID、小店订单号、订单金额、支付时间不能为空'
            ]);
        }
        $crm = new Crm();
        try{
            $res = $crm->putCrm($data);
        }catch (\Exception $e){

        }

    }


    /**
     * 获取组织架构
     */
    public function getOrganizeTree()
    {
        $crm = new Crm();

    }



    /**
     * 记录请求日志
     * @param $data
     * @param $type
     * @param string $msg
     */
    protected function write_log($data,$type,$logId=null,$status="running",$response = null,$msg="请求进来了")
    {
        if($status == "running")
        {
            $rdata = [
                "data"          => json_encode($data,JSON_UNESCAPED_UNICODE),//中文不转义
                "type"          => $type,
                "response"      => "",
                "status"        => $status,
                "error_msg"     => $msg,
                "create_time"   => date("Y-m-d H:i:s")
            ];
            $logId = DB::table($this->crm_request_log)->insertGetId($rdata);

            return $logId;
        }
        if($status == "failed")
        {
            $rdata = [
                "status"        => "failed",
                "error_msg"     => $msg,
            ];

            DB::table($this->crm_request_log)->where("id",$logId)->update($rdata);
        }
        if($status == "success")
        {
            $rdata = [
                "status"        => "success",
                "response"      => $response,
            ];

            DB::table($this->crm_request_log)->where("id",$logId)->update($rdata);
        }
    }

}
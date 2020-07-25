<?php

/**
 *  app端审核资源
 */
namespace App\Repositories\App;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class VerifyRepository
{
    use \App\Helpers\ApiResponse;

    private $user_table;
    private $agreement;
    private $flow_business_log;

    //初始化需要用到的表名称
    public function __construct()
    {
        $this->user_table           = config('db_table.user_table');
        $this->agreement            = config('db_table.agreement');
        $this->flow_business_log    = config('db_table.flow_business_log');
    }




    /**
     * 审核合同逻辑
     * @param $uid
     * @param $agreeId
     * @param string $status
     * @param string $suggestion
     * @return bool
     */
    public function agreement($uid,$agreeId,$status="pass",$suggestion="")
    {
        //查询当前合同是在审核中的
        $row = DB::table($this->agreement)->where("agree_id",$agreeId)->first();
        if(empty($row))
            $this->failed("合同不存在");

        if($row->status != "verifying")
            $this->failed("合同不能审核");

        //查询当前审核id是否已经是进行过审核的,通过code查找   agree_id 不是唯一值
        $where = [
            "type"          => "agreement",
            "code"          => $row->verify_code,
            "uid"           => $uid,
        ];
        $srow = DB::table($this->flow_business_log)->where($where)->orderBy("verify_id","desc")->first();
        if(empty($srow))
            $this->failed("访问出错");

        if($srow->verify_status != "wait")
            $this->failed("不能重复审核");

        $data["verify_status"]  = $status;
        $data["verify_time"]    = date("Y-m-d H:i:s");
        $data["suggestion"]     = $suggestion;

        $bool = DB::table($this->flow_business_log)->where("verify_id",$srow->verify_id)->update($data);
        if(empty($bool))
            $this->failed("审核失败");

        if($status == "pass")//如果等于通过
        {
            //则查询是否审核节点全部完成
            $isFinished = $this->isFinishVerify($srow->code);
            if($isFinished)//完成则增加完成节点,并更新合同状态 已完成
                $this->doFinishVerify($agreeId,$srow->flow_id);
        }
        else
            //如果没有通过，则将合同状态设置为  驳回
            $this->setAgreementNotPass($agreeId);

        return $bool;
    }


    /**
     * 审核详情
     * @param $agreeId
     * @return object
     */
    public function info($agreeId)
    {
        //首先查看该合同是否撤销或者作废，如果是则管理者是看不到该合同的
        $row = DB::table($this->agreement)->where("agree_id",$agreeId)->first();
        if(empty($row))
            $this->failed("合同不存在");

        if(empty($row->verify_code))
            $this->failed("没有审核信息");

        $list = DB::table($this->flow_business_log)->where("code",$row->verify_code)->get();

        return $list;

    }

    /**
     * 获取该用户 关联审核的合同id
     * @param $userId
     * @return array
     */
    public function getAgreementIds($userId)
    {
        //找到所有该账号需要审核的合同id
        $where = [
            "type"          => "agreement",
            "uid"           => $userId,
        ];
        $arr = DB::table($this->flow_business_log)->where($where)->groupBy("business_id")->pluck("business_id")->toArray();

        return $arr;
    }


    /**
     * 查询该流程是否全部完成
     * @param $code
     * @return bool
     */
    protected function isFinishVerify($code)
    {
        $has = DB::table($this->flow_business_log)->where("code",$code)->where("verify_status","<>","finished")->first();
        return empty($has) ? true:false;
    }


    /**
     * 设置合同为驳回状态-未通过
     * @param $agreeId
     * @return int
     */
    protected function setAgreementNotPass($agreeId)
    {
        $bool = DB::table($this->agreement)->where("agree_id",$agreeId)->update(["status"=>"not_pass"]);
        return $bool;
    }

    /**
     * 增加流程审核完毕的闭合节点
     * @param $agreeId
     * @param $flowId
     * @return bool
     */
    protected function doFinishVerify($agreeId,$flowId)
    {
        $data = ["type"=>"agreement","code"=>"","business_id"=>$agreeId,"flow_id"=>$flowId,"node_id"=>0,"node_name"=>"流程结束","uid"=>0,"verify_status"=>"finished","suggestion"=>"","verify_time"=> date("Y-m-d H:i:s")];

        //此处需要事务操作
        try{
            DB::transaction(function () use($data,$agreeId){
                DB::table($this->flow_business_log)->insert($data);
                DB::table($this->agreement)->where("agree_id",$agreeId)->update(["status"=>"finished"]);
            });
        }catch(QueryException $ex){
            $this->failed("审核流程完成更新失败");
        }
    }
}
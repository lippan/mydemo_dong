<?php

/**
 *  后台-合同资源
 */
namespace App\Repositories\Admin;
use Illuminate\Support\Facades\DB;

class AgreementRepository
{
    use \App\Helpers\ApiResponse;

    //客户表
    private $customer;
    private $agreement;
    private $agreement_attachment;
    private $source;
    private $clue;
    private $user;
    private $business;
    private $flow_node;
    private $flow_business_log;

    private $base_url;//图片地址

    //初始化需要用到的表名称
    public function __construct()
    {
        $this->customer                 = config('db_table.customer_table');
        $this->clue                     = config('db_table.clue_table');
        $this->user                     = config('db_table.user_table');
        $this->source                   = config('db_table.crm_source');
        $this->business                 = config('db_table.business_table');
        $this->agreement                = config('db_table.agreement_table');
        $this->agreement_attachment     = config('db_table.agreement_attachment');
        $this->flow_node                = config('db_table.flow_node');
        $this->flow_business_log        = config('db_table.flow_business_log');
        $this->base_url                 = config('oss.base_url');
    }


    /**
     * 列表
     */
    public function lists($params)
    {

        $page       = !empty($params['page'])       ? (int)$params['page']:1;
        $page_nums  = !empty($params['page_nums'])  ? (int)$params['page_nums']:10;
        $offset     = ($page-1) * $page_nums;

        //如果是超级管理员
        $list = $this->adminList($params,$offset,$page_nums);

        return $list;
    }

    /**
     * 合同详情
     * @param $agreeId
     * @return object
     */
    public function info($agreeId,$uid)
    {
        $row = DB::table($this->agreement." as a")
            ->leftJoin($this->customer." as b","a.customer_id","=","b.customer_id")
            ->where("a.agree_id",$agreeId)
            ->first(["a.*","b.name"]);

        if(empty($row))
            $this->failed("访问出错");

        $result = new \stdClass();
        $result->agreement_info = $this->parseJson($row);

        //获取该合同的附件
        $arow = DB::table($this->agreement_attachment)->where("agree_id",$agreeId)->first();
        if(empty($arow))
            $this->failed("访问出错");

        $result->attachment_id   = $arow->attachment_id;
        $result->attachment_info = $this->attachment_info($arow->attachment_id);

        //获取审批信息
        if($row->status != "store")
        {
            $where = [
                //"a.type"          => "agreement",
                //"a.business_id"   => $agreeId
                "a.code"    => $row->verify_code
            ];

            $list = DB::table($this->flow_business_log." as a")
                ->leftJoin($this->user." as b","a.uid","=","b.uid")
                ->where($where)
                ->orderBy("a.verify_id","asc")
                ->get(["a.*","b.username","a.uid"]);

            $result->verify_list = $list;
            //是否具有审核权限  TODO
            $is_verifyer = 0;
            if(!$list->isEmpty())
            {
                foreach($list as $val)
                {
                    if($val->uid == $uid)
                        $is_verifyer =1;
                }
            }
            $result->is_verifyer = $is_verifyer;

        }

        return $result;
    }

    /**
     * 附件详情
     * @param $attachmentId
     * @return array
     */
    public function attachment_info($attachmentId)
    {
        $row = DB::table($this->agreement_attachment)->where("attachment_id",$attachmentId)->first();
        if(empty($row))
            $this->failed("访问出错");

        $srow = [];

        !empty($row->business_license) && $srow["business_license"] = $this->connectUrl($row->business_license);
        !empty($row->id_card) && $srow["id_card"] = $this->connectUrl($row->id_card);
        !empty($row->marry_card) && $srow["marry_card"] = $this->connectUrl($row->marry_card);
        !empty($row->guarantor_card) && $srow["guarantor_card"] =  $this->connectUrl($row->guarantor_card);
        !empty($row->guarantor_marry_card) && $srow["guarantor_marry_card"] = $this->connectUrl($row->guarantor_marry_card);
        !empty($row->buyer_credit_report) && $srow["buyer_credit_report"] = $this->connectUrl($row->buyer_credit_report);
        !empty($row->guarantor_credit_report) && $srow["guarantor_credit_report"] = $this->connectUrl($row->guarantor_credit_report);
        !empty($row->company_credit_warrant) && $srow["company_credit_warrant"] = $this->connectUrl($row->company_credit_warrant);
        !empty($row->buyer_face_sign) && $srow["buyer_face_sign"] = $this->connectUrl($row->buyer_face_sign);
        !empty($row->guarantor_face_sign) && $srow["guarantor_face_sign"] = $this->connectUrl($row->guarantor_face_sign);
        !empty($row->third_guarantor_card) && $srow["third_guarantor_card"] = $this->connectUrl($row->third_guarantor_card);
        !empty($row->third_guarantor_credit_report) && $srow["third_guarantor_credit_report"] = $this->connectUrl($row->third_guarantor_credit_report);
        !empty($row->guarantor_bank_cert) && $srow["guarantor_bank_cert"] = $this->connectUrl($row->guarantor_bank_cert);

        return $srow;
    }


    /**
     * 超级管理员查看列表
     * @param $params
     * @param $offset
     * @param $page_nums
     * @return object
     */
    protected  function adminList($params,$offset,$page_nums)
    {
        $field  = !empty($params["field"]) ? $params["field"]:"create_time";
        $sort   = !empty($params["sort"]) ? $params["sort"]:"desc";

        $where = [];
        isset($params["customer_id"]) && $where[] = ["a.customer_id","=",$params["customer_id"]];

        $list = DB::table($this->agreement." as a")
            ->leftJoin($this->customer." as b","a.customer_id","=","b.customer_id")
            ->leftJoin($this->user." as c","c.uid","=","a.uid")
            ->where($where)
            ->orderby('a.'.$field,$sort)
            ->offset($offset)
            ->limit($page_nums)
            ->get(["a.agree_id","a.business_id","a.category_name","a.create_time","a.status","a.price","b.name","b.company_name","c.username"]);

        return $list;
    }


    protected function parseJson($row)
    {
        if(!empty($row->product_params))
            $params = json_decode($row->product_params,true);

        if(!empty($params))
            foreach($params as $key=>$val)
                $row->$key = $val;

        if(!empty($row->pay_params))
            $params2 = json_decode($row->pay_params,true);

        if(!empty($params2))
            foreach($params2 as $key=>$val)
                $row->$key = $val;

        return $row;
    }

    /**
     * 拼接绝对路径url地址
     * @param $picJsonStr
     * @return mixed
     */
    protected function connectUrl($picJsonStr)
    {
        $arr = json_decode($picJsonStr,true);
        foreach($arr as &$val)
            $val = $this->base_url.$val;

        return $arr;
    }

}
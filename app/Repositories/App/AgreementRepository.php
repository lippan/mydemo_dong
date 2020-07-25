<?php

/**
 *  合同资源
 */
namespace App\Repositories\App;
use App\Repositories\Business\WaitDoneRepository;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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
    public function lists($params,$uid,$crm_user_id,$is_admin,$isDepartAdmin=null,$level=null)
    {

        $page       = !empty($params['page'])       ? (int)$params['page']:1;
        $page_nums  = !empty($params['page_nums'])  ? (int)$params['page_nums']:10;
        $offset     = ($page-1) * $page_nums;

        //如果是超级管理员
        if($is_admin)
        {
            $list = $this->adminList($params,$offset,$page_nums);
            return $list;
        }

        if(empty($crm_user_id))
            $list = $this->normalList($uid,$isDepartAdmin,$level,$params,$offset,$page_nums);
        else
            $list = $this->crmList($uid,$crm_user_id,$params,$offset,$page_nums);

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
     * 提交审批 默认为审核流程1
     * @param $uid
     * @param $agreeId
     * @param $flowId
     * @return array
     */
    public function apply_verify($uid,$agreeId,$flowId=1)
    {
        $where  = [
            "uid"       => $uid,
            "agree_id"  => $agreeId
        ];
        $row    = DB::table($this->agreement)->where($where)->first();
        if(empty($row))
            $this->failed("您没有提交权限");

        //判断该合同状态是否在审核中  TODO ???
        if($row->status != "store")
            $this->failed("合同已经在审核中！");

        //生成唯一编码
        $slat = rand_str(6);
        $code = Hash::make(time().$slat);

        $data[] = ["type"=>"agreement","code"=>"","business_id"=>$agreeId,"flow_id"=>$flowId,"node_id"=>0,"node_name"=>"营销代表","uid"=>$uid,"verify_status"=>"apply","suggestion"=>"提交合同评审","verify_time"=> date("Y-m-d H:i:s")];

        $list   = DB::table($this->flow_node)->where("flow_id",$flowId)->orderBy("sort","asc")->get();
        foreach($list as $val)
        {
            $data[] = ["type"=>"agreement","code"=>$code,"business_id"=>$agreeId,"flow_id"=>$flowId,"node_id"=>$val->node_id,"node_name"=>$val->node_name,"verify_status"=>"wait","uid"=>$val->uid,"suggestion"=>NULL,"verify_time"=> NULL];
        }

//        $bool = DB::table($this->flow_business_log)->insert($data);
//        if(!empty($bool))//更新合同状态为审核中，并将该合同的审核编号记录下来
//        {
//            DB::table($this->agreement)->where($where)->update(["status"=>"verifying","verify_code"=>$code]);
//        }

        //此处需要事务操作
        try{
            DB::transaction(function () use($data,$row,$code){
                DB::table($this->flow_business_log)->insert($data);
                DB::table($this->agreement)->where("agree_id",$row->agree_id)->update(["status"=>"verifying","verify_code"=>$code]);
                DB::table($this->customer)->where("customer_id",$row->customer_id)->update(["last_follow_time"=>date("Y-m-d H:i:s")]);
            });
        }catch(QueryException $ex){
            $this->failed("提交审核失败");
        }

        return $data;
    }


    /**
     * 设置合同状态
     * @param $uid
     * @param $agreeId
     * @param $status
     * @return bool
     */
    public function setAgreementStatus($uid,$agreeId,$status="recall")
    {
        $where  = [
            "uid"           => $uid,
            "agree_id"      => $agreeId
        ];
        $row    = DB::table($this->agreement)->where($where)->first();
        if(empty($row))
            $this->failed("您没有权限");

        $bool   = DB::table($this->agreement)->where("agree_id",$agreeId)->update(["status"=>$status]);

        return $bool;
    }


    /**
     * 添加
     */
    public function add($params)
    {
        //通过商机id，获取客户id
        $row = DB::table($this->business)->where("business_id",$params["business_id"])->first();
        if(empty($row))
            $this->failed("商机不能为空");

        $arr = config("app.category_arr");

        $data = [
            "uid"               => $params["uid"],
            "customer_id"       => $row->customer_id,
            "business_id"       => $params["business_id"],
            "category_id"       => $params["category_id"],
            "category_name"     => $arr[$params["category_id"]],
            "customer_industry" => $params["customer_industry"],
            "is_normal_agree"   => $params["is_normal_agree"],
            "is_first_interest" => $params["is_first_interest"],
            "is_old_customer"   => $params["is_old_customer"],
            "is_over_due"       => $params["is_over_due"],
            "is_more_money"     => $params["is_more_money"],
            "send_time"         => $params["send_time"],
            "receive_type"      => $params["receive_type"],
            "mail_rate"         => $params["mail_rate"],
            "is_good_price_sale"=> $params["is_good_price_sale"],
            "product_name"      => $params["product_name"],
            "product_sn"        => $params["product_sn"],
            "buy_num"           => $params["buy_num"],
            "price"             => $params["price"],
            "create_time"       => date("Y-m-d H:i:s")
        ];
        //处理履带起重机的 特殊参数
        $special_params = [];
        isset($params["main_arm"]) && $special_params["main_arm"] = $params["main_arm"];//主臂
        isset($params["sub_arm"]) && $special_params["sub_arm"]   = $params["sub_arm"];//固定副臂
        isset($params["change_sub_arm"]) && $special_params["change_sub_arm"]   = $params["change_sub_arm"];//变幅副臂
        isset($params["hook"]) && $special_params["hook"]   = $params["hook"];//吊钩
        isset($params["has_free_hook"]) && $special_params["has_free_hook"]   = $params["has_free_hook"];//是否带自由落钩
        if(!empty($special_params))
            $data["product_params"] = json_encode($special_params);

        isset($params["customer_remark"]) && $data["customer_remark"] = $params["customer_remark"];
        isset($params["qa_time"]) && $data["qa_time"] = $params["qa_time"];
        isset($params["qa_month"]) && $data["qa_month"] = $params["qa_month"];
        isset($params["qa_unit"]) && $data["qa_unit"] = $params["qa_unit"];
        isset($params["qa_value"]) && $data["qa_value"] = $params["qa_value"];
        isset($params["product_config_remark"]) && $data["product_config_remark"] = $params["product_config_remark"];
        isset($params["pay_type"]) && $data["pay_type"] = $params["pay_type"];

        //如果付款方式不是全款，则需要提供哪些字段
        $spay_params = [];
        if(isset($params["pay_type"]) && $params["pay_type"] > 0)
        {
            isset($params["finance_name"]) && $spay_params["finance_name"] = $params["finance_name"];//融资方名称
            isset($params["rate"]) && $spay_params["rate"]   = $params["rate"];//利率
            isset($params["first_pay"]) && $spay_params["first_pay"]   = $params["first_pay"];//首付
            isset($params["first_pay_time"]) && $spay_params["first_pay_time"]   = $params["first_pay_time"];//首付时间
            isset($params["bank_loans_money"]) && $spay_params["bank_loans_money"]   = $params["bank_loans_money"];//银行贷款金额
            isset($params["bank_loans_month"]) && $spay_params["bank_loans_month"]   = $params["bank_loans_month"];//银行贷款期数
            isset($params["first_payback_time"]) && $spay_params["first_payback_time"] = $params["first_payback_time"];//首次还款时间
            isset($params["delay_time"]) && $spay_params["delay_time"] = $params["delay_time"];//延期放款
            isset($params["send_money_date"]) && $spay_params["send_money_date"] = $params["send_money_date"];//放款日期
            isset($params["first_payback_date"]) && $spay_params["first_payback_date"] = $params["first_payback_date"];//首次还款日期

            $data["pay_params"] = json_encode($spay_params);
        }
        if(isset($params["pay_type"]) && $params["pay_type"] == 0)
        {
            isset($params["advance_order_money"]) && $spay_params["advance_order_money"] = $params["advance_order_money"];//预付定金
            isset($params["first_pay"]) && $spay_params["first_pay"] = $params["first_pay"];//预付定金
            isset($params["first_pay_time"]) && $spay_params["first_pay_time"]   = $params["first_pay_time"];//首付时间
            isset($params["remain_money"]) && $spay_params["remain_money"]   = $params["remain_money"];//余款
            isset($params["remain_pay_type"]) && $spay_params["remain_pay_type"]   = $params["remain_pay_type"];//余款支付方式
            isset($params["qa_money"]) && $spay_params["qa_money"]   = $params["qa_money"];//质保金
            isset($params["qa_pay_type"]) && $spay_params["qa_pay_type"]   = $params["qa_pay_type"];//质保金支付方式
            isset($params["other_money"]) && $spay_params["other_money"]   = $params["other_money"];//其他费用

            $data["pay_params"] = json_encode($spay_params);
        }

        isset($params["earnest_money"]) && $data["earnest_money"] = $params["earnest_money"];// 保证金
        isset($params["charges"]) && $data["charges"] = $params["charges"];//手续费
        isset($params["mortgage_money"]) && $data["mortgage_money"] = $params["mortgage_money"];//抵押公证费
        isset($params["safe_money"]) && $data["safe_money"] = $params["safe_money"];//保险费用
        isset($params["remark"]) && $data["remark"] = $params["remark"];//备注

        $agreeId = DB::table($this->agreement)->insertGetId($data);

        DB::table($this->customer)->where("customer_id",$row->customer_id)->update(["last_follow_time"=>date("Y-m-d H:i:s")]);
        //添加附件信息
        $this->add_attachment($params,$agreeId);

        return $agreeId;
    }


    /**
     * 编辑
     */
    public function edit($params,$agreeId)
    {
        $cRow = DB::table($this->agreement)->where("agree_id",$agreeId)->first();
        if(empty($cRow))
            $this->failed("访问出错");

        $data = [
            "customer_industry" => $params["customer_industry"],
            "is_normal_agree"   => $params["is_normal_agree"],
            "is_first_interest" => $params["is_first_interest"],
            "is_old_customer"   => $params["is_old_customer"],
            "is_over_due"       => $params["is_over_due"],
            "is_more_money"     => $params["is_more_money"],
            "send_time"         => $params["send_time"],
            "receive_type"      => $params["receive_type"],
            "mail_rate"         => $params["mail_rate"],
            "is_good_price_sale"=> $params["is_good_price_sale"],
            "product_name"      => $params["product_name"],
            "product_sn"        => $params["product_sn"],
            "buy_num"           => $params["buy_num"],
            "price"             => $params["price"],
        ];

        //处理履带起重机的 特殊参数
        $special_params = [];
        isset($params["main_arm"]) && $special_params["main_arm"] = $params["main_arm"];//主臂
        isset($params["sub_arm"]) && $special_params["sub_arm"]   = $params["sub_arm"];//固定副臂
        isset($params["change_sub_arm"]) && $special_params["change_sub_arm"]   = $params["change_sub_arm"];//变幅副臂
        isset($params["hook"]) && $special_params["hook"]   = $params["hook"];//吊钩
        isset($params["has_free_hook"]) && $special_params["has_free_hook"]   = $params["has_free_hook"];//是否带自由落钩
        if(!empty($special_params))
            $data["product_params"] = json_encode($special_params);

        isset($params["customer_remark"]) && $data["customer_remark"] = $params["customer_remark"];
        isset($params["qa_time"]) && $data["qa_time"] = $params["qa_time"];
        isset($params["qa_month"]) && $data["qa_month"] = $params["qa_month"];
        isset($params["qa_unit"]) && $data["qa_unit"] = $params["qa_unit"];
        isset($params["qa_value"]) && $data["qa_value"] = $params["qa_value"];
        isset($params["product_config_remark"]) && $data["product_config_remark"] = $params["product_config_remark"];
        isset($params["pay_type"]) && $data["pay_type"] = $params["pay_type"];

        //如果付款方式不是全款，则需要提供哪些字段
        $spay_params = [];
        if(isset($params["pay_type"]) && $params["pay_type"] != "full")
        {
            isset($params["finance_name"]) && $spay_params["finance_name"] = $params["finance_name"];//融资方名称
            isset($params["rate"]) && $spay_params["rate"]   = $params["rate"];//利率
            isset($params["first_pay"]) && $spay_params["first_pay"]   = $params["first_pay"];//首付
            isset($params["first_pay_time"]) && $spay_params["first_pay_time"]   = $params["first_pay_time"];//首付时间
            isset($params["bank_loans_money"]) && $spay_params["bank_loans_money"]   = $params["bank_loans_money"];//银行贷款金额
            isset($params["bank_loans_month"]) && $spay_params["bank_loans_month"]   = $params["bank_loans_month"];//银行贷款期数
            isset($params["first_payback_time"]) && $spay_params["first_payback_time"] = $params["first_payback_time"];//首次还款时间
            isset($params["delay_time"]) && $spay_params["delay_time"] = $params["delay_time"];//延期放款
            isset($params["send_money_date"]) && $spay_params["send_money_date"] = $params["send_money_date"];//放款日期
            isset($params["first_payback_date"]) && $spay_params["first_payback_date"] = $params["first_payback_date"];//首次还款日期

            $data["pay_params"] = json_encode($spay_params);
        }
        if(isset($params["pay_type"]) && $params["pay_type"] == "full")
        {
            isset($params["advance_order_money"]) && $spay_params["advance_order_money"] = $params["advance_order_money"];//预付定金
            isset($params["first_pay"]) && $spay_params["first_pay"] = $params["first_pay"];//预付定金
            isset($params["first_pay_time"]) && $spay_params["first_pay_time"]   = $params["first_pay_time"];//首付时间
            isset($params["remain_money"]) && $spay_params["remain_money"]   = $params["remain_money"];//余款
            isset($params["remain_pay_type"]) && $spay_params["remain_pay_type"]   = $params["remain_pay_type"];//余款支付方式
            isset($params["qa_money"]) && $spay_params["qa_money"]   = $params["qa_money"];//质保金
            isset($params["qa_pay_type"]) && $spay_params["qa_pay_type"]   = $params["qa_pay_type"];//质保金支付方式
            isset($params["other_money"]) && $spay_params["other_money"]   = $params["other_money"];//其他费用

            $data["pay_params"] = json_encode($spay_params);
        }

        isset($params["earnest_money"]) && $data["earnest_money"] = $params["earnest_money"];
        isset($params["charges"]) && $data["charges"] = $params["charges"];
        isset($params["mortgage_money"]) && $data["mortgage_money"] = $params["mortgage_money"];
        isset($params["safe_money"]) && $data["safe_money"] = $params["safe_money"];
        isset($params["remark"]) && $data["remark"] = $params["remark"];

        if(empty($data))
            return true;

        $bool = DB::table($this->agreement)->where("agree_id",$agreeId)->update($data);

        DB::table($this->customer)->where("customer_id",$cRow->customer_id)->update(["last_follow_time"=>date("Y-m-d H:i:s")]);
        //如果附件修改了
        $this->edit_attachment($params,$agreeId);

        return $bool;
    }


    /**
     *  添加附件
     */
    public function add_attachment($params,$agreeId)
    {
        //如果该合同已经有了附件，则不再增加
        $row = DB::table($this->agreement_attachment)->where("agree_id",$agreeId)->first();
        if(!empty($row))
            $this->failed("附件已存在！请勿重复添加！");

        $data = [];
        isset($params["business_license"]) && count(array_filter($params["business_license"])) && $data["business_license"] = json_encode($params["business_license"]);
        isset($params["id_card"]) && count(array_filter($params["id_card"])) && $data["id_card"] = json_encode($params["id_card"]);
        isset($params["marry_card"]) && count(array_filter($params["marry_card"])) && $data["marry_card"] = json_encode($params["marry_card"]);
        isset($params["guarantor_card"]) && count(array_filter($params["guarantor_card"])) && $data["guarantor_card"] = json_encode($params["guarantor_card"]);
        isset($params["guarantor_marry_card"])  && count(array_filter($params["guarantor_marry_card"])) && $data["guarantor_marry_card"] = json_encode($params["guarantor_marry_card"]);
        isset($params["buyer_credit_report"]) && count(array_filter($params["buyer_credit_report"])) && $data["buyer_credit_report"] = json_encode($params["buyer_credit_report"]);
        isset($params["guarantor_credit_report"]) && count(array_filter($params["guarantor_credit_report"])) && $data["guarantor_credit_report"] = json_encode($params["guarantor_credit_report"]);
        isset($params["company_credit_warrant"]) && count(array_filter($params["company_credit_warrant"])) && $data["company_credit_warrant"] = json_encode($params["company_credit_warrant"]);
        isset($params["buyer_face_sign"]) && count(array_filter($params["buyer_face_sign"])) && $data["buyer_face_sign"] = json_encode($params["buyer_face_sign"]);
        isset($params["guarantor_face_sign"])  && count(array_filter($params["guarantor_face_sign"])) && $data["guarantor_face_sign"] = json_encode($params["guarantor_face_sign"]);
        isset($params["third_guarantor_card"]) && count(array_filter($params["third_guarantor_card"])) && $data["third_guarantor_card"] = json_encode($params["third_guarantor_card"]);
        isset($params["third_guarantor_credit_report"]) && count(array_filter($params["third_guarantor_credit_report"])) && $data["third_guarantor_credit_report"] = json_encode($params["third_guarantor_credit_report"]);
        isset($params["guarantor_bank_cert"]) && count(array_filter($params["guarantor_bank_cert"])) && $data["guarantor_bank_cert"] = json_encode($params["guarantor_bank_cert"]);

        if(empty($data))
            $this->failed("附件内容不能为空");

        $data["agree_id"] = $agreeId;

        //$row = DB::table($this->agreement)->where("agree_id",$agreeId)->first();
        //if(empty($row))
           // $this->failed("访问出错");

        $attachmentId = DB::table($this->agreement_attachment)->insertGetId($data);

        return $attachmentId;
    }

    /**
     *  修改附件
     */
    public function  edit_attachment($params,$agreeId)
    {
        $row = DB::table($this->agreement_attachment)->where("agree_id",$agreeId)->first();
        if(empty($row))
            $this->failed("访问出错");

        $data =[];
        isset($params["business_license"]) && count(array_filter($params["business_license"])) && $data["business_license"] = json_encode($params["business_license"]);
        isset($params["id_card"]) && count(array_filter($params["id_card"])) && $data["id_card"] = json_encode($params["id_card"]);
        isset($params["marry_card"]) && count(array_filter($params["marry_card"])) && $data["marry_card"] = json_encode($params["marry_card"]);
        isset($params["guarantor_card"]) && count(array_filter($params["guarantor_card"])) && $data["guarantor_card"] = json_encode($params["guarantor_card"]);
        isset($params["guarantor_marry_card"])  && count(array_filter($params["guarantor_marry_card"])) && $data["guarantor_marry_card"] = json_encode($params["guarantor_marry_card"]);
        isset($params["buyer_credit_report"]) && count(array_filter($params["buyer_credit_report"])) && $data["buyer_credit_report"] = json_encode($params["buyer_credit_report"]);
        isset($params["guarantor_credit_report"]) && count(array_filter($params["guarantor_credit_report"])) && $data["guarantor_credit_report"] = json_encode($params["guarantor_credit_report"]);
        isset($params["company_credit_warrant"]) && count(array_filter($params["company_credit_warrant"])) && $data["company_credit_warrant"] = json_encode($params["company_credit_warrant"]);
        isset($params["buyer_face_sign"]) && count(array_filter($params["buyer_face_sign"])) && $data["buyer_face_sign"] = json_encode($params["buyer_face_sign"]);
        isset($params["guarantor_face_sign"])  && count(array_filter($params["guarantor_face_sign"])) && $data["guarantor_face_sign"] = json_encode($params["guarantor_face_sign"]);
        isset($params["third_guarantor_card"]) && count(array_filter($params["third_guarantor_card"])) && $data["third_guarantor_card"] = json_encode($params["third_guarantor_card"]);
        isset($params["third_guarantor_credit_report"]) && count(array_filter($params["third_guarantor_credit_report"])) && $data["third_guarantor_credit_report"] = json_encode($params["third_guarantor_credit_report"]);
        isset($params["guarantor_bank_cert"]) && count(array_filter($params["guarantor_bank_cert"])) && $data["guarantor_bank_cert"] = json_encode($params["guarantor_bank_cert"]);


        if(empty($data))
            return true;

        $bool = DB::table($this->agreement_attachment)->where("attachment_id",$row->attachment_id)->update($data);

        return $bool;
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
     * 线索历史
     * @param $params
     * @return object
     */
    public function  history($params,$uid,$crm_user_id,$is_admin,$isDepartAdmin=null,$level=null)
    {
        $page       = !empty($params['page'])       ? (int)$params['page']:1;
        $page_nums  = !empty($params['page_nums'])  ? (int)$params['page_nums']:10;
        $offset     = ($page-1) * $page_nums;

        //如果是超级管理员
        if($is_admin)
        {
            $list = $this->adminList($params,$offset,$page_nums);
            return $list;
        }

        if(empty($crm_user_id))
            $list = $this->normalList($uid,$isDepartAdmin,$level,$params,$offset,$page_nums);
        else
            $list = $this->crmList($uid,$crm_user_id,$params,$offset,$page_nums);

        return $list;
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


    //不具备crm属性用户列表
    protected function normalList($uid,$isDepartAdmin,$level,$params,$offset,$page_nums)
    {
        $field  = !empty($params["field"]) ? $params["field"]:"create_time";
        $sort   = !empty($params["sort"]) ? $params["sort"]:"desc";

        isset($params["customer_id"]) && $where[] = ["a.customer_id","=",$params["customer_id"]];

        if($isDepartAdmin)
            $where[] = ['a.level', 'like', $level."%"];
        else
            $where[] = ['a.uid', '=', $uid];

        $list = DB::table($this->agreement." as a")
            ->leftJoin($this->customer." as b","a.customer_id","=","b.customer_id")
            ->leftJoin($this->user." as c","c.uid","=","a.uid")
            ->where($where)
            ->orderby('a.'.$field,$sort)
            ->offset($offset)
            ->limit($page_nums)
            ->get(["a.agree_id","a.business_id","a.category_name","a.create_time","a.status","a.price","b.name","b.company_name","c.username"]);

        $data["list"] = $list;

        return $data;
    }

    //crm属性用户列表
    protected function crmList($uid,$crm_user_id,$params,$offset,$page_nums)
    {
        $field  = !empty($params["field"]) ? $params["field"]:"create_time";
        $sort   = !empty($params["sort"]) ? $params["sort"]:"desc";

        $userArr = getUserAuth($crm_user_id,$uid);

        //查询该用户 关联审核的 合同id
        $verifyRepos = new VerifyRepository();
        $aidArr = $verifyRepos->getAgreementIds($uid);

        $where = [];
        isset($params["customer_id"]) && $where[] = ["a.customer_id","=",$params["customer_id"]];

        $prefix = DB::getConfig('prefix');

        $aidStr = '';
        if(is_array($aidArr) && !empty($aidArr))//如果是数组,且数组不为空
        {
            $aidStr = count($aidArr) == 1 ? $aidArr[0]:implode(',',$aidArr);
        }


        if(!empty($aidStr))
            $list = DB::table($this->agreement." as a")
                ->leftJoin($this->customer." as b","a.customer_id","=","b.customer_id")
                ->leftJoin($this->user." as c","c.uid","=","a.uid")
                ->where($where)
                ->whereIn("a.uid",$userArr)
                ->orWhere(DB::Raw($prefix."a.agree_id in ({$aidStr})"))
                ->orderby('a.'.$field,$sort)
                ->offset($offset)
                ->limit($page_nums)
                ->get(["a.agree_id","a.business_id","a.category_name","a.create_time","a.status","a.price","b.name","b.company_name","c.username"]);
        else
            $list = DB::table($this->agreement." as a")
                ->leftJoin($this->customer." as b","a.customer_id","=","b.customer_id")
                ->leftJoin($this->user." as c","c.uid","=","a.uid")
                ->where($where)
                ->whereIn("a.uid",$userArr)
                ->orderby('a.'.$field,$sort)
                ->offset($offset)
                ->limit($page_nums)
                ->get(["a.agree_id","a.business_id","a.category_name","a.create_time","a.status","a.price","b.name","b.company_name","c.username"]);

        $data["list"] = $list;

        return $data;
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
<?php

/**
 * 商机控制器
 */
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\BaseController;

use App\Repositories\Admin\BusinessRepository;
use App\Repositories\Business\ExportRepository;
use Illuminate\Http\Request;


class BusinessController extends  BaseController
{

    /**
     * 客户列表
     * @param Request $request
     * @param BusinessRepository $businessRepository
     */
    public function lists(Request $request,BusinessRepository $businessRepository)
    {
        $uid            = (int)$request->get("uid");
        $is_admin       = (int)$request->get("is_admin");
        $is_depart_admin= (int)$request->get("is_depart_admin");
        $level          = $request->get("level");

        if(empty($uid))
            $this->failed("您还没有登录");

        $data                   = $request->post();
        $data["keywords"]       = $request->post("keywords");
        $data["status"]         = $request->post("status");
        $data["page"]           = $request->post("page");
        $data["page_nums"]      = $request->post("page_nums");
        $data["field"]          = $request->post("field","answer_update_time");
        $data["sort"]           = $request->post("sort","desc");

        if(!in_array($data["sort"],["desc","asc"]))
            $this->failed("排序参数错误");

        $list = $businessRepository->lists($data,$uid,$is_admin,$is_depart_admin,$level);

        $this->success($list);
    }


    /**
     * 添加与修改
     */
    public function save(Request $request,BusinessRepository $businessRepository)
    {
        $adminUser["uid"]           = $request->get("uid");
        $adminUser["depart_id"]     = $request->get("depart_id");
        $adminUser["username"]      = $request->get("username");

        if(empty($adminUser["uid"]))
            $this->failed("您还未登陆");

        $data             = $request->post();
        $customer_id      = (int)$request->post("id");

        if(!empty($customer_id))
        {
            $businessRepository->edit($data,$customer_id,$adminUser["uid"]);

            //admin_operate_log($adminUser,request()->route()->getActionName(),[],"modify",$adminUser["username"]."修改了商机信息:customer_id".$customer_id);
            $this->success("修改成功");
        }


        $customer_id = $businessRepository->add($data,$adminUser["uid"],$adminUser["depart_id"]);
        if($customer_id>0)
        {
            //admin_operate_log($adminUser,request()->route()->getActionName(),[],"add",$adminUser["username"]."新增了商机信息:customer_id".$customer_id);
            $this->success(["id"  => $customer_id]);
        }

        $this->failed("添加失败");

    }


    /**
     * 分配
     */
    public function allot(Request $request,BusinessRepository $businessRepository)
    {
        $isAdmin        = $request->get("is_admin");
        $isDepartAdmin  = $request->get("is_depart_admin");
        $uid            = (int)$request->get("uid");
        $customer_id    = (int)$request->post("customer_id");
        $level          = $request->get("level");

        if(empty($uid))
            $this->failed("您还没有登录");

        if(empty($isAdmin) && empty($isDepartAdmin))
            $this->failed("您没有操作权限");

        $toUid          = (int)$request->post("to_uid");
        if(empty($toUid))
            $this->failed("分配的人id不能为空");

        $bool = $businessRepository->allot($isAdmin,$isDepartAdmin,$uid,$level,$customer_id,$toUid);

        $bool ? $this->success("分配成功"):$this->failed("分配失败");
    }

    /**
     * 跟进记录
     * @param Request $request
     * @param BusinessRepository $businessRepository
     */
    public function follow_log(Request $request,BusinessRepository $businessRepository)
    {
        $isAdmin        = $request->get("is_admin");
        $isDepartAdmin  = $request->get("is_depart_admin");
        $uid            = (int)$request->get("uid");
        $level          = $request->get("level");

        if(empty($uid))
            $this->failed("您还没有登录");

        $customer_id    = (int)$request->post("customer_id");
        if(empty($customer_id))
            $this->failed("商机id不能为空");

        $list = $businessRepository->follow_log($uid,$customer_id);

        $this->success($list);
    }


    /**
     * 获取商机类型
     * @param BusinessRepository $businessRepository
     */
    public function getType(BusinessRepository $businessRepository)
    {
        $list = $businessRepository->getType();

        $this->success($list);
    }


    /**
     * 领取确认
     * @param Request $request
     * @param BusinessRepository $businessRepository
     */
    public function confirm(Request $request,BusinessRepository $businessRepository)
    {
        $uid            = $request->get("uid");
        $username       = $request->get("username");
        $customer_id    = $request->post("customer_id");

        if(empty($uid))
            $this->failed("您还没有登录");

        if(empty($customer_id))
            $this->failed("客户参数不能为空");

        $bool = $businessRepository->confirm($uid,$username,$customer_id);

        $bool ? $this->success("领取成功") :$this->failed("领取失败");
    }


    /**
     * 继续跟进
     */
    public function continue_follow(Request $request,BusinessRepository $businessRepository)
    {
        $uid            = $request->get("uid");
        $username       = $request->get("username");

        $customer_id    = $request->post("customer_id");
        $status         = $request->post("status");
        $remark         = $request->post("remark");
        $next_follow_time = $request->post("next_follow_time");
        $dealMoney      = (int)$request->post("deal_money");

        if(empty($uid))
            $this->failed("您还没有登录");

        if(empty($customer_id))
            $this->failed("客户参数不能为空");

        if(empty($status))
            $this->failed("跟进状态不能为空");

        if(!in_array($status,["running","not_dealed","dealed","finished","give_up"]))
            $this->failed("跟进状态有误");

        if($status == 'running' && empty($next_follow_time))
        {
            $this->failed("下次跟进时间不能为空");
        }
        if($status == "dealed" && empty($dealMoney))
        {
            $this->failed("成交金额不能为空");
        }

        if(empty($remark))
            $this->failed("跟进说明不能为空");

        $bool = $businessRepository->continue_follow($uid,$customer_id,$username,$status,$remark,$dealMoney,$next_follow_time);

        $bool ? $this->success("跟进成功"):$this->failed("跟进失败");
    }


    /**
     * 导出
     */
    public function exportList(Request $request,BusinessRepository $businessRepository,ExportRepository $exportRepository)
    {
        $token   = $request->get("token");
        if(empty($token))
            $this->failed("您还没有登录");

        $uid    = expain_token($token);

        $data                   = $request->all();
        $data["keywords"]       = $request->get("keywords");
        $data["status"]         = $request->get("status");
        $data["is_company"]     = (int)$request->get("is_company");
        $data["page"]           = $request->get("page");
        $data["page_nums"]      = $request->get("page_nums");

        $list = $businessRepository->exportList($data);
        if($list->isEmpty())
           $this->failed("检索结果为空");


        $exportRepository->lists($list,"customer",$data["is_company"]);
    }
}

<?php

/**
 * 用户控制器
 */
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\BaseController;

use App\Repositories\Admin\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


class UserController extends  BaseController
{
    /**
     * 用户列表
     */
    public function lists(Request $request,UserRepository $userRepository)
    {

        $uid            = (int)$request->get("uid");
        $is_admin       = (int)$request->get("is_admin");
        $is_depart_admin= (int)$request->get("is_depart_admin");
        $level          = $request->get("level");

        $data                   = $request->post();
        $data["keywords"]       = $request->post("keywords");
        $data["page"]           = $request->post("page");
        $data["page_nums"]      = $request->post("page_nums");
        $data["field"]          = $request->post("field","create_time");
        $data["sort"]           = $request->post("sort","asc");

        $list = $userRepository->lists($data,$uid,$is_admin,$is_depart_admin,$level);
        $this->success($list);
    }

    /**
     * 我的可分配人员
     * @param Request $request
     */
    public function myAllotUserList(Request $request,UserRepository $userRepository)
    {
        $uid            = (int)$request->get("uid");
        $level          = $request->get("level");
        $isAdmin        = (int)$request->get("is_admin");
        $isDepartAdmin  = (int)$request->get("is_depart_admin");

        if(empty($uid))
            $this->failed("您还未登陆",4001);

        if(empty($isAdmin) && empty($isDepartAdmin))
            $this->failed("您没有权限");

        $list = $userRepository->myAllotUserList($isAdmin,$isDepartAdmin,$level,$uid);

        $this->success($list);
    }


    /**
     * 添加or编辑普通管理者
     */
    public function save(Request $request,UserRepository $userRepository)
    {
        $adminUser["uid"]           = $request->get("uid");
        $adminUser["username"]      = $request->get("username");
        $isDepartAdmin  = (int)$request->get("is_depart_admin");

        $is_admin      = (int)$request->get("is_admin");

        if(empty($is_admin)  && empty($isDepartAdmin))
            $this->failed("您没有此权限");

        $data = $request->post();
        $uid = (int)$request->post("uid");
        if(!empty($uid))
        {
            $userRepository->edit($data,$uid,$adminUser["uid"]);

            //admin_operate_log($adminUser,request()->route()->getActionName(),[],"modify",$adminUser["username"]."修改了用户信息:uid".$uid);
            $this->success("修改成功");
        }

        $uid = $userRepository->add($data,$adminUser["uid"]);
        if($uid>0)
        {
            //admin_operate_log($adminUser,request()->route()->getActionName(),[],"add",$adminUser["username"]."新增了用户信息:uid".$uid);
            $this->success(["uid"=>$uid]);
        }

       $this->failed("添加失败");

    }


    /**
     * 设置月度目标
     */
    public function setMonthTarget(Request $request,UserRepository $userRepository)
    {
        $uid            = $request->get("uid");
        $is_admin       = (int)$request->get("is_admin");
        if(empty($uid))
            $this->failed("您还没有登录",4001);

        if(empty($is_admin))
            $this->failed("您没有此权限");

        $toUid          = (int)$request->post("to_uid");
        if(empty($toUid))
            $this->failed("设置人不能为空");

        $money          = (int)$request->post("money");
        if(empty($money))
            $this->failed("目标销售额不能为空");

        $bool = $userRepository->setMonthTarget($toUid,$money);

        $bool ? $this->success("设置成功"):$this->failed("设置失败");
    }

    /**
     * 检验用户数据
     */
    protected  function checkData($data)
    {
        !isset($data["mobile"]) && $this->failed("用户账号不能为空");
        !isset($data["username"]) && $this->failed("用户名称不能为空");
        !isset($data["depart_id"]) && $this->failed("部门不能为空");
        !isset($data["role_id"]) && $this->failed("用户角色不能为空");

        empty($data["mobile"]) && $this->failed("用户账号不能为空");
        empty($data["username"]) && $this->failed("用户名称不能为空");
        empty($data["depart_id"]) && $this->failed("部门不能为空");
        empty($data["role_id"]) && $this->failed("用户角色不能为空");

        //手机号格式验证
        if(!is_numeric($data["mobile"]) || strlen($data["mobile"]) != 11)
            $this->failed("手机号格式不正确");

    }
}

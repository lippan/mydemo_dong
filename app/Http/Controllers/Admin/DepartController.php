<?php

/**
 * 部门组织控制器
 */
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\BaseController;

use App\Repositories\Admin\DepartRepository;
use Illuminate\Http\Request;


class DepartController extends  BaseController
{

    /**
     * 部门管理员
     * @param Request $request
     * @param DepartRepository $departRepository
     */
    public function lists(Request $request,DepartRepository $departRepository)
    {
        $data                   = $request->post();
        $data["page"]           = $request->post("page");
        $data["page_nums"]      = $request->post("page_nums");

        $list = $departRepository->lists($data);

        $this->success($list);
    }


    /**
     * 添加or编辑
     * @param Request $request
     * @param DepartRepository $departRepository
     */
    public function save(Request $request,DepartRepository $departRepository)
    {
        $uid           = $request->get("uid");
        $is_admin      = (int)$request->get("is_admin");

        if(empty($is_admin))
            $this->failed("您没有此权限");

        $data   = $request->post();
        $depart_id    = (int)$request->post("depart_id");
        if(!empty($depart_id))
        {
            $departRepository->edit($data,$depart_id,$uid);

            //admin_operate_log($adminUser,request()->route()->getActionName(),[],"modify",$adminUser["username"]."修改了用户信息:uid".$uid);
            $this->success("修改成功");
        }

        $parent_id = (int)$request->post("parent_id");

        $depart_id = $departRepository->add($data,$parent_id,$uid);
        if($depart_id>0)
        {
            //admin_operate_log($adminUser,request()->route()->getActionName(),[],"add",$adminUser["username"]."新增了用户信息:uid".$uid);
            $this->success(["depart_id"=>$depart_id]);
        }

       $this->failed("添加失败");

    }


    /**
     * 删除
     * @param Request $request
     * @param DepartRepository $departRepository
     */
    public function del(Request $request,DepartRepository $departRepository)
    {
        $uid           = $request->get("uid");
        $is_admin      = (int)$request->get("is_admin");
        if(empty($is_admin))
            $this->failed("您没有此权限");

        $depart_id    = (int)$request->post("depart_id");
        if(empty($depart_id))
            $this->failed("部门id不能为空");

        $bool = $departRepository->del($depart_id,$uid);

        $bool ? $this->success("删除成功") :$this->failed("删除失败");
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

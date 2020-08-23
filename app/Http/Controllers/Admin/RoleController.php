<?php
/**
 *  角色控制器
 */
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Repositories\Admin\RoleRepository;
use Illuminate\Http\Request;

class RoleController extends BaseController
{


    /**
     * 获取角色列表(是否支持按部门定义角色)
     * @param Request $request
     *
     */
    public function lists(Request $request,RoleRepository $roleRepository)
    {
        $data                   = $request->post();
        $data["page"]           = $request->post("page");
        $data["page_nums"]      = $request->post("page_nums");

        $list = $roleRepository->lists($data);
        $this->success($list);
    }


    /**
     * 添加与修改
     * @param Request $request
     * @param RoleRepository $roleRepository
     */
    public function save(Request $request,RoleRepository $roleRepository)
    {
        $adminUser["uid"]           = $request->get("uid");
        $adminUser["username"]      = $request->get("username");

        $is_admin                   = (int)$request->get("is_admin");

        if(empty($is_admin))
            $this->failed("您没有此权限");

        $data       = $request->post();
        $role_id    = (int)$request->post("role_id");

        $this->checkData($data);

        if(!empty($role_id))
        {
            $roleRepository->edit($data,$role_id,$adminUser["uid"]);

            //admin_operate_log($adminUser,request()->route()->getActionName(),[],"modify",$adminUser["username"]."修改了角色信息:role_id".$role_id);
            $this->success("修改成功");
        }

        $role_id = $roleRepository->add($data,$adminUser["uid"]);
        if($role_id>0)
        {
            //admin_operate_log($adminUser,request()->route()->getActionName(),[],"add",$adminUser["username"]."新增了角色信息:role_id".$role_id);
            $this->success(["role_id"=>$role_id]);
        }

        $this->failed("添加失败");
    }


    /**
     * 删除
     * @param Request $request
     * @param RoleRepository $roleRepository
     */
    public function del(Request $request,RoleRepository $roleRepository)
    {
        $uid           = $request->get("uid");
        $is_admin      = (int)$request->get("is_admin");
        if(empty($is_admin))
            $this->failed("您没有此权限");

        $role_id    = (int)$request->post("role_id");
        if(empty($role_id))
            $this->failed("角色id不能为空");

        $bool = $roleRepository->del($role_id,$uid);

        $bool ? $this->success("删除成功") :$this->failed("删除失败");
    }


    /**
     * 检验角色数据
     */
    protected  function checkData($data)
    {
        !isset($data["role_name"])  && $this->failed("角色名称不能为空");
        !isset($data["remark"]) && $this->failed("负责功能不能为空");

        empty($data["role_name"])   && $this->failed("角色名称不能为空");
        empty($data["remark"])  && $this->failed("负责功能不能为空");
    }
}

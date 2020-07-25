<?php
/**
 *  角色控制器
 */
namespace App\Http\Controllers\Common;

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
        $list = $roleRepository->lists();
        $this->success($list);
    }


}

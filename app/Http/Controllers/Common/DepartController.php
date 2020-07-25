<?php
/**
 *  部门控制器
 */
namespace App\Http\Controllers\Common;

use App\Http\Controllers\BaseController;
use App\Repositories\Common\DepartRepository;
use Illuminate\Http\Request;

class DepartController extends BaseController
{


    /**
     * 获取部门列表
     * @param Request $request
     *
     */
    public function lists(Request $request,DepartRepository $departRepository)
    {
        $agentId = "50001359";
        $departRepository->syncLevel($agentId);

    }



}

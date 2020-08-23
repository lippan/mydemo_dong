<?php

/**
 * 公海控制器
 */
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\BaseController;

use App\Repositories\Admin\CommonSeaRepository;
use Illuminate\Http\Request;


class CommonSeaController extends  BaseController
{

    /**
     * 客户列表
     * @param Request $request
     * @param CommonSeaRepository $commonSeaRepository
     */
    public function lists(Request $request,CommonSeaRepository $commonSeaRepository)
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
        $data["field"]          = $request->post("field","create_time");
        $data["sort"]           = $request->post("sort","desc");

        if(!in_array($data["sort"],["desc","asc"]))
            $this->failed("排序参数错误");

        $list = $commonSeaRepository->lists($data,$uid,$is_admin,$is_depart_admin,$level);

        $this->success($list);
    }

}

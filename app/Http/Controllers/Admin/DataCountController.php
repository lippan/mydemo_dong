<?php

/**
 * 数据统计控制器
 */
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\BaseController;

use App\Repositories\Admin\DataCountRepository;
use Illuminate\Http\Request;


class DataCountController extends  BaseController
{

    /**
     * 销售看板
     * @param Request $request
     * @param DataCountRepository $dataCountRepository
     */
    public function staff(Request $request,DataCountRepository $dataCountRepository)
    {
        $uid            = (int)$request->get("uid");
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

        $list = $dataCountRepository->staff($data);

        $this->success($list);
    }


    /**
     * 每日看板
     * @param Request $request
     * @param DataCountRepository $dataCountRepository
     */
    public function everyday(Request $request,DataCountRepository $dataCountRepository)
    {
        $uid            = (int)$request->get("uid");
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

        $list = $dataCountRepository->everyday($data);

        $this->success($list);
    }
}

<?php

/**
 * 线索控制器
 */
namespace App\Http\Controllers\App;

use App\Http\Controllers\BaseController;
use App\Repositories\Business\ClueRepository;
use Illuminate\Http\Request;

class ImportController  extends BaseController
{
    public function data(Request $request,ClueRepository $clueRepository)
    {
        $data               = $request->all();
        $data["mobile"]     = $request->post("mobile");
        $data["shop_name"]  = $request->post("shop_name");
        $data["source"]     = $request->post("source");
        $data["position"]   = $request->post("position");

        if(empty($data["mobile"]))
            $this->failed("手机号不能为空");

        if(empty($data["shop_name"]))
            $this->failed("店铺名称不能为空");

        $bool = $clueRepository->add($data);

        $bool ? $this->success("添加成功"):$this->failed("添加失败");
    }
}

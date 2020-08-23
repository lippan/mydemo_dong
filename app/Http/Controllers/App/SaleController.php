<?php

/**
 * 销售控制器
 */
namespace App\Http\Controllers\App;

use App\Http\Controllers\BaseController;
use App\Repositories\App\ClueRepository;
use Illuminate\Http\Request;

class SaleController  extends BaseController
{

    /**
     * 随机取数据
     * (部门id+短信发送次数+地区)
     * @param Request $request
     * @param ClueRepository $clueRepository
     */
    public function getRandomData(Request $request,ClueRepository $clueRepository)
    {
        $departId       = (int)$request->post("depart_id");
        $smsSendNums    = (int)$request->post("sms_send_nums");
        $position       = $request->post("position");
        $wx             = $request->post("wx");
        if(empty($wx))
            $this->failed("微信不能为空");

        $data = $clueRepository->getRandomData($departId,$smsSendNums,$wx,$position);

        $this->success($data);
    }

    /**
     * 发送成功
     * @param Request $request
     * @param ClueRepository $clueRepository
     */
    public function sendSuccess(Request $request,ClueRepository $clueRepository)
    {
        $id = (int)$request->post("id");
        if(empty($id))
            $this->failed("id不能空");

        $bool = $clueRepository->updateClue($id);

        $bool ? $this->success("更新成功"):$this->failed("更新失败");
    }
}

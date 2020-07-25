<?php
/**
 *  地图控制器
 */
namespace App\Http\Controllers\Common;

use App\Http\Controllers\BaseController;
use App\Repositories\Common\MapRepository;
use Illuminate\Http\Request;

class MapController extends BaseController
{
    /**
     * 获取周边
     */
    public function getAround(Request $request,MapRepository $mapRepository)
    {
        $location = $request->post("location");
        if(empty($location))
            $this->failed("坐标位置不能为空");

        $data                   = $request->post();
        $data["offset"]         = $request->post("page_nums",20);
        $data["page"]           = $request->post("page",1);

        $result = $mapRepository->getAround($data,$type="gd");

       $this->success($result);
    }
}

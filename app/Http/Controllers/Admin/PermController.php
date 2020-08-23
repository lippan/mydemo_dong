<?php
/**
 *  权限控制器
 */
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Repositories\Admin\PermRepository;
use Illuminate\Http\Request;

class PermController extends BaseController
{


    /**
     * 获取权限列表
     * @param Request $request
     *
     */
    public function lists(Request $request,PermRepository $permRepository)
    {
        $list = $permRepository->lists();
        $this->success($list);
    }


    /**
     * 同步权限节点
     */
    public function sync(PermRepository $permRepository)
    {
        $app    = app();
        $routes = $app->routes->getRoutes();
        $path   = [];
        foreach ($routes as $k=>$value)
        {
            if(isset($value->action["as"]) && $value->action["prefix"] != "_ignition")
            {
                $path[$k]['uri']    = $value->uri;
                $path[$k]['path']   = $value->methods[0];
                $path[$k]['name']   = $value->action["as"];
            }
        }

        if(!empty($path))
            $permRepository->sync($path);
        else
            $this->failed("请求失败:权限节点为空");

        $this->success("权限节点同步成功");
    }

}

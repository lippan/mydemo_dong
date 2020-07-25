<?php
/**
 *  阿里云Oss控制器
 */
namespace App\Http\Controllers\Common;

use App\Http\Controllers\BaseController;
use App\Repositories\Common\OssRepository;
use Illuminate\Http\Request;

class OssController extends BaseController
{


    /**
     * 获取阿里云 OSS Policy
     * @param Request $request
     * @param OssRepository $ossRepository
     */
    public function getOssPolicy(Request $request,OssRepository $ossRepository)
    {
        $policy = $ossRepository->policy();
        $this->success($policy);
    }


}

<?php
/**
 *  api 外部接口 控制器
 */
namespace App\Http\Controllers\Common;

use App\Http\Controllers\BaseController;
use App\Libraries\pay\wxpay\PayApi;
use App\Libraries\Wx\WxApi;
use App\Repositories\Common\ApiRepository;
use App\Repositories\Common\TaskRepository;
use Curl\Curl;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class TaskController extends BaseController
{



    /**
     * 每日早上八点推送模板消息
     * @param Request $request
     */
    public function day_push(Request $request,TaskRepository $taskRepository)
    {
        //$taskRepository->day_push();
    }


}

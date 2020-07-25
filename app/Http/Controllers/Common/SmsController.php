<?php
/**
 *  短信控制器
 */
namespace App\Http\Controllers\Common;

use App\Http\Controllers\BaseController;
use App\Repositories\Common\SmsRepository;
use Illuminate\Http\Request;

class SmsController extends BaseController
{


    /**
     * 获取短信
     * @param Request $request
     * @param
     */
    public function getSmsCode(Request $request,SmsRepository $smsRepository)
    {
        $mobile = $request->post("mobile");
        $type   = $request->post("type");

        if(empty($mobile))
            $this->failed("手机号不能为空");

        if(empty($type))
            $this->failed("短信类型不能为空");

        if(!in_array($type,["login","bind_mobile"]))
            $this->failed("短信类型有误");

        if(!preg_match('/1[23456789]\d{9}$/',$mobile))
            $this->failed("手机号码格式不正确");

        $smsRepository->getSms($mobile,$type);
    }


    /**
     * 验证短信
     * @param Request $request
     * @param SmsRepository $smsRepository
     */
    public function checkSms(Request $request,SmsRepository $smsRepository)
    {
        $mobile = $request->post("mobile");
        $code   = $request->post("code");
        $type   = $request->post("type");

        if(empty($mobile))
            $this->failed("手机号不能为空");

        if(empty($code))
            $this->failed("验证码不能为空");

        if(empty($type))
            $this->failed("短信类型不能为空");

        if(!in_array($type,["login","bind_mobile"]))
            $this->failed("短信类型有误");

        $result = $smsRepository->checkSms($mobile,$code,$type);

        $result ? $this->success("短信验证成功") :$this->failed("短信验证失败");
    }

}

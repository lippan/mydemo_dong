<?php
/**
 *  登陆控制器
 */
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\BaseController;
use App\Repositories\Business\LoginRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;


class LoginController extends BaseController
{


    /**
     * 登陆接口
     * @param Request $request
     */
    public function index(Request $request,LoginRepository $loginRepository)
    {
        //默认为账号密码登陆
        $data       = $request->post();
        $loginType  = $request->post("type",1);

        //检验数据格式
        $this->checkLoginData($data,$loginType);

        $user = $loginRepository->index($data,$loginType);
        if(empty($user))
            $this->failed("账号或密码错误");

        //只有超级管理员能登陆后台
        //if($user->is_admin != 1)
            //$this->failed("您没有此权限");

        //获取用户的权限节点,存入redis 缓存中 TODO


        $token_data = [
            "pc_admin"  => 1,
            'uid'       => $user->uid,
            'level'     => $user->level,
            'status'    => $user->status,
            'username'  => $user->username,
            'is_admin'  => $user->is_admin,
            'is_depart_admin' =>$user->is_depart_admin,
            'role_id'   => $user->role_id,
            'expire'    => time()+3600*24
        ];
        $token = Crypt::encrypt($token_data);

        //记录操作
        admin_operate_log((array)$user,request()->route()->getActionName(),[],"login",$user->username."登陆了后台系统");

        $this->success(["token"=>$token,"is_admin"=>$user->is_admin,"is_depart_admin"=>$user->is_depart_admin]);
    }


    //检查登陆数据
    protected function checkLoginData($data,$loginType)
    {
        if($loginType == 1)
        {
            if(!isset($data["account"])|| !isset($data["password"]))
                $this->failed("账号或密码不能为空");

            if(empty($data["account"])||empty($data["password"]))
                $this->failed("账号或密码不能为空");

            if(!is_numeric($data["account"]) || strlen(trim($data["account"])) != 11)
                $this->failed("账号格式不正确");
        }
        elseif($loginType == 2)
        {
            if(!isset($data["account"])|| !isset($data["code"]))
                $this->failed("账号或验证码不能为空");

            if(empty($data["account"])||empty($data["code"]))
                $this->failed("账号或验证码不能为空");

            if(!is_numeric($data["account"]) || strlen(trim($data["mobile"])) != 11)
                $this->failed("账号格式不正确");
        }
        elseif($loginType == 3)
        {
            if(!isset($data["account"])|| !isset($data["password"]))
                $this->failed("账号或密码不能为空");

            if(empty($data["account"])||empty($data["password"]))
                $this->failed("账号或密码不能为空");
        }
        else
            $this->failed("非法请求");
    }


}

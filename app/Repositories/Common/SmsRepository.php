<?php

/**
 *  短信资源
 */
namespace App\Repositories\Common;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Libraries\aliyun\Sms;

class SmsRepository
{
    use \App\Repositories\BaseRepository;
    use \App\Helpers\ApiResponse;

    private $sms_log_table ;

    //初始化需要用到的表名称
    public function __construct()
    {
        $this->sms_log_table       = config('db_table.sms_log_table');
    }

    /**
     *
     * @param Request $request
     */
    public function getSms($mobile,$type="login")
    {

        $hasSend = $this->checkHasSend($mobile,$type);
        if($hasSend)
            $this->failed('两分钟内不能重复发送短信');

        $code = rand(1000, 9999);   // 验证码
        //发送短信
        try{

            //$this->send_msg($mobile,"您的验证码是 ".$code);
            $this->sendMsgAli($mobile,$code);
        }
        catch (Exception $e){
            $this->failed($e->getMessage());
        }

        //设置时区
        date_default_timezone_set("PRC");
        
        $data = [
            'mobile'    => $mobile,
            'code'      => $code,
            'ip'        => $_SERVER["REMOTE_ADDR"],
            'type'      => $type,
            'create_time'=> date('Y-m-d H:i:s'),
        ];
        $bool = DB::table($this->sms_log_table)->insert($data);
        if(empty($bool))
            $this->failed("短信记录写入数据库失败");

        $this->success("短信发送成功");
    }


    /**
     * 验证码检验
     * @param Request $request
     */
    public function checkSms($mobile,$code,$type)
    {
        $smstime = config('app.sms_time_validity');
        $valid_time = date('Y-m-d H:i:s', (time() - $smstime));

        $sendlog = DB::table($this->sms_log_table)->where('mobile',$mobile)
            ->where('type',$type)
            ->where('code',$code)
            ->where('create_time','>=',$valid_time)
            ->first();

        return !empty($sendlog) ? true:false;
    }


    /**
     * 修改短信验证码状态
     * @param $mobile
     * @param $code
     * @param $type
     * @param string $setType
     * @return bool
     */
    public function setUsed($mobile,$code,$type,$setType="used_login")
    {
        $bool = DB::table($this->sms_log_table)->where('mobile',$mobile)
            ->where('type',$type)
            ->where('code',$code)
            ->update(["type"=>$setType]);

        return !empty($bool) ? true:false;
    }


    /**
     * 发送短信(集团内)
     * @param $mobile
     * @param $content
     * @return bool|string
     */
    protected function send_msg($mobile,$content)
    {
        $url = 'http://sms.sany.com.cn:8318/sms/services/SmsNewOperatoraddsubCode?wsdl';
        $account = 'o2osmsxd';
        $password = md5('xdsmszmj9');
        $firstDeptId = '50524052';
        $secondDeptId = '50603145';
        $MtNewMessage['smsId '] = '';
        $MtNewMessage['phoneNumber'] = $mobile;
        $MtNewMessage['content'] = $content;
        $MtNewMessage['scheduleTime'] = null ;
        $MtNewMessage['Wappushurl '] = '';
        $subCode = '';
        libxml_disable_entity_loader(false);
        try{
            $soap =  new \SoapClient($url);
            $res = $soap->sendSms($account, $password, $firstDeptId, $secondDeptId, null, $MtNewMessage, $subCode);
            if (!isset($res['sendResMsg'])) return '发送失败';
            if (!empty($res['errMsg'])) return $res['errMsg'];
            $code = str_replace($mobile, '', $res['sendResMsg']);
            if (strlen($code) != 94) return "错误码：{$code}";
            return true;
        }catch (\SoapFault $e){
            return $e->getMessage();
        }
    }


    /**
     * 阿里云发送短信
     */
    protected function sendMsgAli($mobile,$code)
    {
        try {
            Sms::getInstance()->send_verify_code($mobile, $code);
        }
        catch (Exception $e){
            $this->failed("短信发送失败:".$e->getMessage());
        }
    }


    /**
     * 检查该类型短信是否发送
     * @param $mobile
     * @return bool
     */
    protected function checkHasSend($mobile,$type)
    {
        //检测是否重复发送短信
        $sms_time   = config("app.sms_time_validity");
        $valid_time = date('Y-m-d H:i:s', (time() - $sms_time));

        $sendlog    = DB::table($this->sms_log_table)->where('mobile',$mobile)
            ->where('type',$type)
            ->where('create_time','>=',$valid_time)
            ->first();

        return !empty($sendlog) ? true:false;
    }
}
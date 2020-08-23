<?php

/**
 * 线索资源
 */
namespace App\Repositories\Business;


use Illuminate\Support\Facades\DB;

class ClueRepository
{
    use \App\Helpers\ApiResponse;

    private $depart         = "depart";
    private $business       = "business";

    /**
     * 添加线索
     * @param $params
     * @return bool
     */
    public function add($params)
    {
        $data = [
            "mobile"        => $params["mobile"],
            "shop_name"     => $params["shop_name"],
            "position"      => $params["position"],
            "source"        => $params["source"],
            "create_time"   => date("Y-m-d H:i:s")
        ];

        isset($params["order"]) && $data["order"] = $params["order"];
        isset($params["range"]) && $data["range"] = $params["range"];
        isset($params["people_hot"]) && $data["people_hot"] = $params["people_hot"];
        isset($params["income"]) && $data["income"] = $params["income"];
        isset($params["position"]) && $data["position"] = $params["position"];
        isset($params["url"]) && $data["url"] = $params["url"];
        isset($params["sms_send_nums"]) && $data["sms_send_nums"] = $params["sms_send_nums"];
        isset($params["sms_last_send_time"]) && $data["sms_last_send_time"] = $params["sms_last_send_time"];
        isset($params["sms_feedback"]) && $data["sms_feedback"] = $params["sms_feedback"];
        isset($params["qq_number"]) && $data["qq_number"] = $params["qq_number"];
        isset($params["wx_number"]) && $data["wx_number"] = $params["wx_number"];
        isset($params["telphone_sale_nums"]) && $data["telphone_sale_nums"] = $params["telphone_sale_nums"];
        isset($params["telphone_last_call_time"]) && $data["telphone_last_call_time"] = $params["telphone_last_call_time"];
        isset($params["telphone_record"]) && $data["telphone_record"] = $params["telphone_record"];
        isset($params["status"]) && $data["status"] = $params["status"];
        isset($params["source"]) && $data["source"] = $params["source"];
        isset($params["shop_area"]) && $data["shop_area"] = $params["shop_area"];
        isset($params["longitude"]) && $data["longitude"] = $params["longitude"];
        isset($params["latitude"]) && $data["latitude"] = $params["latitude"];
        isset($params["remark"]) && $data["remark"] = $params["remark"];

        $bool = DB::table("live_data")->insert($data);

        return $bool;
    }

    /**
     * 获取随机数据
     */
    public function getRandomData($departId,$smsSendNums,$position)
    {
        $where = [
            "depart_id"         => $departId,
            "sms_send_nums"     => $smsSendNums
        ];
        $row = DB::table($this->business)
            ->where($where)
            ->when(!empty($position),function($query)use($position){
                $query->where('position',"like",$position."%");
            })
            ->first();

        return $row;
    }

    /**
     * 更新线索
     */
    public function updateClue($id)
    {
        $row = DB::table($this->business)->where("id",$id)->first();
        if(empty($row))
            $this->failed("非法请求");

        $data = [
            "sms_send_nums"         => $row->sms_send_nums + 1,
            "sms_last_send_time"    => date("Y-m-d H:i:s")
        ];

        $bool = DB::table($this->business)->where("id",$id)->update($data);

        return $bool;
    }
}
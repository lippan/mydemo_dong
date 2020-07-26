<?php

/**
 * 后台基础控制器
 */
namespace App\Http\Controllers\App;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IndexController  extends BaseController
{

    public function notice(Request $request)
    {
        $data   = $request->all();

        $newData = [
            "name"      =>  $data["name"],
            "title"     =>  $data["title"],
            "order_num" =>  $data["order_num"],
            "rang"      =>  $data["rang"],
            "hot"       =>  $data["hot"],
            "money"     =>  $data["money"],
            "mobile"    =>  $data["mobile"],
            "days"      =>  $data["days"],
            "end_time"  =>  date("Y-m-d H:i:s",strtotime("+".$data["days"]." day")),
        ];

        //如果手机号存在，则替换更新内容
        $row = DB::table("business")->where("mobile",$data["mobile"])->first();
        if(!empty($row))
        {
            DB::table("business")->where("mobile",$data["mobile"])->update($newData);

            $this->success("http://tbw315.xyz/view/Index.html?id=".$data["mobile"]);
        }

        $newData["create_time"] = date("Y-m-d H:i:s");

        $id = DB::table("business")->insertGetId($newData);
        if($id)
        {
            $this->success("http://tbw315.xyz/view/Index.html?id=".$data["mobile"]);
        }
        else
            $this->failed("请求失败");
    }

    public function init(Request $request)
    {
        $id   = $request->post("id");
        if(empty($id))
            $this->failed("参数不能为空");

        $row = DB::table("business")->where("mobile",$id)->first();
        if($row)
        {
            //判断是否过期
            if(time()> strtotime($row->end_time))
                $this->failed("您访问的页面过期了");

            $this->success($row);
        }
        else
            $this->failed("请求失败");
    }
}

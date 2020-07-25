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
            "create_time" => date("Y-m-d H:i:s")
        ];

        $id = DB::table("business")->insertGetId($newData);
        if($id)
        {
            $this->success("http://tbw315.xyz/view/Index.html?id=".$id);
        }
        else
            $this->failed("请求失败");
    }
}

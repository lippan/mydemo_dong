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

    public function lists(Request $request)
    {
        $data["start_time"]     = $request->post("start_time");
        $data["end_time"]       = $request->post("end_time");
        $page                   = $request->post("page",1);
        $page_nums              = $request->post("page_nums",20);

        $where =[];
        $where1 = null;
        if(!empty($data["start_time"]))
        {
            $where[]    = ["create_time",">",$data["start_time"]];
            $where1[]   = ["answer_update_time",">",$data["start_time"]];
        }
        if(!empty($data["end_time"]))
        {
            $where[]    = ["create_time","<",$data["end_time"]];
            $where1[]   = ["answer_update_time","<",$data["end_time"]];
        }


        $offset     = ($page-1) * $page_nums;

        $count = DB::table("business")
            ->where($where)
            ->when(isset($where1),function ($query) use ($where1){
                $query->orWhere($where1);
            })
            ->count();

        if(empty($count))
            return ["count"=>0,"list"=>""];

        $list = DB::table("business")
            ->where($where)
            ->when(isset($where1),function ($query) use ($where1){
                $query->orWhere($where1);
            })
            ->orderby('a.create_time',"desc")
            ->offset($offset)
            ->limit($page_nums)
            ->count();

        $data["count"] = $count;
        $data["list"]  = $list;

        $this->success($data);
    }


    public function getDateNum(Request $request)
    {

        $date     = $request->post("date");

        $where =[];
        if(!empty($date))
        {
            $where[]   = ["answer_update_time",">",$date];
            $where[]   = ["answer_update_time","<",$date." 23:59:59"];
        }
        else
            $this->failed("日期不能为空");

        $count = DB::table("business")
            ->where($where)
            ->whereNotNull("answer")
            ->count();

        $this->success(["count"=>$count]);
    }

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


    public function save(Request $request)
    {
        $id   = $request->post("id");
        if(empty($id))
            $this->failed("参数不能为空");

        $answer = $request->post("answer");
        if(empty($answer))
            $this->failed("按钮值为空");

        $row = DB::table("business")->where("mobile",$id)->first();
        if($row)
        {
            //判断是否过期
            if(time()> strtotime($row->end_time))
                $this->failed("您访问的页面过期了");

            if(strpos($row->answer,$answer) !== false)//出现重复答案
            {
                $data = [
                    "answer"                =>$row->answer,
                    "answer_update_time"    => date("Y-m-d H:i:s")
                ];
            }
            else
                $data = [
                    "answer"                =>$row->answer."-".$answer,
                    "answer_update_time"    => date("Y-m-d H:i:s")
            ];


            DB::table("business")->where("mobile",$id)->update($data);

            $this->success($row);
        }
        else
            $this->failed("请求失败");
    }

    public function mobile(Request $request)
    {
        $id   = $request->post("id");
        if(empty($id))
            $this->failed("参数不能为空");

        $row = DB::table("business")->where("mobile",$id)->first();
        if(empty($row))
            $this->failed("记录不存在");

        $this->success($row);
    }
}

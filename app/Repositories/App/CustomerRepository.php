<?php

/**
 *  客户资源
 */
namespace App\Repositories\App;

use Illuminate\Support\Facades\DB;

class CustomerRepository
{
    use \App\Helpers\ApiResponse;

    //客户表
    private $customer;
    private $user;
    private $customer_normal_info;
    private $customer_company_info;
    private $customer_charge_log;
    private $main_business;
    private $customer_type;

    private $visit;
    private $clue;

    //初始化需要用到的表名称
    public function __construct()
    {
        $this->user                     = config('db_table.user_table');
        $this->customer                 = config('db_table.customer_table');
        $this->customer_normal_info     = config('db_table.customer_normal_info');
        $this->customer_company_info    = config('db_table.customer_company_info');
        $this->customer_charge_log      = config('db_table.customer_charge_log');
        $this->customer_type            = config('db_table.customer_type');

        $this->visit                    = config('db_table.visit_table');
        $this->clue                     = config('db_table.clue_table');
        $this->main_business            = config('db_table.main_business');
    }


    /**
     * 陌客列表
     */
    public function lists($params,$uid,$crm_user_id,$is_admin,$isDepartAdmin=null,$level=null)
    {
        $page       = !empty($params['page'])       ? (int)$params['page']:1;
        $page_nums  = !empty($params['page_nums'])  ? (int)$params['page_nums']:10;
        $offset     = ($page-1) * $page_nums;

        if($is_admin)
        {
            $list = $this->adminList($params,$offset,$page_nums);
            return $list;
        }

        if(empty($crm_user_id))
            $list = $this->normalList($uid,$isDepartAdmin,$level,$params,$offset,$page_nums);
        else
            $list = $this->crmList($uid,$crm_user_id,$params,$offset,$page_nums);

        return $list;
    }


    /**
     * 客户详情
     */
    public function info($customerId)
    {
        $row = DB::table($this->customer." as a")
            ->leftJoin($this->user." as b","a.charge_uid","=","b.uid")
            ->where("a.customer_id", $customerId)
            ->first(["a.*","b.username as charge_name"]);

        if (empty($row))
            $this->failed("访问出错");

        //如果是公司客户
        if ($row->is_company)
            $info = DB::table($this->customer_company_info)->where("customer_id", $customerId)->first();
        else
            $info = DB::table($this->customer_normal_info)->where("customer_id", $customerId)->first();

        //获取该用户时间轴 1.创建   3.线索
        $data[] = ["tag"=>"add","business_id"=>$customerId,"create_time" => $row->create_time, "timestamp" => strtotime($row->create_time), "name" => "创建客户","region" => $row->province_name.$row->city_name,"remark"=>$row->require_type];

//        if(empty($info))
//        {
//            $edata = [
//                "name"          => $row->name,
//                "mobile"        => $row->mobile,
//                "create_time"   => $row->create_time,
//                "charge_name"   => $row->charge_name,
//                "time_axis"     => $data
//            ];
//            return $edata;
//        }

        //编辑客户
        if(!empty($info))
            $data[] = ["tag"=>"edit","business_id"=>$customerId,"create_time" => $info->create_time, "timestamp" => strtotime($info->create_time), "name" => "编辑客户"];


        //拜访
        $list = DB::table($this->visit)->where("customer_id", $customerId)->get();
        if (!$list->isEmpty()) {
            foreach ($list as $val) {
                $data[] = ["tag"=>"visit","business_id"=>$val->visit_id,"create_time" => $val->create_time, "timestamp" => strtotime($val->create_time), "name" => "拜访客户", "remark" => $val->contact_remark];
            }
        }

        //线索
        $clist = DB::table($this->clue)->where("customer_id", $customerId)->get();

        if (!$clist->isEmpty()) {
            foreach ($clist as $val) {
                $data[] = ["tag"=>"clue","business_id"=>$val->clue_id,"create_time" => $val->create_time, "timestamp" => strtotime($val->create_time), "name" => "创建线索","remark"=>$val->request_remark];
            }
        }

        //商机

        foreach ($data as $key => $val) {
            $distance[$key] = $val['timestamp'];
        }
        array_multisort($distance, SORT_DESC, $data);

        if(!empty($info))
        {
            $info->time_axis = $data;
            $info->type = $row->is_company;
            $info->charge_name = $row->charge_name;

            if(isset($info->main_business) && !empty($info->main_business))
            {
                $mrow =  DB::table($this->main_business)->where("id",$info->main_business)->first();
                if(isset($mrow->cat_id))
                {
                    $crow = DB::table($this->main_business)->where("code",$mrow->cat_id)->first();
                    $info->main_cat_id = $crow->id;
                }
            }
            if(!empty($info->customer_type))
                $trow = DB::table($this->customer_type)->where("id",$info->customer_type)->first();

            $info->customer_type_title = isset($trow->title)? $trow->title:"";
            $info->main_business_title = isset($mrow->cat_name)? $mrow->cat_name:"";
        }
        else
        {
            $info = $row;
            $info->time_axis   = $data;
            $info->charge_name = $row->charge_name;
        }


        return $info;
    }


    /**
     * 快速添加
     */
    public function quick_add($params)
    {
        $time = date("Y-m-d H:i:s");
        $data = [
            "name"          => $params["name"],
            "mobile"        => $params["mobile"],
            "uid"           => $params["uid"],
            "charge_uid"    => $params["uid"],
            "create_time"   => $time,
            "last_follow_time" => $time
        ];

        !empty($params["level"]) && $data["level"] = $params["level"];

        $data["province_name"] = config("app.belong_province_name");
        isset($params["city_name"])   && $data["city_name"] = $params["city_name"];

        isset($params["require_type"]) && $data["require_type"] = $params["require_type"];

        //检查该用户是否已经添加过
        $row = DB::table($this->customer)->where("mobile",$params["mobile"])->first();
        if(!empty($row))
            $this->failed("该用户已存在！请勿重复添加！");

        $customerId = DB::table($this->customer)->insertGetId($data);

        return $customerId;
    }


    /**
     * 编辑
     */
    public function edit($params,$customerId,$customerType)
    {
        //获取当前客户快速创建的信息
        $cRow = DB::table($this->customer)->where("uid",$params["uid"])->where("customer_id",$customerId)->first();
        if(empty($cRow))
            $this->failed("您的身份有误");

        if(empty($customerType))
            $fillNum = $this->normalEdit($params,$customerId);
        else
            $fillNum = $this->companyEdit($params,$customerId);

        //如果用户类型是个人用户的话
        if(empty($customerType))
            $total_num = 14;
        else
            $total_num = 21;


        //此处更新快速创建的具体信息
        $data["fill_percent"] = round($fillNum/$total_num*100);
        $data["is_company"] = $customerType;

        $data["last_follow_time"] = date("Y-m-d H:i:s");
        isset($params["name"])      && $data["name"] = $params["name"];
        isset($params["mobile"])    && $data["mobile"] = $params["mobile"];
        isset($params["company_name"]) && $data["company_name"] = $params["company_name"];

        $bool = DB::table($this->customer)->where("customer_id",$customerId)->update($data);

        return $bool;
    }


    /**
     * 修改负责人(TODO 谁能修改负责人？？？)
     * @param $params
     * @param $customerId
     * @param $uid
     * @return boolean
     */
    public function modifyCharge($params,$customerId,$uid)
    {
        //首先判断此客户是否为本人的
        $row = DB::table($this->customer)->where("customer_id",$customerId)->where("uid",$params["uid"])->first();
        if(empty($row))
            $this->failed("访问出错");

        //如果此人是当前负责人，则不需要更换
        if($row->charge_uid == $uid)
            return true;

        $data = [
            "customer_id"   => $customerId,
            "charge_uid"    => $uid,
            "reason"        => $params["reason"],
            "create_time"   => date("Y-m-d H:i:s")
        ];

        $bool = DB::table($this->customer_charge_log)->insert($data);
        if($bool)
            DB::table($this->customer)->where("customer_id",$customerId)->update(["charge_uid" => $uid]);

        return $bool;
    }


    /**
     * 修改客户状态(TODO 谁能修改客户状态？？？)
     * @param $params
     * @param $customerId
     * @return boolean
     */
    public function modifyStatus($params,$customerId)
    {


        $data = [
            "status"        => $params["status"],
            "failed_msg"    => $params["failed_msg"]
        ];
        $bool = DB::table($this->customer)->where("customer_id",$customerId)->update($data);

        return $bool;
    }


    //超级管理员列表
    protected function adminList($params,$offset,$page_nums)
    {
        $where = [];
        $field  = !empty($params["field"]) ? $params["field"]:"create_time";
        $sort   = !empty($params["sort"]) ? $params["sort"]:"desc";
        isset($params["charge_uid"]) && $where[] = ["a.charge_uid","=",$params["charge_uid"]];

        if(isset($params["keywords"]) && !empty($params["keywords"]))
        {

            $list = DB::table($this->customer." as a")
                ->where($where)
                ->where(function($query)use($params){
                    $query->where('a.name',"like","%".$params["keywords"]."%")
                        ->orWhere("a.company_name","like","%".$params["keywords"]."%")
                        ->orWhere("a.mobile","like","%".$params["keywords"]."%");
                })
                ->orderby('a.'.$field,$sort)
                ->offset($offset)
                ->limit($page_nums)
                ->get();

            $data["list"] = $list;

            return $data;
        }


        //获取数量
        //$count = DB::table($this->customer." as a")->where($where)->count();
        //if(empty($count))
            //return ["list"=>[],"count"=>0];

        //$data["count"] = $count;

        $list = DB::table($this->customer." as a")
            ->where($where)
            ->orderby('a.'.$field,$sort)
            ->offset($offset)
            ->limit($page_nums)
            ->get();

        $data["list"] = $list;

        return $data;
    }


    //不具备crm属性用户列表
    protected function normalList($uid,$isDepartAdmin,$level,$params,$offset,$page_nums)
    {
        $field  = !empty($params["field"]) ? $params["field"]:"create_time";
        $sort   = !empty($params["sort"]) ? $params["sort"]:"desc";

        isset($params["charge_uid"]) && $where[] = ["a.charge_uid","=",$params["charge_uid"]];

        if($isDepartAdmin)
            $where[] = ['a.level', 'like', $level."%"];
        else
            $where[] = ['a.uid', '=', $uid];

        if(isset($params["keywords"]) && !empty($params["keywords"]))
        {
            $list = DB::table($this->customer." as a")
                ->where($where)
                ->where(function($query)use($params){
                    $query->where('a.name',"like","%".$params["keywords"]."%")
                        ->orWhere("a.company_name","like","%".$params["keywords"]."%")
                        ->orWhere("a.mobile","like","%".$params["keywords"]."%");
                })
                ->orderby('a.'.$field,$sort)
                ->offset($offset)
                ->limit($page_nums)
                ->get();

            $data["list"] = $list;

            return $data;
        }


        $list = DB::table($this->customer." as a")
            ->where($where)
            ->orderby('a.'.$field,$sort)
            ->offset($offset)
            ->limit($page_nums)
            ->get();

        $data["list"] = $list;

        return $data;
    }


    //crm属性用户列表
    protected function crmList($uid,$crm_user_id,$params,$offset,$page_nums)
    {
        $field  = !empty($params["field"]) ? $params["field"]:"create_time";
        $sort   = !empty($params["sort"]) ? $params["sort"]:"desc";

        $userArr = getUserAuth($crm_user_id,$uid);

        isset($params["charge_uid"]) && $where[] = ["a.charge_uid","=",$params["charge_uid"]];

        if(isset($params["keywords"]) && !empty($params["keywords"]))
        {
            $list = DB::table($this->customer." as a")
                ->whereIn("a.uid",$userArr)
                ->where(function($query)use($params){
                    $query->where('a.name',"like","%".$params["keywords"]."%")
                        ->orWhere("a.company_name","like","%".$params["keywords"]."%")
                        ->orWhere("a.mobile","like","%".$params["keywords"]."%");
                })
                ->orderby('a.'.$field,$sort)
                ->offset($offset)
                ->limit($page_nums)
                ->get();

            $data["list"] = $list;

            return $data;
        }


        $list = DB::table($this->customer." as a")
            ->whereIn("a.uid",$userArr)
            ->orderby('a.'.$field,$sort)
            ->offset($offset)
            ->limit($page_nums)
            ->get();

        $data["list"] = $list;

        return $data;
    }


    //普通客户编辑
    protected function normalEdit($params,$customerId)
    {
        $time = date("Y-m-d H:i:s");
        //查询该客户资料是否存在，存在则更新
        $row = DB::table($this->customer_normal_info)->where("customer_id",$customerId)->first();

        if(empty($row))//如果个人客户里面没有此客户，则查询一个公司客户中是否存在
        {
            $crow = DB::table($this->customer_company_info)->where("customer_id",$customerId)->first();
            if(!empty($crow))
                $this->failed("该用户已在公司用户中存在，请勿重复创建");
        }


        $data       = [];
        $fill_num   = 0;
        if(isset($params["name"]))
        {
            $data["name"] = $params["name"];$fill_num++;
        }
        if(isset($params["mobile"]) )
        {
            $data["mobile"] = $params["mobile"] ; $fill_num++;
        }
        if(isset($params["sex"]) )
        {
            $data["sex"] = $params["sex"] ; $fill_num++;
        }
        if(isset($params["customer_level"]) )
        {
            $data["customer_level"] = $params["customer_level"] ; $fill_num++;
        }
        if(isset($params["card_no"]) )
        {
            $data["card_no"] = $params["card_no"] ; $fill_num++;
        }
        if(isset($params["birth"]) )
        {
            $data["birth"] = $params["birth"] ; $fill_num++;
        }
        if(isset($params["card_province_id"]) )
        {
            $data["card_province_id"] = $params["card_province_id"] ; $fill_num++;
        }
        if(isset($params["card_city_id"]) )
        {
            $data["card_city_id"] = $params["card_city_id"] ; $fill_num++;
        }
        if(isset($params["card_address"]) )
        {
            $data["card_address"] = $params["card_address"] ; $fill_num++;
        }
        if(isset($params["live_province_id"]) )
        {
            $data["live_province_id"] = $params["live_province_id"] ; $fill_num++;
        }
        if(isset($params["live_city_id"]) )
        {
            $data["live_city_id"] = $params["live_city_id"] ; $fill_num++;
        }
        if(isset($params["live_address"]) )
        {
            $data["live_address"] = $params["live_address"] ; $fill_num++;
        }
        if(isset($params["is_marry"]) )
        {
            $data["is_marry"] = $params["is_marry"] ; $fill_num++;
        }
        if(isset($params["nation"]) )
        {
            $data["nation"] = $params["nation"] ; $fill_num++;
        }


        if(empty($row))//如果是新增
        {
            $data["customer_id"] = $customerId;
            $data["create_time"] = date("Y-m-d H:i:s");
            $bool = DB::table($this->customer_normal_info)->insert($data);

            return $fill_num;
        }

        $data["update_time"] = $time;
        $bool = DB::table($this->customer_normal_info)->where("id",$row->id)->update($data);

        return $this->countFillNum($row);
    }

    //公司客户编辑
    protected function companyEdit($params,$customerId)
    {
        $time = date("Y-m-d H:i:s");
        //查询该客户资料是否存在，存在则更新
        $row = DB::table($this->customer_company_info)->where("customer_id",$customerId)->first();

        if(empty($row))//如果个人客户里面没有此客户，则查询一个公司客户中是否存在
        {
            $crow = DB::table($this->customer_normal_info)->where("customer_id",$customerId)->first();
            if(!empty($crow))
                $this->failed("该用户已在个人用户中存在，请勿重复创建");
        }

        $data       = [];
        $fill_num   = 0;

        if(isset($params["name"]) )
        {
            $data["name"] = $params["name"] ; $fill_num++;
        }
        if(isset($params["mobile"]) )
        {
            $data["mobile"] = $params["mobile"] ; $fill_num++;
        }
        if(isset($params["sex"]) )
        {
            $data["sex"] = $params["sex"] ; $fill_num++;
        }
        if(isset($params["customer_level"]) )
        {
            $data["customer_level"] = $params["customer_level"] ; $fill_num++;
        }
        if(isset($params["company_name"]) )
        {
            $data["company_name"] = $params["company_name"] ; $fill_num++;
        }
        if(isset($params["regist_number"]) )
        {
            $data["regist_number"] = $params["regist_number"] ; $fill_num++;
        }
        if(isset($params["law_person"]) )
        {
            $data["law_person"] = $params["law_person"] ; $fill_num++;
        }
        if(isset($params["found_time"]) )
        {
            $data["found_time"] = $params["found_time"] ; $fill_num++;
        }
        if(isset($params["is_general_taxpayer"]) )
        {
            $data["is_general_taxpayer"] = $params["is_general_taxpayer"] ; $fill_num++;
        }
        if(isset($params["regist_money"]) )
        {
            $data["regist_money"] = $params["regist_money"] ; $fill_num++;
        }
        if(isset($params["card_province_id"]) )
        {
            $data["card_province_id"] = $params["card_province_id"] ; $fill_num++;
        }
        if(isset($params["card_city_id"]) )
        {
            $data["card_city_id"] = $params["card_city_id"] ; $fill_num++;
        }
        if(isset($params["card_address"]) )
        {
            $data["card_address"] = $params["card_address"] ; $fill_num++;
        }
        if(isset($params["live_address"]) )
        {
            $data["live_address"] = $params["live_address"] ; $fill_num++;
        }
        if(isset($params["live_province_id"]) )
        {
            $data["live_province_id"] = $params["live_province_id"] ; $fill_num++;
        }
        if(isset($params["live_city_id"]) )
        {
            $data["live_city_id"] = $params["live_city_id"] ; $fill_num++;
        }
        if(isset($params["customer_type"]) )
        {
            $data["customer_type"] = $params["customer_type"] ; $fill_num++;
        }
        if(isset($params["is_up_market"]) )
        {
            $data["is_up_market"] = $params["is_up_market"] ; $fill_num++;
        }
        if(isset($params["market_type"]) )
        {
            $data["market_type"] = $params["market_type"] ; $fill_num++;
        }
        if(isset($params["main_business"]) )
        {
            $data["main_business"] = $params["main_business"] ; $fill_num++;
        }
        if(isset($params["run_state"]) )
        {
            $data["run_state"] = $params["run_state"] ; $fill_num++;
        }

        if(empty($row))
        {

            $data["customer_id"] = $customerId;
            $data["create_time"] = date("Y-m-d H:i:s");
            $bool = DB::table($this->customer_company_info)->insert($data);

            return $fill_num;
        }

        $data["update_time"] = $time;
        $bool = DB::table($this->customer_company_info)->where("id",$row->id)->update($data);

        return  $this->countFillNum($row,1);
    }

    //统计已填写资料数量
    protected  function countFillNum($params,$type=0)
    {
        $fill_num = 0;
        if(empty($type))
        {
            !empty($params->name)        && $fill_num++;
            !empty($params->mobile)    && $fill_num++;
            !empty($params->sex)       && $fill_num++;
            !empty($params->card_no)   && $fill_num++;

            !empty($params->birth_year)        && $fill_num++;
            !empty($params->birth_month)       && $fill_num++;
            !empty($params->card_province_id)  && $fill_num++;
            !empty($params->card_city_id)      && $fill_num++;

            !empty($params->card_address)      && $fill_num++;
            !empty($params->live_province_id)  && $fill_num++;
            !empty($params->live_city_id)      && $fill_num++;
            !empty($params->live_address)      && $fill_num++;

            isset($params->is_marry)           && $fill_num++;
            !empty($params->nation)            && $fill_num++;

            return $fill_num;
        }

        !empty($params->name)      && $fill_num++;
        !empty($params->mobile)    && $fill_num++;
        !empty($params->sex)       && $fill_num++;
        !empty($params->company_name)   && $fill_num++;

        !empty($params->regist_number)          && $fill_num++;
        !empty($params->law_person)             && $fill_num++;
        !empty($params->found_time)             && $fill_num++;
        !empty($params->is_general_taxpayer)    && $fill_num++;
        !empty($params->regist_money)         && $fill_num++;

        !empty($params->card_province_id)      && $fill_num++;
        !empty($params->card_city_id)          && $fill_num++;

        !empty($params->card_address)          && $fill_num++;
        !empty($params->live_address)          && $fill_num++;

        !empty($params->live_province_id)  && $fill_num++;
        !empty($params->live_city_id)      && $fill_num++;
        !empty($params->customer_type)     && $fill_num++;
        isset($params->is_up_market)      && $fill_num++;
        !empty($params->market_type)       && $fill_num++;
        !empty($params->main_business)     && $fill_num++;
        //经营状况
        !empty($params->run_state)         && $fill_num++;

        return $fill_num;
    }
}
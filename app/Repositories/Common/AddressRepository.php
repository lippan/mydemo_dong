<?php

/**
 *  公共地址资源
 */
namespace App\Repositories\Common;


use Illuminate\Support\Facades\DB;

class AddressRepository
{
    use \App\Repositories\BaseRepository;
    use \App\Helpers\ApiResponse;

    private $address_table;

    //初始化表信息
    public function __construct()
    {
        $this->address_table = config("db_table.address_table");
    }


    /**
     * 获取省份
     * @param $id
     * @return object
     */
    public function getProvince($id = null,$country="china")
    {
        if(empty($id))
            $data = DB::table($this->address_table)->where("level",1)->where("code",0)->get();
        else
            $data = DB::table($this->address_table)->where("id",$id)->first();

        return $data;
    }

    /**
     * 获取城市
     * @param $id
     * @return object
     */
    public function getCity($id, $type="list")
    {
        if($type == "list")
            $data = DB::table($this->address_table)->where("code",$id)->where("level",2)->get();
        else
            $data = DB::table($this->address_table)->where("id",$id)->where("level",2)->first();

        return $data;
    }




    /**
     * 获取地区
     * @param $id
     * @return object
     */
    public function getArea($id,$type="list")
    {
        if($type == "list")
            $data = DB::table($this->address_table)->where("code",$id)->where("level",3)->get();
        else
            $data = DB::table($this->address_table)->where("id",$id)->where("level",3)->first();

        return $data;
    }

}
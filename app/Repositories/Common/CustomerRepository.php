<?php

/**
 *  公共客户资源
 */
namespace App\Repositories\Common;


use Illuminate\Support\Facades\DB;

class CustomerRepository
{
    use \App\Repositories\BaseRepository;
    use \App\Helpers\ApiResponse;

    private $customer_type;
    private $main_business;
    private $tag_product;
    private $tag_weight;
    private $source;

    //初始化需要用到的表名称
    public function __construct()
    {
        $this->customer_type       = config('db_table.customer_type');
        $this->main_business       = config('db_table.main_business');
        $this->tag_product         = config('db_table.tag_product');
        $this->tag_weight          = config('db_table.tag_weight');
        $this->source              = config('db_table.crm_source');
    }


    /**
     * 获取客户类型列表
     */
    public function typeLists()
    {
        $list = DB::table($this->customer_type)->get();

        return $list;
    }


    /**
     * 主营行业列表 一级分类
     */
    public function mainCateLists()
    {
        $list = DB::table($this->main_business)->whereRaw('cat_id=code')->get();

        return $list;
    }

    /**
     * 线索来源表
     */
    public function sourceLists($type="offline")
    {
        $list = DB::table($this->source)->where("type",$type)->get();

        return $list;
    }

    /**
     * 产品标签列表
     */
    public function pTagLists()
    {
        $list = DB::table($this->tag_product)->get();

        return $list;
    }

    /**
     * 重量标签列表
     */
    public function wTagLists()
    {
        $list = DB::table($this->tag_weight)->get();

        return $list;
    }


    /**
     * 主营行业  二级列表
     */
    public function mainLists($cat_id)
    {
        $list = DB::table($this->main_business)->where('cat_id',$cat_id)->where("code","<>",$cat_id)->get();

        return $list;
    }
}
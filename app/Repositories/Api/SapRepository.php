<?php

/**
 *  Sap资源
 */
namespace App\Repositories\Api;
use App\Libraries\Crm\Crm;

use App\Libraries\Sap\Sap;
use Illuminate\Support\Facades\DB;

class SapRepository
{
    use \App\Repositories\BaseRepository;
    use \App\Helpers\ApiResponse;


    private $crm_org;
    private $crm_syb_agent;
    private $crm_employee;
    private $crm_position;

    public function __construct()
    {
        $this->crm_org          = config("db_table.crm_org");
        $this->crm_syb_agent    = config("db_table.crm_syb_agent");
        $this->crm_employee     = config("db_table.crm_employee");
        $this->crm_position     = config("db_table.crm_position");

    }

    /**
     * 同步组织信息
     */
    public function syncAuto()
    {
        try
        {
            $sap      = new Sap();
            $result   = $sap->getAllOrg();

        }catch(\Exception $e)
        {
            $this->failed("sap异常:".$e->getMessage());
        }

        //同步组织信息
        if(isset($result["IT_ORG"]) && !empty($result["IT_ORG"]))
            $this->syncOrg($result["IT_ORG"]);

        //同步员工信息
        if(isset($result["IT_EMPLOYEE"]) && !empty($result["IT_EMPLOYEE"]))
            $this->syncEmployee($result["IT_EMPLOYEE"]);

        //同步员工信息
        if(isset($result["IT_POSITION"]) && !empty($result["IT_POSITION"]))
            $this->syncPosition($result["IT_POSITION"]);

        //同步代理商信息
        if(isset($result["IT_SYBDLS"]) && !empty($result["IT_SYBDLS"]))
            $this->syncAgent($result["IT_SYBDLS"]);

    }


    //同步组织信息
    protected function syncOrg($result)
    {
        echo "--------start_sync_org---------"."<br/>";
        $data = [];
        foreach($result as $val)
        {
            $data[] = [
                "langu"         => trim($val["LANGU"]),
                "org_id"        => trim($val["ORG_ID"]),
                "org_bp"        => trim($val["ORG_BP"]),
                "org_text"      => trim($val["ORG_TEXT"]),
                "org_up"        => trim($val["ORG_UP"]),
                "org_up_text"   => trim($val["ORG_UP_TEXT"])
            ];
        }

        //删除组织架构表
        DB::table($this->crm_org)->truncate();
        //新增组织架构记录
        DB::table($this->crm_org)->insert($data);

        echo "--------over_sync_org---------"."<br/>";
    }

    //同步员工信息表
    protected function syncEmployee($result)
    {
        echo "--------start_sync_employee---------"."<br/>";

        $data = [];
        foreach($result as $val)
        {
            $data[] = [
                "langu"             => trim($val["LANGU"]),
                "partner"           => trim($val["PARTNER"]),
                "partner_text"      => trim($val["PARTNER_TEXT"]),
                "user_id"           => trim($val["USERID"]),
                "tel_number"        => trim($val["TEL_NUMBER"]),
                "position_id"       => trim($val["POSITION_ID"]),
                "position_text"     => trim($val["POSITION_TEXT"]),
                "org_id"            => trim($val["ORG_ID"]),
                "org_text"          => trim($val["ORG_TEXT"]),
            ];
        }

        DB::table($this->crm_employee)->truncate();
        DB::table($this->crm_employee)->insert($data);

        echo "--------over_sync_employee---------"."<br/>";
    }

    //同步岗位表
    protected function syncPosition($result)
    {
        echo "--------start_sync_position---------"."<br/>";

        $data = [];
        foreach($result as $val)
        {
            $data[] = [
                "langu"             => trim($val["LANGU"]),
                "position_id"       => trim($val["POSITION_ID"]),
                "position_text"     => trim($val["POSITION_TEXT"]),
                "org_id"            => trim($val["ORG_ID"]),
                "org_text"          => trim($val["ORG_TEXT"]),
                "profile"           => trim($val["PROFILE"]),
            ];
        }


        DB::table($this->crm_position)->truncate();
        DB::table($this->crm_position)->insert($data);

        echo "--------over_sync_position---------"."<br/>";
    }


    //同步事业部代理商表
    protected function syncAgent($result)
    {
        echo "--------start_sync_agent---------"."<br/>";

        $data = [];
        foreach($result as $val)
        {
            $data[] = [
                "syb_org_id"    => trim($val["SYB_ORG_ID"]),
                "syb_id"        => trim($val["SYB_ID"]),
                "attrib"        => trim($val["ATTRIB"]),
                "partner_no"    => trim($val["PARTNER_NO"]),
                "partner_no_desc"   => trim($val["PARTNER_NO_DESC"]),
                "partner_no_text"   => trim($val["PARTNER_NO_TEXT"])
            ];
        }

        DB::table($this->crm_syb_agent)->truncate();
        DB::table($this->crm_syb_agent)->insert($data);

        echo "--------start_sync_agent---------"."<br/>";
    }
}
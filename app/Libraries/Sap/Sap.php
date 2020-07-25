<?php
namespace App\Libraries\Sap;
use Illuminate\Support\Facades\DB;
use \SAPNWRFC\Connection as SapConnection;
use \SAPNWRFC\Exception as SapException;

/**
 * Sap类
 */
class Sap
{

    private $sap;
    private $org_id;

    /**
     * sap远程函数列表
     * @var array
     */
    public static $api = [
        'ping_test'  => ['name' => 'rfc测试ping函数','function'=> 'RFC_PING','params'=>[]]
    ];


    //构造函数
    public function __construct($config = ['ashost' => '10.0.13.51', 'sysnr'  => '00', 'client' => '300', 'user'   => 'daiy11', 'passwd' => '7ujm&UJM'])
    {
        $this->org_id = "50000673";
        if(config('app.env') != "local")
        {
            $config = ['ashost' => '172.16.9.181', 'sysnr'  => '06', 'client' => '800', 'user'   => 'RFC_BPMUSER', 'passwd' => 'BPM2crm!2015'];
            $this->org_id = config("app.org_id");
        }
        $this->sap = new SapConnection($config);
    }


    /**
     * crm登录
     */
    public function login($account,$password)
    {
        $f = $this->sap->getFunction('ZFM_CRM_LOGIN'); //sap的方法/函数
        $result = $f->invoke([   //给sap传参数
            'IV_UNAME'      => $account,
            'IV_PASSWORD'   => $password,
        ]);

        return $result["EV_MSGTY"] == "E" ? false:true;
    }


    /**
     * 获取商品列表
     */
    public function getCategory($category_id= '')
    {
        $f = $this->sap->getFunction('ZFM_R_MOB_GET_CATEGORY'); //sap的方法/函数
        $result = $f->invoke([   //给sap传参数
            'IS_BASE'           => ["LANGU"=>'ZH'],
            'IV_CATEGORY_ID'    => empty($category_id) ? '':$category_id,
        ]);

        return $result;
    }

    /**
     * 获取物料清单
     * @return mixed
     */
    public function getBomList($category_id= '')
    {
        $f = $this->sap->getFunction('ZFM_R_MOB_PRODUCT_SEARCH_N'); //sap的方法/函数
        $result = $f->invoke([   //给sap传参数
            'IS_BASE'           => ["LANGU"=>'ZH'],
            'IV_CATEGORY_ID'    => empty($category_id) ? '':$category_id,
        ]);

        return $result;
    }


    /**
     * 导入职位表
     */
    public function importPosition()
    {
        $f = $this->sap->getFunction('ZFM_CRM_ORG_ALL'); //sap的方法/函数
        $result = $f->invoke([   //给sap传参数
            'IS_BASE' => ["LANGU"=>'ZH'],
            'IV_OBJID' => $this->org_id,
        ]);


        $data = [];
        foreach($result["IT_POSITION"] as $val)
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

        $crm_org    = config("db_table.crm_position");
        $bool       = DB::table($crm_org)->insert($data);

        echo "插入成功";
    }


    /**
     * 导入雇员关系表
     */
    public function importEmployee()
    {
        $f = $this->sap->getFunction('ZFM_CRM_ORG_ALL'); //sap的方法/函数
        $result = $f->invoke([   //给sap传参数
            'IS_BASE' => ["LANGU"=>'ZH'],
            'IV_OBJID' => $this->org_id,
        ]);


        $data = [];
        foreach($result["IT_EMPLOYEE"] as $val)
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

        $crm_org    = config("db_table.crm_employee");
        $bool       = DB::table($crm_org)->insert($data);

        echo "插入成功";
    }

    /**
     * 导入事业部代理商表
     */
    public function importDepartAgent()
    {
        $f = $this->sap->getFunction('ZFM_CRM_ORG_ALL'); //sap的方法/函数
        $result = $f->invoke([   //给sap传参数
            'IS_BASE' => ["LANGU"=>'ZH'],
            'IV_OBJID' => $this->org_id,
        ]);


        $data = [];
        foreach($result["IT_SYBDLS"] as $val)
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

        $crm_org    = config("db_table.crm_syb_agent");
        $bool       = DB::table($crm_org)->insert($data);

        echo "插入成功";
    }


    /**
     * 导入组织表
     */
    public function getAllOrg()
    {
        $f = $this->sap->getFunction('ZFM_CRM_ORG_ALL'); //sap的方法/函数
        $result = $f->invoke([   //给sap传参数
            'IS_BASE' => ["LANGU"=>'ZH'],
            'IV_OBJID' => $this->org_id,
        ]);

        return $result;
    }
}
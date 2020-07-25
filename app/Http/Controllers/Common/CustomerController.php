<?php
/**
 *  客户控制器
 */
namespace App\Http\Controllers\Common;

use App\Http\Controllers\BaseController;
use App\Repositories\Common\AddressRepository;
use App\Repositories\Common\CustomerRepository;
use App\Repositories\Common\DepartRepository;
use Illuminate\Http\Request;

class CustomerController extends BaseController
{


    /**
     * 获取用户类型列表
     * @param Request $request
     *
     */
    public function typeLists(Request $request,CustomerRepository $customerRepository)
    {
        $list = $customerRepository->typeLists();
        $this->success($list);
    }

    /**
     * 初始化获取列表参数
     */
    public function init(Request $request,CustomerRepository $customerRepository)
    {
        $type     = (int)$request->post("type");
        if(empty($type))
        {
            $typeList = $customerRepository->typeLists();
            $mainList = $customerRepository->mainCateLists();
            $ptagList = $customerRepository->pTagLists();
            $wtagList = $customerRepository->wTagLists();
            $source   = $customerRepository->sourceLists();
            $addressRepository = new AddressRepository();
            $adress = $addressRepository->getCity(config("app.belong_province_id"));

            $data = [
                "customer_type" => $typeList,//客户类型
                "main_business" => $mainList,//主营分类
                "tag_product"   => $ptagList,//商品标签
                "tag_weight"    => $wtagList,//重量标签
                "source"        => $source,//线索来源
                "address"       => $adress
            ];
            $this->success($data);
        }
        elseif($type == 1)//客户业务
        {
            $addressRepository = new AddressRepository();
            $list = $addressRepository->getCity(config("app.belong_province_id"));
            $this->success($list);
        }
        else
        {
            $addressRepository = new AddressRepository();
            $adress = $addressRepository->getCity(config("app.belong_province_id"));
            $source = $customerRepository->sourceLists();

            $origin_category = config("app.sell_category");

            $data["address"]        = $adress;
            $data["source"]         = $source;
            $data["origin_category"]= $origin_category;
            $this->success($data);
        }
    }

    /**
     * 主营列表
     * @param Request $request
     */
    public function mainLists(Request $request,CustomerRepository $customerRepository)
    {
        $cat_id = $request->post("cat_id");
        if(empty($cat_id))
            $this->failed("主营业务不能为空");

        $list = $customerRepository->mainLists($cat_id);

        $this->success($list);
    }
}
